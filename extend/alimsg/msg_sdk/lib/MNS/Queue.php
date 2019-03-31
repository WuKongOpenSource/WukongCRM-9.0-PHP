<?php
namespace AliyunMNS;

use AliyunMNS\Http\HttpClient;
use AliyunMNS\AsyncCallback;
use AliyunMNS\Model\QueueAttributes;
use AliyunMNS\Requests\SetQueueAttributeRequest;
use AliyunMNS\Responses\SetQueueAttributeResponse;
use AliyunMNS\Requests\GetQueueAttributeRequest;
use AliyunMNS\Responses\GetQueueAttributeResponse;
use AliyunMNS\Requests\SendMessageRequest;
use AliyunMNS\Responses\SendMessageResponse;
use AliyunMNS\Requests\PeekMessageRequest;
use AliyunMNS\Responses\PeekMessageResponse;
use AliyunMNS\Requests\ReceiveMessageRequest;
use AliyunMNS\Responses\ReceiveMessageResponse;
use AliyunMNS\Requests\DeleteMessageRequest;
use AliyunMNS\Responses\DeleteMessageResponse;
use AliyunMNS\Requests\ChangeMessageVisibilityRequest;
use AliyunMNS\Responses\ChangeMessageVisibilityResponse;
use AliyunMNS\Requests\BatchSendMessageRequest;
use AliyunMNS\Responses\BatchSendMessageResponse;
use AliyunMNS\Requests\BatchReceiveMessageRequest;
use AliyunMNS\Responses\BatchReceiveMessageResponse;
use AliyunMNS\Requests\BatchPeekMessageRequest;
use AliyunMNS\Responses\BatchPeekMessageResponse;
use AliyunMNS\Requests\BatchDeleteMessageRequest;
use AliyunMNS\Responses\BatchDeleteMessageResponse;

class Queue
{
    private $queueName;
    private $client;

    // boolean, whether the message body will be encoded in base64
    private $base64;

    public function __construct(HttpClient $client, $queueName, $base64 = TRUE)
    {
        $this->queueName = $queueName;
        $this->client = $client;
        $this->base64 = $base64;
    }

    public function setBase64($base64)
    {
        $this->base64 = $base64;
    }

    public function isBase64()
    {
        return ($this->base64 == TRUE);
    }

    public function getQueueName()
    {
        return $this->queueName;
    }

