<?php
namespace AliyunMNS\Requests;

use AliyunMNS\Constants;
use AliyunMNS\Requests\BaseRequest;
use AliyunMNS\Model\SendMessageRequestItem;

class BatchSendMessageRequest extends BaseRequest
{
    protected $queueName;
    protected $sendMessageRequestItems;

    // boolean, whether the message body will be encoded in base64
    protected $base64;

    public function __construct(array $sendMessageRequestItems, $base64 = TRUE)
    {
        parent::__construct('post', NULL);

        $this->queueName = NULL;
        $this->sendMessageRequestItems = $sendMessageRequestItems;
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

    public function getSendMessageRequestItems()
    {
        return $this->sendMessageRequestItems;
    }

    public function addSendMessageRequestItem(SendMessageRequestItem $item)
    {
        $this->sendMessageRequestItems[] = $item;
    }

    public function generateBody()
    {
        $xmlWriter = new \XMLWriter;
        $xmlWriter->openMemory();
        $xmlWriter->startDocument("1.0", "UTF-8");
        $xmlWriter->startElementNS(NULL, "Messages", Constants::MNS_XML_NAMESPACE);
        foreach ($this->sendMessageRequestItems as $item)
        {
            $item->writeXML($xmlWriter, $this->base64);
        }
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
