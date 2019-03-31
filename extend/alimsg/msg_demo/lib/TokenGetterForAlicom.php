<?php

use Aliyun\Api\Msg\Request\V20170525\QueryTokenForMnsQueueRequest;
use Aliyun\Core\Config;
use AliyunMNS\Client;
use Aliyun\Core\Profile\DefaultProfile;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Core\Exception\ClientException;
use Aliyun\Core\Exception\ServerException;

// 加载区域结点配置
Config::load();

/**
 * 接收云通信消息的临时token
 *
 * @property array tokenMap
 * @property int bufferTime 过期时间小于2分钟则重新获取，防止服务器时间误差
 * @property string mnsAccountEndpoint
 * @property \Aliyun\Core\DefaultAcsClient acsClient
 */
class TokenGetterForAlicom
{
    /**
     * TokenGetterForAlicom constructor.
     *
     * @param string $accountId AccountId
     * @param string $accessKeyId AccessKeyId
     * @param string $accessKeySecret AccessKeySecret
     */
    public function __construct($accountId, $accessKeyId, $accessKeySecret)
    {
        $endpointName = "cn-hangzhou";
        $regionId = "cn-hangzhou";
        $productName = "Dybaseapi";
        $domain = "dybaseapi.aliyuncs.com";

        $this->tokenMap = [];
        $this->bufferTime = 2 * 60;
        DefaultProfile::addEndpoint($endpointName, $regionId, $productName, $domain);
        $profile = DefaultProfile::getProfile($regionId, $accessKeyId, $accessKeySecret);
        $this->acsClient = new DefaultAcsClient($profile);
        $this->mnsAccountEndpoint = $this->getAccountEndpoint($accountId, $regionId);
    }


    /**
     * 配置获取AccountEndPoint
     *
     * @param string $accountId AccountId
     * @param string $region Region
     * @param bool $secure 是否启用https
     * @param bool $internal
     * @param bool $vpc
     * @return string <ul>
     * <li>http(s)://{AccountId}.mns.cn-beijing.aliyuncs.com</li>
     * <li>http(s)://{AccountId}.mns.cn-beijing-internal.aliyuncs.com</li>
     * <li>http(s)://{AccountId}.mns.cn-beijing-internal-vpc.aliyuncs.com</li>
     * </ul>
     */
    private function getAccountEndpoint($accountId, $region, $secure=false, $internal=false, $vpc=false)
    {
        $protocol = $secure ? 'https' : 'http';
        $realRegion = $region;
        if ($internal) {
            $realRegion .= '-internal';
        }

        if ($vpc) {
            $realRegion .= '-vpc';
        }

        return "$protocol://$accountId.mns.$realRegion.aliyuncs.com";
    }


    /**
     * 远程取Token
     *
     * @param string $messageType 消息订阅类型 SmsReport | SmsUp
     * @return TokenForAlicom|bool
     */
    public function getTokenFromRemote($messageType)
    {
        $request = new QueryTokenForMnsQueueRequest();
        $request->setMessageType($messageType);

        try {
            $response = $this->acsClient->getAcsResponse($request);
            // print_r($response);
            $tokenForAlicom = new TokenForAlicom();
            $tokenForAlicom->setMessageType($messageType);
            $tokenForAlicom->setToken($response->MessageTokenDTO->SecurityToken);
            $tokenForAlicom->setTempAccessKey($response->MessageTokenDTO->AccessKeyId);
            $tokenForAlicom->setTempSecret($response->MessageTokenDTO->AccessKeySecret);           
            $tokenForAlicom->setExpireTime($response->MessageTokenDTO->ExpireTime);
            // print_r($tokenForAlicom);
            return $tokenForAlicom;
        }
        catch (ServerException $e) {
            print_r($e->getErrorCode());
            print_r($e->getErrorMessage());
        }
        catch (ClientException $e) {
            print_r($e->getErrorCode());   
            print_r($e->getErrorMessage());
        }
        return false;
    }

    /**
     * 先从tokenMap中取，如果不存在则远程取Token并存入tokenMap
     *
     * @param string $messageType 消息订阅类型 SmsReport | SmsUp
     * @param string $queueName 在云通信页面开通相应业务消息后，就能在页面上获得对应的queueName<br/>(e.g. Alicom-Queue-xxxxxx-SmsReport)
     * @return TokenForAlicom|bool
     */
    public function getTokenByMessageType($messageType, $queueName)
    {
        $tokenForAlicom = null;
        if(isset($this->tokenMap[$messageType])) {
            $tokenForAlicom = $this->tokenMap[$messageType];
        }

        if(null == $tokenForAlicom || strtotime($tokenForAlicom->getExpireTime()) - time() > $this->bufferTime)
        {
            $tokenForAlicom =$this->getTokenFromRemote($messageType);

            $client = new Client(
                $this->mnsAccountEndpoint,
                $tokenForAlicom->getTempAccessKey(),
                $tokenForAlicom->getTempSecret(),
                $tokenForAlicom->getToken()
            );

            $tokenForAlicom->setClient($client);
            $tokenForAlicom->setQueue($queueName);

            $this->tokenMap[$messageType] = $tokenForAlicom;
        }

        return $tokenForAlicom;
    }
}