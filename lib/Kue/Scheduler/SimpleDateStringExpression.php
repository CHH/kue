<?php

namespace Kue\Scheduler;

class SimpleDateStringExpression implements Expression
{
    protected $expression;
    protected $duration;

    function __construct($expression)
    {
        # Only for reference.
        $this->expression = $expression;

        $now = new \DateTime('now');
        $then = clone $now;
        $then->modify($expression);

        $this->duration = $then->getTimestamp() - $now->getTimestamp();
    }

    function getNextRunDate($currentTime = 'now')
    {
        if ($currentTime instanceof \DateTime) {
            $now = $currentTime;
        } else {
            $now = new \DateTime($currentTime);
        }

        return $now->getTimestamp() + ($this->duration - ($now->getTimestamp() % $this->duration));
    }

    function isDue(\DateTime $currentTime = null)
    {
        if (null === $currentTime) {
            $currentTime = new \DateTime('now');
        }

        return $currentTime->getTimestamp() % $this->duration === 0;
    }
}

