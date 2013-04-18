<?php

namespace Kue;

use Evenement\EventEmitter;

class SequentialWorker extends EventEmitter implements Worker
{
    function process(Queue $queue)
    {
        $queue->process($this);

        for (;;) {
            $job = $queue->pop();

            if ($job) {
                $this->emit('init', array($job));

                $self = $this;

                set_error_handler(function($code, $message, $file, $line) use ($job, $self) {
                    throw new \ErrorException($message, $code, 0, $file, $line);
                });

                try {
                    $job->run();
                    $this->emit('success', array($job));

                } catch (\Exception $e) {
                    $this->emit('exception', array($job, $e));
                }

                restore_error_handler();
            }
        }
    }
}
