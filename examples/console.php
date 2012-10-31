<?php

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/job.php');

use Symfony\Component\Console\Application;

$queue = new Kue\LocalQueue;

$app = new Application;
$app->add(new Kue\Command\Worker($queue));

$app->run();

