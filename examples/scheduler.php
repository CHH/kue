<?php

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/job.php');

use Kue\Scheduler\Time;

$scheduler = new Kue\Scheduler(new Kue\LocalQueue);

$scheduler->every(20*Time::SECOND, new HelloJob);
$scheduler->every(30*Time::SECOND, new FooJob);

$scheduler->cron('*/5 * * * *', new CronJob);

for (;;) {
    var_dump($scheduler->run());
    var_dump(date('Y-m-d H:i:s'));
    //sleep(1);
}

