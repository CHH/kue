<?php

namespace Kue\Test;

use Kue\Scheduler;
use Kue\ArrayQueue;

class TestJob implements \Kue\Job
{
    function run()
    {
    }
}

class SchedulerTest extends \PHPUnit_Framework_TestCase
{
    function testRunReturnsNumberOfJobsScheduled()
    {
        $queue = $this->getMock('\Kue\ArrayQueue');
        $queue->expects($this->once())->method('push');

        $scheduler = new Scheduler($queue);
        $scheduler->every('1 seconds', new TestJob);

        $t1 = time();
        $this->assertEquals(1, $scheduler->run());

        $this->assertEquals(1, time() - $t1);
    }
}

