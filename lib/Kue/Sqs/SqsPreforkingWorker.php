<?php

namespace Ext\Kue;

use Kue\PreforkingWorker;

class SqsPreforkingWorker extends PreforkingWorker {

	protected $delay;

	public function __construct($worker_poolsize, $delay = 1) {
		parent::__construct($worker_poolsize);
		$this->delay = $delay;

		$worker = $this;
		$this->on('success', function($job) use($worker) {
			$worker->getQueue()->deleteJob($job);
		});
	}

	public function getQueue() {
		return $this->queue;
	}

	protected function processJobs() {
		parent::processJobs();
		sleep($this->delay);
	}
}