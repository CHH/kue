<?php

namespace Kue\Scheduler;

use DateTime;
use DateInterval;

class SimpleExpression implements Expression
{
    protected $expression;

    function __construct($expression)
    {
        $this->expression = $expression;
    }

    function getNextRunDate($currentTime = 'now')
    {
        if ($currentTime instanceof DateTime) {
            $now = $currentTime;
        } else {
            $now = new DateTime($currentTime);
        }

        $duration = $this->expression;

        $date = new DateTime;
        $date->setTimestamp($now->getTimestamp() + ($duration - ($now->getTimestamp() % $duration)));

        return $date;
    }

    function isDue(DateTime $currentTime = null)
    {
        if (null === $currentTime) {
            $currentTime = new \DateTime('now');
        }

        $duration = $this->expression;

        return $currentTime->getTimestamp() % $duration === 0;
    }
}

