<?php
namespace AliyunMNS\Responses;

use GuzzleHttp\Promise\PromiseInterface;
use AliyunMNS\Responses\BaseResponse;
use AliyunMNS\Exception\MnsException;
use GuzzleHttp\Exception\TransferException;
use Psr\Http\Message\ResponseInterface;

class MnsPromise
{
    private $response;
    private $promise;

    public function __construct(PromiseInterface &$promise, BaseResponse &$response)
    {
        $this->promise = $promise;
        $this->response = $response;
    }

    public function isCompleted()
    {
        return $this->promise->getState() != 'pending';
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function wait()
    {
        try {
            $res = $this->promise->wait();
            if ($res instanceof ResponseInterface)
            {
                $this->response->parseResponse($res->getStatusCode(), $res->getBody());
            }
        } catch (TransferException $e) {
            $message = $e->getMessage();
            if ($e->hasResponse()) {
                $message = $e->getResponse()->getBody();
            }
            $this->response->parseErrorResponse($e->getCode(), $message);
        }
        return $this->response;
    }
}

?>
