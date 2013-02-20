<?php

namespace Kue\Sqs;

use Kue\PreforkingWorker;

class SqsPreforkingWorker extends PreforkingWorker
{

	protected $delay;

	public function __construct($workerPoolsize, $delay = 1)
	{
		parent::__construct($workerPoolsize);
		$this->delay = $delay;

		$worker = $this;
		$this->on('success', function($job) use($worker)
		{
			$worker->getQueue()->deleteJob($job);
		});
	}

	public function getQueue()
	{
		return $this->queue;
	}

	protected function processJobs()
	{
		parent::processJobs();
		sleep($this->delay);
	}
}