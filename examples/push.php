<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/job.php';

$queue = new Kue\LocalQueue;
$queue->push(new HelloJob);
$queue->flush();

