<?php
namespace AliyunMNS\Model;

use AliyunMNS\Constants;
use AliyunMNS\Traits\MessageIdAndMD5;

/**
 * if isSucceed() == TRUE, the messageId and messageBodyMD5 are returned.
 * otherwise, the errorCode and errorMessage are returned.
 */
class SendMessageResponseItem
{
    use MessageIdAndMD5;

    protected $isSucceed;
    protected $errorCode;
    protected $errorMessage;

    public function __construct($isSucceed, $param1, $param2)
    {
        $this->isSucceed = $isSucceed;
        if ($isSucceed == TRUE)
        {
            $this->messageId = $param1;
            $this->messageBodyMD5 = $param2;
        }
        else
        {
            $this->errorCode = $param1;
            $this->errorMessage = $param2;
        }
    }

    public function isSucceed()
    {
        return $this->isSucceed;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    static public function fromXML($xmlReader)
    {
        $messageId = NULL;
        $messageBodyMD5 = NULL;
        $errorCode = NULL;
        $errorMessage = NULL;

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
                case Constants::ERROR_CODE:
                    $xmlReader->read();
                    if ($xmlReader->nodeType == \XMLReader::TEXT)
                    {
                        $errorCode = $xmlReader->value;
                    }
                    break;
                case Constants::ERROR_MESSAGE:
                    $xmlReader->read();
                    if ($xmlReader->nodeType == \XMLReader::TEXT)
                    {
                        $errorMessage = $xmlReader->value;
                    }
                    break;
                }
                break;
            case \XMLReader::END_ELEMENT:
                if ($xmlReader->name == 'Message')
                {
                    if ($messageId != NULL)
                    {
                        return new SendMessageResponseItem(TRUE, $messageId, $messageBodyMD5);
                    }
                    else
                    {
                        return new SendMessageResponseItem(FALSE, $errorCode, $errorMessage);
                    }
                }
                break;
            }
        }

        if ($messageId != NULL)
        {
            return new SendMessageResponseItem(TRUE, $messageId, $messageBodyMD5);
        }
        else
        {
            return new SendMessageResponseItem(FALSE, $errorCode, $errorMessage);
        }
    }
}

?>
