<?php
namespace AliyunMNS;

use AliyunMNS\Http\HttpClient;
use AliyunMNS\AsyncCallback;
use AliyunMNS\Model\TopicAttributes;
use AliyunMNS\Model\SubscriptionAttributes;
use AliyunMNS\Model\UpdateSubscriptionAttributes;
use AliyunMNS\Requests\SetTopicAttributeRequest;
use AliyunMNS\Responses\SetTopicAttributeResponse;
use AliyunMNS\Requests\GetTopicAttributeRequest;
use AliyunMNS\Responses\GetTopicAttributeResponse;
use AliyunMNS\Requests\PublishMessageRequest;
use AliyunMNS\Responses\PublishMessageResponse;
use AliyunMNS\Requests\SubscribeRequest;
use AliyunMNS\Responses\SubscribeResponse;
use AliyunMNS\Requests\UnsubscribeRequest;
use AliyunMNS\Responses\UnsubscribeResponse;
use AliyunMNS\Requests\GetSubscriptionAttributeRequest;
use AliyunMNS\Responses\GetSubscriptionAttributeResponse;
use AliyunMNS\Requests\SetSubscriptionAttributeRequest;
use AliyunMNS\Responses\SetSubscriptionAttributeResponse;
use AliyunMNS\Requests\ListSubscriptionRequest;
use AliyunMNS\Responses\ListSubscriptionResponse;

class Topic
{
    private $topicName;
    private $client;

    public function __construct(HttpClient $client, $topicName)
    {
        $this->client = $client;
        $this->topicName = $topicName;
    }

    public function getTopicName()
    {
        return $this->topicName;
    }

    public function setAttribute(TopicAttributes $attributes)
    {
        $request = new SetTopicAttributeRequest($this->topicName, $attributes);
        $response = new SetTopicAttributeResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function getAttribute()
    {
        $request = new GetTopicAttributeRequest($this->topicName);
        $response = new GetTopicAttributeResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function generateQueueEndpoint($queueName)
    {
        return "acs:mns:" . $this->client->getRegion() . ":" . $this->client->getAccountId() . ":queues/" . $queueName;
    }

    public function generateMailEndpoint($mailAddress)
    {
        return "mail:directmail:" . $mailAddress;
    }

    public function generateSmsEndpoint($phone = null)
    {
        if ($phone)
        {
            return "sms:directsms:" . $phone;
        }
        else
        {
            return "sms:directsms:anonymous";
        }
    }

    public function generateBatchSmsEndpoint()
    {
        return "sms:directsms:anonymous";
    }

    public function publishMessage(PublishMessageRequest $request)
    {
        $request->setTopicName($this->topicName);
        $response = new PublishMessageResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function subscribe(SubscriptionAttributes $attributes)
    {
        $attributes->setTopicName($this->topicName);
        $request = new SubscribeRequest($attributes);
        $response = new SubscribeResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function unsubscribe($subscriptionName)
    {
        $request = new UnsubscribeRequest($this->topicName, $subscriptionName);
        $response = new UnsubscribeResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function getSubscriptionAttribute($subscriptionName)
    {
        $request = new GetSubscriptionAttributeRequest($this->topicName, $subscriptionName);
        $response = new GetSubscriptionAttributeResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function setSubscriptionAttribute(UpdateSubscriptionAttributes $attributes)
    {
        $attributes->setTopicName($this->topicName);
        $request = new SetSubscriptionAttributeRequest($attributes);
        $response = new SetSubscriptionAttributeResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function listSubscription($retNum = NULL, $prefix = NULL, $marker = NULL)
    {
        $request = new ListSubscriptionRequest($this->topicName, $retNum, $prefix, $marker);
        $response = new ListSubscriptionResponse();
        return $this->client->sendRequest($request, $response);
    }
}

?>
