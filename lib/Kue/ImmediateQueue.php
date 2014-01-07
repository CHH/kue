<?php

namespace Kue;

class ImmediateQueue implements Queue
{
    function push(Job $job)
    {
        $job->run();
    }

    function pop()
    {}

    function flush()
    {}

    function process(Worker $worker)
    {}
}
