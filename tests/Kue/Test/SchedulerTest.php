<?php

namespace Kue\Test;

use Kue\Scheduler;
use Kue\Scheduler\Time as t;
use Kue\ArrayQueue;

class SchedulerTest extends \PHPUnit_Framework_TestCase
{
    function testRunReturnsNumberOfJobsScheduled()
    {
        $queue = $this->getMock('\Kue\ArrayQueue');
        $queue->expects($this->once())->method('push');

        $scheduler = new Scheduler($queue);
        $scheduler->every(1*t::SECOND, new TestJob("Hello World"));

        $this->assertEquals(1, $scheduler->run());
    }
}

