<?php

namespace Kue;

use DateTime;
use DateInterval;

/**
 * Simple, tickless scheduler which puts jobs into the queue.
 *
 * @author Christoph Hochstrasser <christoph.hochstrasser@gmail.com>
 */
class Scheduler
{
    protected $jobs = array();
    protected $queue;
    protected $lastRun;

    function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    /**
     * Adds the job to the scheduler.
     *
     * @param string $interval Any string accepted by DateTime::modify()
     * @param Job $job
     *
     * @return Scheduler
     */
    function every($interval, Job $job)
    {
        $this->jobs[] = array($interval, $job);

        return $this;
    }

    /**
     * Schedules jobs, sleeps until a job has to be scheduled. Returns 
     * when jobs were scheduled.
     *
     * @return int Number of scheduled jobs.
     */
    function run()
    {
        $now = new DateTime;

        $sleep = min(array_map(
            function($current) use ($now) {
                list($interval, $job) = $current;

                $then = clone $now;
                $then->modify($interval);

                $seconds = $then->getTimestamp() - $now->getTimestamp();

                return $now->getTimestamp() + ($seconds - ($now->getTimestamp() % $seconds));
            },
            $this->jobs
        ));

        time_sleep_until($sleep);

        $scheduled = 0;

        foreach ($this->jobs as $entry) {
            list($interval, $job) = $entry;

            $then = clone $now;
            $then->modify($interval);

            $seconds = $then->getTimestamp() - $now->getTimestamp();

            if ($now->getTimestamp() % $seconds === 0) {
                $this->queue->push($job);
                $scheduled += 1;
            }
        }

        $this->queue->flush();

        return $scheduled;
    }
}

