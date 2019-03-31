<?php
namespace AliyunMNS;

use AliyunMNS\Queue;
use AliyunMNS\Config;
use AliyunMNS\Http\HttpClient;
use AliyunMNS\AsyncCallback;
use AliyunMNS\Model\AccountAttributes;
use AliyunMNS\Requests\CreateQueueRequest;
use AliyunMNS\Responses\CreateQueueResponse;
use AliyunMNS\Requests\ListQueueRequest;
use AliyunMNS\Responses\ListQueueResponse;
use AliyunMNS\Requests\DeleteQueueRequest;
use AliyunMNS\Responses\DeleteQueueResponse;
use AliyunMNS\Requests\CreateTopicRequest;
use AliyunMNS\Responses\CreateTopicResponse;
use AliyunMNS\Requests\DeleteTopicRequest;
use AliyunMNS\Responses\DeleteTopicResponse;
use AliyunMNS\Requests\ListTopicRequest;
use AliyunMNS\Responses\ListTopicResponse;
use AliyunMNS\Requests\GetAccountAttributesRequest;
use AliyunMNS\Responses\GetAccountAttributesResponse;
use AliyunMNS\Requests\SetAccountAttributesRequest;
use AliyunMNS\Responses\SetAccountAttributesResponse;

/**
 * Please refer to
 * https://docs.aliyun.com/?spm=#/pub/mns/api_reference/api_spec&queue_operation
 * for more details
 */
class Client
{
    private $client;

    /**
     * Please refer to http://www.aliyun.com/product/mns for more details
     *
     * @param endPoint: the host url
     *               could be "http://$accountId.mns.cn-hangzhou.aliyuncs.com"
     *               accountId could be found in aliyun.com
     * @param accessId: accessId from aliyun.com
     * @param accessKey: accessKey from aliyun.com
     * @param securityToken: securityToken from aliyun.com
     * @param config: necessary configs
     */
    public function __construct($endPoint, $accessId,
        $accessKey, $securityToken = NULL, Config $config = NULL)
    {
        $this->client = new HttpClient($endPoint, $accessId,
            $accessKey, $securityToken, $config);
    }

    /**
     * Returns a queue reference for operating on the queue
     * this function does not create the queue automatically.
     *
     * @param string $queueName:  the queue name
     * @param bool $base64: whether the message in queue will be base64 encoded
     *
     * @return Queue $queue: the Queue instance
     */
    public function getQueueRef($queueName, $base64 = TRUE)
    {
        return new Queue($this->client, $queueName, $base64);
    }

    /**
     * Create Queue and Returns the Queue reference
     *
     * @param CreateQueueRequest $request:  the QueueName and QueueAttributes
     *
     * @return CreateQueueResponse $response: the CreateQueueResponse
     *
     * @throws QueueAlreadyExistException if queue already exists
     * @throws InvalidArgumentException if any argument value is invalid
     * @throws MnsException if any other exception happends
     */
    public function createQueue(CreateQueueRequest $request)
    {
        $response = new CreateQueueResponse($request->getQueueName());
        return $this->client->sendRequest($request, $response);
    }

    /**
     * Create Queue and Returns the Queue reference
     * The request will not be sent until calling MnsPromise->wait();
     *
     * @param CreateQueueRequest $request:  the QueueName and QueueAttributes
     * @param AsyncCallback $callback:  the Callback when the request finishes
     *
     * @return MnsPromise $promise: the MnsPromise instance
     *
     * @throws MnsException if any exception happends
     */
    public function createQueueAsync(CreateQueueRequest $request,
        AsyncCallback $callback = NULL)
    {
        $response = new CreateQueueResponse($request->getQueueName());
        return $this->client->sendRequestAsync($request, $response, $callback);
    }

    /**
     * Query the queues created by current account
     *
     * @param ListQueueRequest $request: define filters for quering queues
     *
     * @return ListQueueResponse: the response containing queueNames
     */
    public function listQueue(ListQueueRequest $request)
    {
        $response = new ListQueueResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function listQueueAsync(ListQueueRequest $request,
        AsyncCallback $callback = NULL)
    {
        $response = new ListQueueResponse();
        return $this->client->sendRequestAsync($request, $response, $callback);
    }

    /**
     * Delete the specified queue
     * the request will succeed even when the queue does not exist
     *
     * @param $queueName: the queueName
     *
     * @return DeleteQueueResponse
     */
    public function deleteQueue($queueName)
    {
        $request = new DeleteQueueRequest($queueName);
        $response = new DeleteQueueResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function deleteQueueAsync($queueName,
        AsyncCallback $callback = NULL)
    {
        $request = new DeleteQueueRequest($queueName);
        $response = new DeleteQueueResponse();
        return $this->client->sendRequestAsync($request, $response, $callback);
    }

    // API for Topic
    /**
     * Returns a topic reference for operating on the topic
     * this function does not create the topic automatically.
     *
     * @param string $topicName:  the topic name
     *
     * @return Topic $topic: the Topic instance
     */
    public function getTopicRef($topicName)
    {
        return new Topic($this->client, $topicName);
    }

    /**
     * Create Topic and Returns the Topic reference
     *
     * @param CreateTopicRequest $request:  the TopicName and TopicAttributes
     *
     * @return CreateTopicResponse $response: the CreateTopicResponse
     *
     * @throws TopicAlreadyExistException if topic already exists
     * @throws InvalidArgumentException if any argument value is invalid
     * @throws MnsException if any other exception happends
     */
    public function createTopic(CreateTopicRequest $request)
    {
        $response = new CreateTopicResponse($request->getTopicName());
        return $this->client->sendRequest($request, $response);
    }

    /**
     * Delete the specified topic
     * the request will succeed even when the topic does not exist
     *
     * @param $topicName: the topicName
     *
     * @return DeleteTopicResponse
     */
    public function deleteTopic($topicName)
    {
        $request = new DeleteTopicRequest($topicName);
        $response = new DeleteTopicResponse();
        return $this->client->sendRequest($request, $response);
    }

    /**
     * Query the topics created by current account
     *
     * @param ListTopicRequest $request: define filters for quering topics
     *
     * @return ListTopicResponse: the response containing topicNames
     */
    public function listTopic(ListTopicRequest $request)
    {
        $response = new ListTopicResponse();
        return $this->client->sendRequest($request, $response);
    }

    /**
     * Query the AccountAttributes
     *
     * @return GetAccountAttributesResponse: the response containing topicNames
     * @throws MnsException if any exception happends
     */
    public function getAccountAttributes()
    {
        $request = new GetAccountAttributesRequest();
        $response = new GetAccountAttributesResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function getAccountAttributesAsync(AsyncCallback $callback = NULL)
    {
        $request = new GetAccountAttributesRequest();
        $response = new GetAccountAttributesResponse();
        return $this->client->sendRequestAsync($request, $response, $callback);
    }

    /**
     * Set the AccountAttributes
     *
     * @param AccountAttributes $attributes: the AccountAttributes to set
     *
     * @return SetAccountAttributesResponse: the response
     *
     * @throws MnsException if any exception happends
     */
    public function setAccountAttributes(AccountAttributes $attributes)
    {
        $request = new SetAccountAttributesRequest($attributes);
        $response = new SetAccountAttributesResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function setAccountAttributesAsync(AccountAttributes $attributes,
        AsyncCallback $callback = NULL)
    {
        $request = new SetAccountAttributesRequest($attributes);
        $response = new SetAccountAttributesResponse();
        return $this->client->sendRequestAsync($request, $response, $callback);
    }
}

?>
