<?php
namespace AliyunMNS\Requests;

use AliyunMNS\Constants;
use AliyunMNS\Requests\BaseRequest;

class DeleteMessageRequest extends BaseRequest
{
    private $queueName;
    private $receiptHandle;

    public function __construct($queueName, $receiptHandle)
    {
        parent::__construct('delete', 'queues/' . $queueName . '/messages');

        $this->queueName = $queueName;
        $this->receiptHandle = $receiptHandle;
    }

    public function getQueueName()
    {
        return $this->queueName;
    }

    public function getReceiptHandle()
    {
        return $this->receiptHandle;
    }

    public function generateBody()
    {
        return NULL;
    }

    public function generateQueryString()
    {
        return http_build_query(array("ReceiptHandle" => $this->receiptHandle));
    }
}
?>
