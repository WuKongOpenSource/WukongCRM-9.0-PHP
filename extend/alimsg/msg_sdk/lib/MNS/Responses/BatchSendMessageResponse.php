<?php
namespace AliyunMNS\Responses;

use AliyunMNS\Constants;
use AliyunMNS\Exception\MnsException;
use AliyunMNS\Exception\BatchSendFailException;
use AliyunMNS\Exception\QueueNotExistException;
use AliyunMNS\Exception\InvalidArgumentException;
use AliyunMNS\Exception\MalformedXMLException;
use AliyunMNS\Responses\BaseResponse;
use AliyunMNS\Common\XMLParser;
use AliyunMNS\Model\SendMessageResponseItem;
use AliyunMNS\Traits\MessageIdAndMD5;

class BatchSendMessageResponse extends BaseResponse
{
    protected $sendMessageResponseItems;

    public function __construct()
    {
        $this->sendMessageResponseItems = array();
    }

    public function getSendMessageResponseItems()
    {
        return $this->sendMessageResponseItems;
    }

    public function parseResponse($statusCode, $content)
    {
        $this->statusCode = $statusCode;
        if ($statusCode == 201) {
            $this->succeed = TRUE;
        } else {
            $this->parseErrorResponse($statusCode, $content);
        }

        $xmlReader = $this->loadXmlContent($content);

        try {
            while ($xmlReader->read())
            {
                if ($xmlReader->nodeType == \XMLReader::ELEMENT && $xmlReader->name == 'Message') {
                    $this->sendMessageResponseItems[] = SendMessageResponseItem::fromXML($xmlReader);
                }
            }
        } catch (\Exception $e) {
            throw new MnsException($statusCode, $e->getMessage(), $e);
        } catch (\Throwable $t) {
            throw new MnsException($statusCode, $t->getMessage());
        }
    }

    public function parseErrorResponse($statusCode, $content, MnsException $exception = NULL)
    {
        $this->succeed = FALSE;
        $xmlReader = $this->loadXmlContent($content);

        try {
            while ($xmlReader->read())
            {
                if ($xmlReader->nodeType == \XMLReader::ELEMENT) {
                    switch ($xmlReader->name) {
                    case Constants::ERROR:
                        $this->parseNormalErrorResponse($xmlReader);
                        break;
                    default: // case Constants::Messages
                        $this->parseBatchSendErrorResponse($xmlReader);
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            if ($exception != NULL) {
                throw $exception;
            } elseif($e instanceof MnsException) {
                throw $e;
            } else {
                throw new MnsException($statusCode, $e->getMessage());
            }
        } catch (\Throwable $t) {
            throw new MnsException($statusCode, $t->getMessage());
        }
    }

    private function parseBatchSendErrorResponse($xmlReader)
    {
        $ex = new BatchSendFailException($this->statusCode, "BatchSendMessage Failed For Some Messages");
        while ($xmlReader->read())
        {
            if ($xmlReader->nodeType == \XMLReader::ELEMENT && $xmlReader->name == 'Message') {
                $ex->addSendMessageResponseItem( SendMessageResponseItem::fromXML($xmlReader));
            }
        }
        throw $ex;
    }

    private function parseNormalErrorResponse($xmlReader)
    {
        $result = XMLParser::parseNormalError($xmlReader);
        if ($result['Code'] == Constants::QUEUE_NOT_EXIST)
        {
            throw new QueueNotExistException($statusCode, $result['Message'], $exception, $result['Code'], $result['RequestId'], $result['HostId']);
        }
        if ($result['Code'] == Constants::INVALID_ARGUMENT)
        {
            throw new InvalidArgumentException($statusCode, $result['Message'], $exception, $result['Code'], $result['RequestId'], $result['HostId']);
        }
        if ($result['Code'] == Constants::MALFORMED_XML)
        {
            throw new MalformedXMLException($statusCode, $result['Message'], $exception, $result['Code'], $result['RequestId'], $result['HostId']);
        }
        throw new MnsException($statusCode, $result['Message'], $exception, $result['Code'], $result['RequestId'], $result['HostId']);
    }
}

?>
