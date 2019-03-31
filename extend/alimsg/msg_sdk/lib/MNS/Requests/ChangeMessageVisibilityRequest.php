<?php
namespace AliyunMNS\Requests;

use AliyunMNS\Constants;
use AliyunMNS\Requests\BaseRequest;

class ChangeMessageVisibilityRequest extends BaseRequest
{
    private $queueName;
    private $receiptHandle;
    private $visibilityTimeout;

    public function __construct($queueName, $receiptHandle, $visibilityTimeout)
    {
        parent::__construct('put', 'queues/' . $queueName . '/messages');

        $this->queueName = $queueName;
        $this->receiptHandle = $receiptHandle;
        $this->visibilityTimeout = $visibilityTimeout;
    }

    public function getQueueName()
    {
        return $this->queueName;
    }

    public function getReceiptHandle()
    {
        return $this->receiptHandle;
    }

    public function getVisibilityTimeout()
    {
        return $this->visibilityTimeout;
    }

    public function generateBody()
    {
        return NULL;
    }

    public function generateQueryString()
    {
        return http_build_query(array("receiptHandle" => $this->receiptHandle, "visibilityTimeout" => $this->visibilityTimeout));
    }
}
?>
