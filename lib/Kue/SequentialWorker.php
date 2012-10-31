<?php

namespace Kue;

use Evenement\EventEmitter;

class SequentialWorker extends EventEmitter implements Worker
{
    function process(Queue $queue)
    {
        for (;;) {
            $job = $queue->pop();

            if ($job) {
                $this->emit('init', array($job));

                $self = $this;

                set_error_handler(function() use ($job, $self) {
                    $event = func_get_args();
                    array_unshift($job, $event);

                    $self->emit('error', $event);
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

