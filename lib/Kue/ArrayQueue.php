<?php

namespace Kue;

/**
 * Simple Array backed Queue, intended for testing.
 *
 * @author Christoph Hochstrasser <christoph.hochstrasser@gmail.com>
 */
class ArrayQueue implements Queue
{
    public $queue = array();

    function pop()
    {
        if (count($this->queue) > 0) {
            return array_pop($this->queue);
        }
    }

    function push(Job $job)
    {
        $this->queue[] = $job;
    }

    function flush()
    {}

    function process(Worker $worker)
    {}
}
