<?php
namespace AliyunMNS\Requests;

use AliyunMNS\Requests\BaseRequest;

class UnsubscribeRequest extends BaseRequest
{
    private $topicName;
    private $subscriptionName;

    public function __construct($topicName, $subscriptionName)
    {
        parent::__construct('delete', 'topics/' . $topicName . '/subscriptions/' . $subscriptionName);
        $this->topicName = $topicName;
        $this->subscriptionName = $subscriptionName;
    }

    public function getTopicName()
    {
        return $this->topicName;
    }

    public function getSubscriptionName()
    {
        return $this->subscriptionName;
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
