<?php

namespace Kue\Test;

class TestJob implements \Kue\Job
{
    protected $message;

    function __construct($message)
    {
        $this->message = $message;
    }

    function getMessage()
    {
        return $this->message;
    }

    function run()
    {
    }
}

