<?php

namespace Ext\Kue;

use Kue\Queue;
use Kue\Job;

use Aws\Common\Enum\ClientOptions;
use Aws\Common\Enum\Region;
use Aws\Sqs\SqsClient;

class SqsQueue implements Queue {

	const REGION_EU_WEST_1 = Region::EU_WEST_1;
	const REGION_US_EAST_1 = Region::US_EAST_1;

	protected $client;
	protected $queue_url;

	public function __construct($access_key, $secret_key, $region, $queue) {
		$this->client = SqsClient::factory(array(
			ClientOptions::KEY		=> $access_key,
			ClientOptions::SECRET	=> $secret_key,
			ClientOptions::REGION	=> $region
		));

		$this->queue_url = $this->client->getQueueUrl(array('QueueName' => $queue))->get('QueueUrl');
	}

	protected function serializeJob($job) {
		return json_encode(array(
			'class'		=> get_class($job),
			'data'		=> json_encode($job->getData())
		));
	}

	protected function unserializeJob($message, $receipt) {
		$job = json_decode($message, true);
		$class = $job['class'];
		$job =  new $class(json_decode($job['data'], true));
		$job->setReceiptHandle($receipt);

		return $job;
	}

	public function deleteJob($job) {
		$this->client->deleteMessage(array(
			'QueueUrl'		=> $this->queue_url,
			'ReceiptHandle'	=> $job->getReceiptHandle()
		));

		return $this;
	}

	/**
	 * Blocks until a job is available, and returns it. This is used by the
	 * worker script that's shipped with Kue.
	 *
	 * @return Job|null Returns either a Job, or Null when the operation
	 * timed out.
	 */
	public function pop() {
		$messages = $this->client->receiveMessage(array('QueueUrl' => $this->queue_url))->get('Messages');
		if(!empty($messages)) {
			return $this->unserializeJob($messages[0]['Body'], $messages[0]['ReceiptHandle']);
		}
	}

	/**
	 * Pushes the job onto the queue.
	 *
	 * @param Job $job
	 * @return void
	 */
	public function push(Job $job) {
		$this->client->sendMessage(array(
			'QueueUrl'		=> $this->queue_url,
			'MessageBody'	=> $this->serializeJob($job),
		));
	}

	/**
	 * Flushes the queue.
	 *
	 * This is mainly useful for queues which are clients to a 3rd party system
	 * which stores the jobs. This method should be called after the response has been sent
	 * to the client, and can be used to send all pushed jobs in one go
	 * without much notice of the user.
	 *
	 * @return void
	 */
	public function flush() {
		// Messages are directly pushed, so no need to flush
	}
}