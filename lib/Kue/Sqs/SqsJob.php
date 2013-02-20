<?php

namespace Kue\Sqs;

use Kue\Job;

abstract class SqsJob implements Job
{

	protected $data = array();
	protected $receiptHandle;

	public function __construct($data)
	{
		$this->data = $data;
	}

	public function getData()
	{
		return $this->data;
	}

	public function getReceiptHandle()
	{
		return $this->receiptHandle;
	}

	public function setReceiptHandle($receipt)
	{
		$this->receiptHandle = $receipt;
	}

	public abstract function run();
}