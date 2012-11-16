<?php

namespace Kue\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use Kue\Queue;
use Kue\Job;
use Kue\SequentialWorker;
use Kue\PreforkingWorker;
use Kue\Worker;

class WorkerCommand extends Command
{
    protected $log;
    protected $queue;
    protected $events;

    function __construct(Queue $queue, $events = array())
    {
        $this->queue = $queue;
        $this->events = $events;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('kue:worker')
            ->setDescription('Processes jobs put into the queue')
            ->addOption('workers', 'c', InputOption::VALUE_REQUIRED, 'Number of workers', 1)
            ->addOption('require', 'r', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'File(s) to require before accepting jobs');
    }

    protected function setupWorker(Worker $worker)
    {
        $log = $this->log;

        $worker->on('init', function(Job $job) use ($log) {
            $log->addInfo(sprintf('Accepted job %s', get_class($job)), array('job' => $job));
        });

        $worker->on('error', function(Job $job, $code, $message, $file, $line) use ($log) {
            $log->addError(
                sprintf('Job %s failed: "%s" in file %s:%d', get_class($job), $message, $file, $line),
                array('job' => $job)
            );
        });

        $worker->on('exception', function(Job $job, \Exception $exception) use ($log) {
            $log->addError(sprintf('Job "%s" failed: %s', get_class($job), $exception), array('job' => $job));
        });

        $worker->on('success', function(Job $job) use ($log) {
            $log->addInfo(sprintf('Job "%s" finished successfully', get_class($job)), array('job' => $job));
        });

        foreach ($this->events as $event => $handler) {
            # A single event handler
            if (is_callable($handler)) {
                $worker->on($event, $handler);
            # Multiple handlers for the event
            } elseif (is_array($handler)) {
                foreach ($handler as $h) {
                    $worker->on($event, $h);
                }
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->log = new Logger($this->getName());
        $this->log->pushHandler(new StreamHandler(STDERR));

        $require = $input->getOption('require');

        foreach ($require as $file) {
            require_once($file);
        }

        if ($input->getOption('workers') > 1) {
            $worker = new PreforkingWorker($input->getOption('workers'));
        } else {
            $worker = new SequentialWorker;
        }

        $output->writeln(sprintf('Processing jobs using %s on queue %s', get_class($worker), get_class($this->queue)));
        $output->writeln('Stop with [CTRL]+[c]');

        $this->setupWorker($worker);

        $worker->process($this->queue);
    }
}

