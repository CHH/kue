<?php

namespace Kue\Test;

class TestJob implements \Kue\Job
{
    protected $message;
    public $finished;

    function __construct($message)
    {
        $this->message = $message;
        $this->finished = false;
    }

    function getMessage()
    {
        return $this->message;
    }

    function run()
    {
        $this->finished = true;
    }
}
