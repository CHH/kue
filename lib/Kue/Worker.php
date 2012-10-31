<?php

namespace Kue;

use Evenement\EventEmitterInterface;

interface Worker extends EventEmitterInterface
{
    function process(Queue $queue);
}

