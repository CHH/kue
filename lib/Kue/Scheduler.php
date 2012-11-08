<?php

namespace Kue;

use DateTime;
use DateInterval;

use Kue\Scheduler\Expression;
use Kue\Scheduler\CronExpression;
use Kue\Scheduler\SimpleDateStringExpression;

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
        $expression = new SimpleDateStringExpression($interval);
        $this->add($expression, $job);

        return $this;
    }

    function cron($expression, Job $job)
    {
        $expression = new CronExpression($expression);
        $this->add($expression, $job);

        return $this;
    }

    function add(Expression $expression, Job $job)
    {
        $this->jobs[] = array($expression, $job);
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
        $now = new DateTime('now');

        $scheduled = 0;

        foreach ($this->jobs as $entry) {
            list($expression, $job) = $entry;

            if ($expression->isDue($now)) {
                $this->queue->push($job);
                $scheduled += 1;
            }
        }

        $this->queue->flush();

        return $scheduled;
    }
}

