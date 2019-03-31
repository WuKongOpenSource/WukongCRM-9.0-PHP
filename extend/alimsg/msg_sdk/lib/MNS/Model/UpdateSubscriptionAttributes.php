<?php
namespace AliyunMNS\Model;

use AliyunMNS\Constants;

class UpdateSubscriptionAttributes
{
    private $subscriptionName;

    private $strategy;

    # may change in AliyunMNS\Topic
    private $topicName;

    public function __construct(
        $subscriptionName = NULL,
        $strategy = NULL)
    {
        $this->subscriptionName = $subscriptionName;

        $this->strategy = $strategy;
    }
    
    public function getStrategy()
    {
        return $this->strategy;
    }

    public function setStrategy($strategy)
    {
        $this->strategy = $strategy;
    }

    public function getTopicName()
    {
        return $this->topicName;
    }

    public function setTopicName($topicName)
    {
        $this->topicName = $topicName;
    }

    public function getSubscriptionName()
    {
        return $this->subscriptionName;
    }

    public function writeXML(\XMLWriter $xmlWriter)
    {
        if ($this->strategy != NULL)
        {
            $xmlWriter->writeElement(Constants::STRATEGY, $this->strategy);
        }
    }
}

?>
