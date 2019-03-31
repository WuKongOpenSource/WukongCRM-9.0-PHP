<?php

/**
 * 用于接收云通信消息的临时token
 *
 * @property string messageType
 * @property string token
 * @property int expireTime
 * @property string tempAccessKey
 * @property string tempSecret
 * @property \AliyunMNS\Client client
 * @property string queue
 */

class TokenForAlicom
{

    /**
     * 设置消息类型
     * @param string $messageType
     */
	public function setMessageType($messageType)
    {
        $this->messageType = $messageType;
    }

    /**
     * 取得消息类型
     * @return string
     */
    public function getMessageType()
    {
        return $this->messageType;
    }

    /**
     * 设置临时token
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * 取得临时token
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * 设置过期时间 (unix timestamp)
     * @param int $expireTime
     */
    public function setExpireTime($expireTime)
    {
        $this->expireTime = $expireTime;
    }

    /**
     * 取得过期时间 (unix timestamp)
     * @return int
     * @return int
     */
    public function getExpireTime()
    {
        return $this->expireTime;
    }

    /**
     * 设置临时AccessKeyId
     * @param $tempAccessKey
     */
    public function setTempAccessKey($tempAccessKey)
    {
        $this->tempAccessKey = $tempAccessKey;
    }

    /**
     * 取得临时AccessKeyId
     * @return string
     */
    public function getTempAccessKey()
    {
        return $this->tempAccessKey;
    }

    /**
     * 设置临时AccessKeySecret
     * @param string $tempSecret
     */
    public function setTempSecret($tempSecret)
    {
        $this->tempSecret = $tempSecret;
    }

    /**
     * 取得临时AccessKeySecret
     * @return string
     */
    public function getTempSecret()
    {
        return $this->tempSecret;
    }

    /**
     * 设置MNS Client
     * @param \AliyunMNS\Client $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * 取得MNS Client
     * @return \AliyunMNS\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * 设置Queue Name
     * @param string $queue
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;
    }

    /**
     * 取得Queue Name
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }
}