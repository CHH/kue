<?php

namespace Kue\Scheduler;

interface Expression
{
    function getNextRunDate($currentTime = 'now');
    function isDue(\DateTime $currentTime = null);
}

