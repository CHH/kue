<?php

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/job.php');

$scheduler = new Kue\Scheduler(new Kue\LocalQueue);

$scheduler->every('20 seconds', new HelloJob);
$scheduler->every('30 seconds', new FooJob);

for (;;) {
    var_dump($scheduler->run());
    sleep(1);
}

