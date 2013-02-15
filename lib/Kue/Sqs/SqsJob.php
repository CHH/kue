<?php

namespace Ext\Kue;

use Kue\Job;

abstract class SqsJob implements Job {

	protected $data = array();
	protected $receipt_handle;

	public function __construct($data) {
		$this->data = $data;
	}

	public function getData() {
		return $this->data;
	}

	public function getReceiptHandle() {
		return $this->receipt_handle;
	}

	public function setReceiptHandle($receipt) {
		$this->receipt_handle = $receipt;
	}

	public abstract function run();
}