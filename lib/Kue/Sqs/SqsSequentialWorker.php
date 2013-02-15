<?php

namespace Kue\Sqs;

use Evenement\EventEmitter;
use Kue\Queue;
use Kue\Worker;

class SqsSequentialWorker extends EventEmitter implements Worker {

	public function __construct($delay = 1) {
		$this->delay = $delay;

		$worker = $this;
		$this->on('success', function($job) use($worker) {
			$worker->getQueue()->deleteJob($job);
		});
	}

	public function process(Queue $queue) {
		for (;;) {
			$job = $queue->pop();

			if ($job) {
				$this->emit('init', array($job));

				$self = $this;

				set_error_handler(function($code, $message, $file, $line) use ($job, $self) {
					$e = new \ErrorException($message, $code, 0, $file, $line);

					$self->emit('exception', array($job, $e));
				});

				try {
					$job->run();
					$this->emit('success', array($job));

				} catch (\Exception $e) {
					$this->emit('exception', array($job, $e));
				}

				restore_error_handler();
			}

			sleep($this->delay);
		}
	}
}