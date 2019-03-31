<?php
namespace AliyunMNS;

use AliyunMNS\Exception\MnsException;
use AliyunMNS\Responses\BaseResponse;

class AsyncCallback
{
    protected $succeedCallback;
    protected $failedCallback;

    public function __construct(callable $succeedCallback, callable $failedCallback)
    {
        $this->succeedCallback = $succeedCallback;
        $this->failedCallback = $failedCallback;
    }

    public function onSucceed(BaseResponse $result)
    {
        return call_user_func($this->succeedCallback, $result);
    }

    public function onFailed(MnsException $e)
    {
        return call_user_func($this->failedCallback, $e);
    }
}

?>
