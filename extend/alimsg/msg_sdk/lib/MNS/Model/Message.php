<?php
namespace AliyunMNS\Model;

use AliyunMNS\Constants;
use AliyunMNS\Traits\MessagePropertiesForReceive;

class Message
{
    use MessagePropertiesForReceive;

    public function __construct($messageId, $messageBodyMD5, $messageBody, $enqueueTime, $nextVisibleTime, $firstDequeueTime, $dequeueCount, $priority, $receiptHandle)
    {
        $this->messageId = $messageId;
        $this->messageBodyMD5 = $messageBodyMD5;
        $this->messageBody = $messageBody;
        $this->enqueueTime = $enqueueTime;
        $this->nextVisibleTime = $nextVisibleTime;
        $this->firstDequeueTime = $firstDequeueTime;
        $this->dequeueCount = $dequeueCount;
        $this->priority = $priority;
        $this->receiptHandle = $receiptHandle;
    }

    static public function fromXML(\XMLReader $xmlReader, $base64)
    {
        $messageId = NULL;
        $messageBodyMD5 = NULL;
        $messageBody = NULL;
        $enqueueTime = NULL;
        $nextVisibleTime = NULL;
        $firstDequeueTime = NULL;
        $dequeueCount = NULL;
        $priority = NULL;
        $receiptHandle = NULL;

        while ($xmlReader->read())
        {
            switch ($xmlReader->nodeType)
            {
            case \XMLReader::ELEMENT:
                switch ($xmlReader->name) {
                case Constants::MESSAGE_ID:
                    $xmlReader->read();
                    if ($xmlReader->nodeType == \XMLReader::TEXT)
                    {
                        $messageId = $xmlReader->value;
                    }
                    break;
                case Constants::MESSAGE_BODY_MD5:
                    $xmlReader->read();
                    if ($xmlReader->nodeType == \XMLReader::TEXT)
                    {
                        $messageBodyMD5 = $xmlReader->value;
                    }
                    break;
                case Constants::MESSAGE_BODY:
                    $xmlReader->read();
                    if ($xmlReader->nodeType == \XMLReader::TEXT)
                    {
                        if ($base64 == TRUE) {
                            $messageBody = base64_decode($xmlReader->value);
                        } else {
                            $messageBody = $xmlReader->value;
                        }
                    }
                    break;
                case Constants::ENQUEUE_TIME:
                    $xmlReader->read();
                    if ($xmlReader->nodeType == \XMLReader::TEXT)
                    {
                        $enqueueTime = $xmlReader->value;
                    }
                    break;
                case Constants::NEXT_VISIBLE_TIME:
                    $xmlReader->read();
                    if ($xmlReader->nodeType == \XMLReader::TEXT)
                    {
                        $nextVisibleTime = $xmlReader->value;
                    }
                    break;
                case Constants::FIRST_DEQUEUE_TIME:
                    $xmlReader->read();
                    if ($xmlReader->nodeType == \XMLReader::TEXT)
                    {
                        $firstDequeueTime = $xmlReader->value;
                    }
                    break;
                case Constants::DEQUEUE_COUNT:
                    $xmlReader->read();
                    if ($xmlReader->nodeType == \XMLReader::TEXT)
                    {
                        $dequeueCount = $xmlReader->value;
                    }
                    break;
                case Constants::PRIORITY:
                    $xmlReader->read();
                    if ($xmlReader->nodeType == \XMLReader::TEXT)
                    {
                        $priority = $xmlReader->value;
                    }
                    break;
                case Constants::RECEIPT_HANDLE:
                    $xmlReader->read();
                    if ($xmlReader->nodeType == \XMLReader::TEXT)
                    {
                        $receiptHandle = $xmlReader->value;
                    }
                    break;
                }
                break;
            case \XMLReader::END_ELEMENT:
                if ($xmlReader->name == 'Message')
                {
                    $message = new Message(
                        $messageId,
                        $messageBodyMD5,
                        $messageBody,
                        $enqueueTime,
                        $nextVisibleTime,
                        $firstDequeueTime,
                        $dequeueCount,
                        $priority,
                        $receiptHandle);
                    return $message;
                }
                break;
            }
        }

        $message = new Message(
            $messageId,
            $messageBodyMD5,
            $messageBody,
            $enqueueTime,
            $nextVisibleTime,
            $firstDequeueTime,
            $dequeueCount,
            $priority,
            $receiptHandle);

        return $message;
    }
}

?>
