<?php
namespace AliyunMNS\Requests;

use AliyunMNS\Constants;
use AliyunMNS\Requests\BaseRequest;

class BatchDeleteMessageRequest extends BaseRequest
{
    private $queueName;
    private $receiptHandles;

    public function __construct($queueName, array $receiptHandles)
    {
        parent::__construct('delete', 'queues/' . $queueName . '/messages');

        $this->queueName = $queueName;
        $this->receiptHandles = $receiptHandles;
    }

    public function getQueueName()
    {
        return $this->queueName;
    }

    public function getReceiptHandles()
    {
        return $this->receiptHandles;
    }

    public function generateBody()
    {
        $xmlWriter = new \XMLWriter;
        $xmlWriter->openMemory();
        $xmlWriter->startDocument("1.0", "UTF-8");
        $xmlWriter->startElementNS(NULL, Constants::RECEIPT_HANDLES, Constants::MNS_XML_NAMESPACE);
        foreach ($this->receiptHandles as $receiptHandle)
        {
            $xmlWriter->writeElement(Constants::RECEIPT_HANDLE, $receiptHandle);
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
