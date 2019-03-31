<?php
namespace AliyunMNS;

class Config
{
    //private $maxAttempts;
    private $proxy;  // http://username:password@192.168.16.1:10
    private $connectTimeout;
    private $requestTimeout;
    private $expectContinue;

    public function __construct()
    {
        // $this->maxAttempts = 3;
        $this->proxy = NULL;
        $this->requestTimeout = 35; // 35 seconds
        $this->connectTimeout = 3;  // 3 seconds
        $this->expectContinue = false;
    }

    /*
    public function getMaxAttempts()
    {
        return $this->maxAttempts;
    }

    public function setMaxAttempts($maxAttempts)
    {
        $this->maxAttempts = $maxAttempts;
    }
    */

    public function getProxy()
    {
        return $this->proxy;
    }

    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
    }

    public function getRequestTimeout()
    {
        return $this->requestTimeout;
    }

    public function setRequestTimeout($requestTimeout)
    {
        $this->requestTimeout = $requestTimeout;
    }

    public function setConnectTimeout($connectTimeout)
    {
        $this->connectTimeout = $connectTimeout;
    }

    public function getConnectTimeout()
    {
        return $this->connectTimeout;
    }

    public function getExpectContinue()
    {
        return $this->expectContinue;
    }

    public function setExpectContinue($expectContinue)
    {
        $this->expectContinue = $expectContinue;
    }
}

?>
