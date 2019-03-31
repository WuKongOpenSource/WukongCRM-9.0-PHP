<?php
namespace AliyunMNS\Exception;

class MnsException extends \RuntimeException
{
    private $mnsErrorCode;
    private $requestId;
    private $hostId;

    public function __construct($code, $message, $previousException = NULL, $mnsErrorCode = NULL, $requestId = NULL, $hostId = NULL)
    {
        parent::__construct($message, $code, $previousException);

        if ($mnsErrorCode == NULL)
        {
            if ($code >= 500)
            {
                $mnsErrorCode = "ServerError";
            }
            else
            {
                $mnsErrorCode = "ClientError";
            }
        }
        $this->mnsErrorCode = $mnsErrorCode;

        $this->requestId = $requestId;
        $this->hostId = $hostId;
    }

    public function __toString()
    {
        $str = "Code: " . $this->getCode() . " Message: " . $this->getMessage();
        if ($this->mnsErrorCode != NULL)
        {
            $str .= " MnsErrorCode: " . $this->mnsErrorCode;
        }
        if ($this->requestId != NULL)
        {
            $str .= " RequestId: " . $this->requestId;
        }
        if ($this->hostId != NULL)
        {
            $str .= " HostId: " . $this->hostId;
        }
        return $str;
    }

    public function getMnsErrorCode()
    {
        return $this->mnsErrorCode;
    }

    public function getRequestId()
    {
        return $this->requestId;
    }

    public function getHostId()
    {
        return $this->hostId;
    }
}

?>
