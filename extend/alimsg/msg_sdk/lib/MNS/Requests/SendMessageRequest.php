<?php
namespace AliyunMNS\Requests;

use AliyunMNS\Constants;
use AliyunMNS\Requests\BaseRequest;
use AliyunMNS\Model\QueueAttributes;
use AliyunMNS\Traits\MessagePropertiesForSend;

class SendMessageRequest extends BaseRequest
{
    use MessagePropertiesForSend;

    private $queueName;

    // boolean, whether the message body will be encoded in base64
    private $base64;

    public function __construct($messageBody, $delaySeconds = NULL, $priority = NULL, $base64 = TRUE)
    {
        parent::__construct('post', NULL);

        $this->queueName = NULL;
        $this->messageBody = $messageBody;
        $this->delaySeconds = $delaySeconds;
        $this->priority = $priority;
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

    public function setQueueName($queueName)
    {
        $this->queueName = $queueName;
        $this->resourcePath = 'queues/' . $queueName . '/messages';
    }

    public function getQueueName()
    {
        return $this->queueName;
    }

    public function generateBody()
    {
        $xmlWriter = new \XMLWriter;
        $xmlWriter->openMemory();
        $xmlWriter->startDocument("1.0", "UTF-8");
        $xmlWriter->startElementNS(NULL, "Message", Constants::MNS_XML_NAMESPACE);
        $this->writeMessagePropertiesForSendXML($xmlWriter, $this->base64);
        $xmlWriter->endElement();
        $xmlWriter->endDocument();
        return $xmlWriter->outputMemory();
    }

    public function generateQueryString()
    {
        return NULL;
    }
}
?>
