<?php

namespace Kue;

use Evenement\EventEmitter;
use UnexpectedValueException;

class PreforkingWorker extends EventEmitter implements Worker
{
    # Used to write messages back from the child to the parent.
    protected $selfPipe = array();

    # Internal: PIDs of all Workers
    protected $workers;

    protected $workerPoolSize = 0;

    protected $shutdown = false;

    protected $pidFile;

    protected $queue;

    # 30 minutes
    protected $workerTimeout = 1800;

    function __construct($workerPoolSize)
    {
        $this->workerPoolSize = $workerPoolSize;
        $this->pidFile = sys_get_temp_dir() . '/kue_worker.pid';
    }

    function setPidFile($pidFile)
    {
        $this->pidFile = $pidFile;
    }

    function setWorkerTimeout($timeout)
    {
        $this->workerTimeout = $timeout;
    }

    function process(Queue $queue)
    {
        if (file_exists($this->pidFile)) {
            $serverPid = trim(file_get_contents($this->pidFile));

            # When the process is not running anymore and a PID file exists
            # than the PID file was not correctly unlinked on the last shutdown.
            if (!posix_kill($serverPid, 0)) {
                unlink($this->pidFile);
            } else {
                throw new UnexpectedValueException("Server is already running with PID $serverPid.");
            }
        }

        if (false === @file_put_contents($this->pidFile, posix_getpid())) {
            throw new UnexpectedValueException(sprintf(
                'Could not create %s. Maybe you have no permission to write it.',
                $this->pidFile
            ));
        }

        $this->selfPipe = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        $this->queue = $queue;
        $queue->process($this);

        # Spawn up the initial worker pool.
        $this->spawnWorkers();

        register_shutdown_function(array($this, "stopListening"));

        pcntl_signal(SIGTTIN, array($this, "incrementWorkerCount"));
        pcntl_signal(SIGTTOU, array($this, "decrementWorkerCount"));
        pcntl_signal(SIGQUIT, array($this, "shutdown"));

        pcntl_signal(SIGINT, function() {
            exit();
        });

        # Monitor the child processes.
        for (;;) {
            pcntl_signal_dispatch();

            if ($this->shutdown) {
                foreach ($this->workers as $pid => $info) {
                    posix_kill($pid, SIGQUIT);
                    pcntl_waitpid($pid, $procStatus);
                }

                $this->stopListening();
                return;
            }

            $read      = array($this->selfPipe[1]);
            $write     = null;
            $exception = null;
            $readySize = @stream_select($read, $write, $exception, 10);

            # Handle the heartbeat sent by a worker.
            if ($readySize > 0 and $read) {
                $childPid = trim(fgets($read[0]));
                $this->workers[$childPid]["heartbeat"] = time();
            }

            $this->removeStaleWorkers();

            # Maintain a stable worker count. Compares the actual worker count
            # to the configured worker count and spawns workers when necessary.
            $this->spawnWorkers();
        }
    }

    function incrementWorkerCount()
    {
        ++$this->workerPoolSize;
        $this->spawnWorkers();
    }

    function decrementWorkerCount()
    {
        --$this->workerPoolSize;

        $workersToKill = count($this->workers) - $this->workerPoolSize;

        foreach (array_slice($this->workers, 0, $workersToKill) as $pid => $info) {
            posix_kill($pid, SIGKILL);
        }
    }

    # Does a save shutdown of the server process. Sets the shutdown flag,
    # the main loop then exits when it sees that the flag is set.
    function shutdown()
    {
        $this->shutdown = true;
    }

    function stopListening()
    {
        @fclose($this->selfPipe[0]);
        @fclose($this->selfPipe[1]);

        @unlink($this->pidFile);
    }

    protected function removeStaleWorkers()
    {
        $now = time();

        # Go through all workers and kill those who did not
        # made a heartbeat within the timeout period or
        # remove those who exited.
        foreach ($this->workers as $pid => $info) {
            # Look if the child has exited.
            if ($pid === pcntl_waitpid($pid, $s, WNOHANG)) {
                unset($this->workers[$pid]);

            # Look if the child's last heartbeat was not made within 
            # the timeout period.
            } else if ($now - $info["heartbeat"] > $this->workerTimeout) {
                # Kill the worker and remove it from the workers array.
                posix_kill($pid, SIGKILL);
                unset($this->workers[$pid]);
            }
        }
    }

    protected function spawnWorkers()
    {
        $workersToSpawn = abs($this->workerPoolSize - count($this->workers));

        if ($workersToSpawn == 0) {
            return 0;
        }

        for ($i = 0; $i < $workersToSpawn; $i++) {
            $this->emit("beforeFork", array($this));

            $pid = pcntl_fork();

            if ($pid === -1) {
                # Error while forking.
                exit();

            } else if ($pid) {
                # Parent, save the worker's process ID.
                $this->workers[$pid] = array(
                    "heartbeat" => time()
                );
            } else {
                $this->emit("afterFork", array($this));
                # Child:
                $this->workerProcess();
                exit();
            }
        }

        return $i;
    }

    protected function workerProcess()
    {
        pcntl_signal(SIGINT, function() {
            exit();
        });

        pcntl_signal(SIGQUIT, array($this, "shutdown"));

        fclose($this->selfPipe[1]);

        for (;;) {
            pcntl_signal_dispatch();
            $this->workerExecute();

            if ($this->shutdown) exit();
        }
    }

    protected function workerExecute()
    {
        $read      = null;
        $write     = null;
        $exception = array($this->selfPipe[0]);

        # Wait for data ready to be read, or look for exceptions
        $readySize = @stream_select($read, $write, $exception, 0);

        if ($readySize === false) {
            # Error happened
            exit(1);

        } else if ($readySize > 0) {
            # Let the child kill itself when the parent closed the pipe 
            # (parent was killed)
            if ($exception) {
                exit();
            }
        }

        $this->processJobs();

        # Send the heartbeat to the parent process everytime stream_select() times out.
        $msg = posix_getpid()."\n";

        # Exit when the fwrite() fails (when parent died and stream_select()
        # did not catch this).
        if (@fwrite($this->selfPipe[0], $msg) < strlen($msg)) exit();
    }

    protected function processJobs()
    {
        $job = $this->queue->pop();

        if ($job) {
            $this->emit('init', array($job));

            try {
                $job->run();
                $this->emit('success', array($job));
            } catch (\Exception $e) {
                $this->emit('exception', array($job, $e));
            }
        }
    }
}

