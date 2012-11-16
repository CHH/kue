<?php

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/job.php');

use Symfony\Component\Console\Application;

$queue = new Kue\LocalQueue;

$worker = new Kue\Command\WorkerCommand($queue);

$app = new Application;
$app->add($worker);

$app->run();

