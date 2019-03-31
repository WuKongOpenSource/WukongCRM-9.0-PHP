<?php
namespace AliyunMNS\Requests;

use AliyunMNS\Constants;
use AliyunMNS\Requests\BaseRequest;

class ReceiveMessageRequest extends BaseRequest
{
    private $queueName;
    private $waitSeconds;

    public function __construct($queueName, $waitSeconds = NULL)
    {
        parent::__construct('get', 'queues/' . $queueName . '/messages');

        $this->queueName = $queueName;
        $this->waitSeconds = $waitSeconds;
    }

    public function getQueueName()
    {
        return $this->queueName;
    }

    public function getWaitSeconds()
    {
        return $this->waitSeconds;
    }

    public function generateBody()
    {
        return NULL;
    }

    public function generateQueryString()
    {
        if ($this->waitSeconds != NULL)
        {
            return http_build_query(array("waitseconds" => $this->waitSeconds));
        }
    }
}
?>
