<?php
namespace AliyunMNS\Requests;

use AliyunMNS\Constants;
use AliyunMNS\Requests\BaseRequest;

class PeekMessageRequest extends BaseRequest
{
    private $queueName;

    public function __construct($queueName)
    {
        parent::__construct('get', 'queues/' . $queueName . '/messages?peekonly=true');

        $this->queueName = $queueName;
    }

    public function getQueueName()
    {
        return $this->queueName;
    }

    public function generateBody()
    {
        return NULL;
    }

    public function generateQueryString()
    {
        return NULL;
    }
}
?>
