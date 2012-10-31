<?php

namespace Kue;

use SplQueue;

/**
 * Simple queue implementation, which uses a local socket to hold
 * the jobs.
 *
 * This queue is designed to be used by two separate processes:
 *
 * + one that watches the queue with the `pop()` method and runs the
 *   receiving side.
 * + one or more senders, which use `push()` and `flush()` to send serialized
 *   job instances to the receiving side (one per line).
 *
 * @author Christoph Hochstrasser <christoph.hochstrasser@gmail.com>
 */
class LocalQueue implements Queue
{
    private $socket = "tcp://127.0.0.1:33133";

    private $client;
    private $queue;

    private $server;

    /** @var \SplQueue Received jobs */
    private $received;

    function __construct()
    {
        $this->received = new SplQueue;

        $this->queue = new SplQueue;
        $this->queue->setIteratorMode(SplQueue::IT_MODE_FIFO | SplQueue::IT_MODE_DELETE);
    }

    /**
     * Override the default socket.
     *
     * @param string $socket
     * @return void
     */
    function setSocket($socket)
    {
        $this->socket = $socket;
    }

    function pop()
    {
        if (count($this->received) === 0) {
            $this->acceptJobs();
        }

        if (count($this->received) > 0) {
            return $this->received->pop();
        }
    }

    function push(Job $job)
    {
        $this->queue->push($job);
    }

    function flush()
    {
        if ($this->queue->count() === 0) {
            return;
        }

        $client = $this->client();

        foreach ($this->queue as $job) {
            $data = serialize($job);
            fwrite($client, "$data\r\n");
        }

        fclose($client);
    }

    function process(Worker $worker)
    {
        # Force server startup when processing is attempted.
        $this->server();
    }

    private function acceptJobs()
    {
        $server = $this->server();

        $r = [$server];
        $w = null;
        $x = null;

        if (@stream_select($r, $w, $x, 10) > 0 and $r) {
            $conn = @stream_socket_accept($r[0]);

            if ($conn) {
                while ($serializedJob = fgets($conn)) {
                    $this->received->push(unserialize($serializedJob));
                }
            }
        }
    }

    private function server()
    {
        if (null === $this->server) {
            $this->server = stream_socket_server($this->socket, $errno, $errstr);

            if (false === $this->server) {
                throw new \InvalidArgumentException(sprintf(
                    'Could not start server: %s', $errstr
                ));
            }
        }

        return $this->server;
    }

    private function client()
    {
        if (null === $this->client) {
            $this->client = stream_socket_client($this->socket, $errno, $errstr);

            if ($this->client === false) {
                throw new \InvalidArgumentException(sprintf(
                    'Connecting to socket failed: %s', $errstr
                ));
            }
        }

        return $this->client;
    }
}

