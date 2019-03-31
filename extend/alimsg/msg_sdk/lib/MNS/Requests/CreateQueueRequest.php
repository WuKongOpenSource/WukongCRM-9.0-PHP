<?php
namespace AliyunMNS\Requests;

use AliyunMNS\Constants;
use AliyunMNS\Requests\BaseRequest;
use AliyunMNS\Model\QueueAttributes;

class CreateQueueRequest extends BaseRequest
{
    private $queueName;
    private $attributes;

    public function __construct($queueName, QueueAttributes $attributes = NULL)
    {
        parent::__construct('put', 'queues/' . $queueName);

        if ($attributes == NULL)
        {
            $attributes = new QueueAttributes;
        }

        $this->queueName = $queueName;
        $this->attributes = $attributes;
    }

    public function getQueueName()
    {
        return $this->queueName;
    }

    public function getQueueAttributes()
    {
        return $this->attributes;
    }

    public function generateBody()
    {
        $xmlWriter = new \XMLWriter;
        $xmlWriter->openMemory();
        $xmlWriter->startDocument("1.0", "UTF-8");
        $xmlWriter->startElementNS(NULL, "Queue", Constants::MNS_XML_NAMESPACE);
        $this->attributes->writeXML($xmlWriter);
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
