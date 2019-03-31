<?php
namespace AliyunMNS\Requests;

abstract class BaseRequest
{
    protected $headers;
    protected $resourcePath;
    protected $method;

    protected $body;
    protected $queryString;

    public function __construct($method, $resourcePath) {
        $this->method = $method;
        $this->resourcePath = $resourcePath;
    }

    abstract public function generateBody();
    abstract public function generateQueryString();

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setQueryString($queryString)
    {
        $this->queryString = $queryString;
    }

    public function getQueryString()
    {
        return $this->queryString;
    }

    public function isHeaderSet($header)
    {
        return isset($this->headers[$header]);
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function removeHeader($header)
    {
        if (isset($this->headers[$header]))
        {
            unset($this->headers[$header]);
        }
    }

    public function setHeader($header, $value)
    {
        $this->headers[$header] = $value;
    }

    public function getResourcePath()
    {
        return $this->resourcePath;
    }

    public function getMethod()
    {
        return $this->method;
    }
}

?>
