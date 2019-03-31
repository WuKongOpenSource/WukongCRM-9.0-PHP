<?php
namespace AliyunMNS\Model;

use AliyunMNS\Constants;

class DeleteMessageErrorItem
{
    protected $errorCode;
    protected $errorMessage;
    protected $receiptHandle;

    public function __construct($errorCode, $errorMessage, $receiptHandle)
    {
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
        $this->receiptHandle = $receiptHandle;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    public function getReceiptHandle()
    {
        return $this->receiptHandle;
    }

    static public function fromXML($xmlReader)
    {
        $errorCode = NULL;
        $errorMessage = NULL;
        $receiptHandle = NULL;

        while ($xmlReader->read())
        {
            switch ($xmlReader->nodeType)
            {
            case \XMLReader::ELEMENT:
                switch ($xmlReader->name)
                {
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
                if ($xmlReader->name == Constants::ERROR)
                {
                    return new DeleteMessageErrorItem($errorCode, $errorMessage, $receiptHandle);
                }
                break;
            }
        }

        return new DeleteMessageErrorItem($errorCode, $errorMessage, $receiptHandle);
    }
}

?>