    /**
     * Set the QueueAttributes, detail API sepcs:
     * https://docs.aliyun.com/?spm=#/pub/mns/api_reference/api_spec&queue_operation
     *
     * @param QueueAttributes $attributes: the QueueAttributes to set
     *
     * @return SetQueueAttributeResponse: the response
     *
     * @throws QueueNotExistException if queue does not exist
     * @throws InvalidArgumentException if any argument value is invalid
     * @throws MnsException if any other exception happends
     */
    public function setAttribute(QueueAttributes $attributes)
    {
        $request = new SetQueueAttributeRequest($this->queueName, $attributes);
        $response = new SetQueueAttributeResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function setAttributeAsync(QueueAttributes $attributes,
        AsyncCallback $callback = NULL)
    {
        $request = new SetQueueAttributeRequest($this->queueName, $attributes);
        $response = new SetQueueAttributeResponse();
        return $this->client->sendRequestAsync($request, $response, $callback);
    }

    /**
     * Get the QueueAttributes, detail API sepcs:
     * https://docs.aliyun.com/?spm=#/pub/mns/api_reference/api_spec&queue_operation
     *
     * @return GetQueueAttributeResponse: containing the attributes
     *
     * @throws QueueNotExistException if queue does not exist
     * @throws MnsException if any other exception happends
     */
    public function getAttribute()
    {
        $request = new GetQueueAttributeRequest($this->queueName);
        $response = new GetQueueAttributeResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function getAttributeAsync(AsyncCallback $callback = NULL)
    {
        $request = new GetQueueAttributeRequest($this->queueName);
        $response = new GetQueueAttributeResponse();
        return $this->client->sendRequestAsync($request, $response, $callback);
    }

    /**
     * SendMessage, the messageBody will be automatically encoded in base64
     *     If you do not need the message body to be encoded in Base64,
     *     please specify the $base64 = FALSE in Queue
     *
     * detail API sepcs:
     * https://docs.aliyun.com/?spm=#/pub/mns/api_reference/api_spec&message_operation
     *
     * @param SendMessageRequest: containing the message body and properties
     *
     * @return SendMessageResponse: containing the messageId and bodyMD5
     *
     * @throws QueueNotExistException if queue does not exist
     * @throws InvalidArgumentException if any argument value is invalid
     * @throws MalformedXMLException if any error in xml
     * @throws MnsException if any other exception happends
     */
    public function sendMessage(SendMessageRequest $request)
    {
        $request->setQueueName($this->queueName);
        $request->setBase64($this->base64);
        $response = new SendMessageResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function sendMessageAsync(SendMessageRequest $request,
        AsyncCallback $callback = NULL)
    {
        $request->setQueueName($this->queueName);
        $request->setBase64($this->base64);
        $response = new SendMessageResponse();
        return $this->client->sendRequestAsync($request, $response, $callback);
    }

    /**
     * PeekMessage, the messageBody will be automatically decoded as base64 if the $base64 in Queue is TRUE
     *
     * detail API sepcs:
     * https://docs.aliyun.com/?spm=#/pub/mns/api_reference/api_spec&message_operation
     *
     * @return PeekMessageResponse: containing the messageBody and properties
     *
     * @throws QueueNotExistException if queue does not exist
     * @throws MessageNotExistException if no message exists in the queue
     * @throws MnsException if any other exception happends
     */
    public function peekMessage()
    {
        $request = new PeekMessageRequest($this->queueName);
        $response = new PeekMessageResponse($this->base64);
        return $this->client->sendRequest($request, $response);
    }

    public function peekMessageAsync(AsyncCallback $callback = NULL)
    {
        $request = new PeekMessageRequest($this->queueName);
        $response = new PeekMessageResponse($this->base64);
        return $this->client->sendRequestAsync($request, $response, $callback);
    }

    /**
     * ReceiveMessage, the messageBody will be automatically decoded as base64 if $base64 = TRUE in Queue
     * detail API sepcs:
     * https://docs.aliyun.com/?spm=#/pub/mns/api_reference/api_spec&message_operation
     *
     * @param waitSeconds: the long polling waitseconds
     *
     * @return ReceiveMessageResponse: containing the messageBody and properties
     *          the response is same as PeekMessageResponse,
     *          except that the receiptHandle is also returned in receiveMessage
     *
     * @throws QueueNotExistException if queue does not exist
     * @throws MessageNotExistException if no message exists in the queue
     * @throws MnsException if any other exception happends
     */
    public function receiveMessage($waitSeconds = NULL)
    {
        $request = new ReceiveMessageRequest($this->queueName, $waitSeconds);
        $response = new ReceiveMessageResponse($this->base64);
        return $this->client->sendRequest($request, $response);
    }

    public function receiveMessageAsync(AsyncCallback $callback = NULL)
    {
        $request = new ReceiveMessageRequest($this->queueName);
        $response = new ReceiveMessageResponse($this->base64);
        return $this->client->sendRequestAsync($request, $response, $callback);
    }

    /**
     * DeleteMessage
     * detail API sepcs:
     * https://docs.aliyun.com/?spm=#/pub/mns/api_reference/api_spec&message_operation
     *
     * @param $receiptHandle: the receiptHandle returned from receiveMessage
     *
     * @return ReceiveMessageResponse
     *
     * @throws QueueNotExistException if queue does not exist
     * @throws InvalidArgumentException if the argument is invalid
     * @throws ReceiptHandleErrorException if the $receiptHandle is invalid
     * @throws MnsException if any other exception happends
     */
    public function deleteMessage($receiptHandle)
    {
        $request = new DeleteMessageRequest($this->queueName, $receiptHandle);
        $response = new DeleteMessageResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function deleteMessageAsync($receiptHandle,
        AsyncCallback $callback = NULL)
    {
        $request = new DeleteMessageRequest($this->queueName, $receiptHandle);
        $response = new DeleteMessageResponse();
        return $this->client->sendRequestAsync($request, $response, $callback);
    }

    /**
     * ChangeMessageVisibility, set the nextVisibleTime for the message
     * detail API sepcs:
     * https://docs.aliyun.com/?spm=#/pub/mns/api_reference/api_spec&message_operation
     *
     * @param $receiptHandle: the receiptHandle returned from receiveMessage
     *
     * @return ChangeMessageVisibilityResponse
     *
     * @throws QueueNotExistException if queue does not exist
     * @throws MessageNotExistException if the message does not exist
     * @throws InvalidArgumentException if the argument is invalid
     * @throws ReceiptHandleErrorException if the $receiptHandle is invalid
     * @throws MnsException if any other exception happends
     */
    public function changeMessageVisibility($receiptHandle, $visibilityTimeout)
    {
        $request = new ChangeMessageVisibilityRequest($this->queueName, $receiptHandle, $visibilityTimeout);
        $response = new ChangeMessageVisibilityResponse();
        return $this->client->sendRequest($request, $response);
    }

    /**
     * BatchSendMessage, message body will be automatically encoded in base64
     *     If you do not need the message body to be encoded in Base64,
     *     please specify the $base64 = FALSE in Queue
     *
     * detail API sepcs:
     * https://docs.aliyun.com/?spm=#/pub/mns/api_reference/api_spec&message_operation
     *
     * @param BatchSendMessageRequest:
     *            the requests containing an array of SendMessageRequestItems
     *
     * @return BatchSendMessageResponse
     *
     * @throws QueueNotExistException if queue does not exist
     * @throws MalformedXMLException if any error in the xml
     * @throws InvalidArgumentException if the argument is invalid
     * @throws BatchSendFailException if some messages are not sent
     * @throws MnsException if any other exception happends
     */
    public function batchSendMessage(BatchSendMessageRequest $request)
    {
        $request->setQueueName($this->queueName);
        $request->setBase64($this->base64);
        $response = new BatchSendMessageResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function batchSendMessageAsync(BatchSendMessageRequest $request,
        AsyncCallback $callback = NULL)
    {
        $request->setQueueName($this->queueName);
        $request->setBase64($this->base64);
        $response = new BatchSendMessageResponse();
        return $this->client->sendRequestAsync($request, $response, $callback);
    }

    /**
     * BatchReceiveMessage, message body will be automatically decoded as base64 if $base64 = TRUE in Queue
     *
     * detail API sepcs:
     * https://docs.aliyun.com/?spm=#/pub/mns/api_reference/api_spec&message_operation
     *
     * @param BatchReceiveMessageRequest:
     *            containing numOfMessages and waitSeconds
     *
     * @return BatchReceiveMessageResponse:
     *            the received messages
     *
     * @throws QueueNotExistException if queue does not exist
     * @throws MessageNotExistException if no message exists
     * @throws MnsException if any other exception happends
     */
    public function batchReceiveMessage(BatchReceiveMessageRequest $request)
    {
        $request->setQueueName($this->queueName);
        $response = new BatchReceiveMessageResponse($this->base64);
        return $this->client->sendRequest($request, $response);
    }

    public function batchReceiveMessageAsync(BatchReceiveMessageRequest $request, AsyncCallback $callback = NULL)
    {
        $request->setQueueName($this->queueName);
        $response = new BatchReceiveMessageResponse($this->base64);
        return $this->client->sendRequestAsync($request, $response, $callback);
    }

    /**
     * BatchPeekMessage, message body will be automatically decoded as base64 is $base64 = TRUE in Queue
     *
     * detail API sepcs:
     * https://docs.aliyun.com/?spm=#/pub/mns/api_reference/api_spec&message_operation
     *
     * @param BatchPeekMessageRequest:
     *            containing numOfMessages and waitSeconds
     *
     * @return BatchPeekMessageResponse:
     *            the received messages
     *
     * @throws QueueNotExistException if queue does not exist
     * @throws MessageNotExistException if no message exists
     * @throws MnsException if any other exception happends
     */
    public function batchPeekMessage($numOfMessages)
    {
        $request = new BatchPeekMessageRequest($this->queueName, $numOfMessages);
        $response = new BatchPeekMessageResponse($this->base64);
        return $this->client->sendRequest($request, $response);
    }

    public function batchPeekMessageAsync($numOfMessages, AsyncCallback $callback = NULL)
    {
        $request = new BatchPeekMessageRequest($this->queueName, $numOfMessages);
        $response = new BatchPeekMessageResponse($this->base64);
        return $this->client->sendRequestAsync($request, $response, $callback);
    }

    /**
     * BatchDeleteMessage
     * detail API sepcs:
     * https://docs.aliyun.com/?spm=#/pub/mns/api_reference/api_spec&message_operation
     *
     * @param $receiptHandles:
     *            array of $receiptHandle, which is got from receiveMessage
     *
     * @return BatchDeleteMessageResponse
     *
     * @throws QueueNotExistException if queue does not exist
     * @throws ReceiptHandleErrorException if the receiptHandle is invalid
     * @throws InvalidArgumentException if the argument is invalid
     * @throws BatchDeleteFailException if any message not deleted
     * @throws MnsException if any other exception happends
     */
    public function batchDeleteMessage($receiptHandles)
    {
        $request = new BatchDeleteMessageRequest($this->queueName, $receiptHandles);
        $response = new BatchDeleteMessageResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function batchDeleteMessageAsync($receiptHandles, AsyncCallback $callback = NULL)
    {
        $request = new BatchDeleteMessageRequest($this->queueName, $receiptHandles);
        $response = new BatchDeleteMessageResponse();
        return $this->client->sendRequestAsync($request, $response, $callback);
    }
}

?>
