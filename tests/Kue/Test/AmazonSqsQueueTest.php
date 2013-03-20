<?php

namespace Kue\Test;

use Kue\AmazonSqsQueue;
use Aws\Common\Enum\Region;

class AmazonSqsQueueTest extends \PHPUnit_Framework_TestCase
{
    protected $queue;
    protected $jobsToDelete = array();

    function setup()
    {
        $this->queue = new AmazonSqsQueue($_ENV['SQS_QUEUE_NAME'], array(
            'key' => $_ENV['AWS_ACCESS_KEY'],
            'secret' => $_ENV['AWS_SECRET_KEY'],
            'region' => $_ENV['AWS_REGION'],
        ));
    }

    function test()
    {
        $in = new TestJob("Hello World");

        $this->queue->push($in);
        $this->queue->flush();

        $out = $this->queue->pop();

        $this->assertNotNull($out);

        $this->jobsToDelete[] = $out;

        $this->assertEquals($in->getMessage(), $out->getMessage());
    }

    function tearDown()
    {
        foreach ($this->jobsToDelete as $job) {
            $this->queue->deleteJob($job);
        }
    }
}
