<?php

namespace Kue;

use Aws\Sqs\SqsClient;

class AmazonSqsQueue implements Queue
{
    protected $client;
    protected $queueUrl;
    protected $queueName;
    protected $pending = array();
    protected $waitTimeSeconds = 10;

    function __construct($queueName, array $config)
    {
        $this->queueName = $queueName;
        $this->client = SqsClient::factory($config);
    }

    function setWaitTimeSeconds($time)
    {
        $this->waitTimeSeconds = $time;
    }

    function push(Job $job)
    {
        $this->pending[] = $job;
    }

    function pop()
    {
        $response = $this->client->receiveMessage(array(
            'QueueUrl' => $this->queueUrl(),
            'MaxNumberOfMessages' => 1,
            'WaitTimeSeconds' => $this->waitTimeSeconds,
        ));

        $messages = $response->get('Messages');

        if (!$messages) {
            return;
        }

        $job = unserialize(rawurldecode($messages[0]['Body']));
        $job->_amazonSqsReceiptHandle = $messages[0]['ReceiptHandle'];

        return $job;
    }

    function deleteJob(Job $job)
    {
        if (!isset($job->_amazonSqsReceiptHandle)) {
            throw new \InvalidArgumentException("Job is not a valid Amazon SQS job");
        }

        $this->client->deleteMessage(array(
            'QueueUrl' => $this->queueUrl(),
            'ReceiptHandle' => $job->_amazonSqsReceiptHandle
        ));
    }

    function flush()
    {
        $messages = array_map(function(Job $job) {
            $body = serialize($job);
            $id = sha1($body . uniqid());

            return array(
                'Id' => $id,
                'MessageBody' => rawurlencode($body)
            );
        }, $this->pending);

        $response = $this->client->sendMessageBatch(array(
            'QueueUrl' => $this->queueUrl(),
            'Entries' => $messages
        ));

        if ($failed = $response->get('Failed')) {
            $message = "Errors occured while sending the jobs:\n\n";

            $message .= join("\n", array_map(function($error) {
                return sprintf("[%s] %s: %s\n", $error['Id'], $error['Code'], $error['Message']);
            }, $failed));

            throw new \UnexpectedValueException($message);
        }
    }

    protected function queueUrl()
    {
        if (null === $this->queueUrl) {
            $this->queueUrl = $this->client->getQueueUrl(array("QueueName" => $this->queueName))->get('QueueUrl');
        }

        return $this->queueUrl;
    }

    function process(Worker $worker)
    {
    }
}
