<?php

namespace Kue\Test;

use Kue\ImmediateQueue;

class ImmediateQueueTest extends \PHPUnit_Framework_TestCase
{
    function testImmediatelyCallsRunOnPush()
    {
        $queue = new ImmediateQueue;
        $job = new TestJob("Foo");

        $queue->push($job);

        $this->assertTrue($job->finished);
    }
}
