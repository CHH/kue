<?php

namespace Kue;

/**
 * Simple implementation of a queue, that stores jobs into a Redis list.
 *
 * Uses http://github.com/nicolasff/phpredis
 *
 * @author Christoph Hochstrasser <christoph.hochstrasser@gmail.com>
 */
class RedisQueue implements Queue
{
    const QUEUE_KEY = "spark:queue";

    /** @var \Redis */
    protected $redis;

    function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Does a Redis BLPOP on the queue, and blocks until a job is available.
     *
     * @return Job
     */
    function pop()
    {
        $response = $this->redis->blPop(self::QUEUE_KEY, 10);

        if ($response) {
            list($list, $serializedJob) = $response;

            $job = unserialize($serializedJob);
            return $job;
        }
    }

    function push(Job $job)
    {
        $this->redis->rPush(self::QUEUE_KEY, serialize($job));
    }

    function flush()
    {
        # We send jobs directly in `push`, so we don't need to flush
    }
}
