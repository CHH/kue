<?php

namespace Kue\Scheduler;

use DateTime;
use DateInterval;

class SimpleDateStringExpression implements Expression
{
    protected $expression;
    protected $interval;

    function __construct($expression)
    {
        # Only for reference
        $this->expression = $expression;
        $this->interval = DateInterval::createFromDateString($expression);
    }

    function getNextRunDate($currentTime = 'now')
    {
        if ($currentTime instanceof DateTime) {
            $now = $currentTime;
        } else {
            $now = new DateTime($currentTime);
        }

        $then = clone $now;
        $then->add($this->interval);

        $duration = $then->getTimestamp() - $now->getTimestamp();

        $date = new DateTime;
        $date->setTimestamp($now->getTimestamp() + ($duration - ($now->getTimestamp() % $duration)));

        return $date;
    }

    function isDue(DateTime $currentTime = null)
    {
        if (null === $currentTime) {
            $currentTime = new \DateTime('now');
        }

        $then = clone $currentTime;
        $then->add($this->interval);

        $duration = $then->getTimestamp() - $currentTime->getTimestamp();

        return $currentTime->getTimestamp() % $duration === 0;
    }
}

