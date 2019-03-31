<?php

ini_set("display_errors", "on");

require_once dirname(__DIR__) . '/msg_sdk/vendor/autoload.php';

require_once __DIR__ . '/lib/TokenGetterForAlicom.php';
require_once __DIR__ . '/lib/TokenForAlicom.php';

use Aliyun\Core\Config;
use AliyunMNS\Exception\MnsException;

// 加载区域结点配置
Config::load();

/**
 * Class SmsDemo
 *
 * @property TokenGetterForAlicom tokenGetter
 */
class MsgDemo
{

    /**
     * MsgDemo 构造函数
     *
     * @param string $accountId AccountId
     * @param string $accessKeyId AccessKeyId
     * @param string $accessKeySecret AccessKeySecret
     */
	public function __construct($accountId, $accessKeyId, $accessKeySecret)
    {
        $this->tokenGetter = new TokenGetterForAlicom(
            $accountId,
            $accessKeyId,
            $accessKeySecret
        );
    }

    /**
     * 获取消息
     *
     * @param string $messageType 消息类型: SmsReport | SmsUp
     * @param string $queueName 在云通信页面开通相应业务消息后，就能在页面上获得对应的queueName<br/>(e.g. Alicom-Queue-xxxxxx-SmsReport)
     * @param callable $callback <p>
     * 回调仅接受一个消息参数;
     * <br/>回调返回true，则工具类自动删除已拉取的消息;
     * <br/>回调返回false,消息不删除可以下次获取.
     * <br/>(e.g. function ($message) { return true; }
     * </p>
     */
    public function receiveMsg($messageType, $queueName, callable $callback)
    {
        $i = 0;
        // 取回执消息失败3次则停止循环拉取
        while ( $i < 3) {
	        try
	        {
	            // 取临时token
	        	$tokenForAlicom = $this->tokenGetter->getTokenByMessageType($messageType, $queueName);

	        	// 使用MNSClient得到Queue
                $queue = $tokenForAlicom->getClient()->getQueueRef($queueName);

                // 接收消息，并根据实际情况设置超时时间
	            $res = $queue->receiveMessage(2);

                // 计算消息体的摘要用作校验
	            $bodyMD5 = strtoupper(md5(base64_encode($res->getMessageBody())));

                // 比对摘要，防止消息被截断或发生错误
	            if ($bodyMD5 == $res->getMessageBodyMD5())
	            {
	                // 执行回调
	                if(call_user_func($callback, json_decode($res->getMessageBody())))
                    {
                        // 当回调返回真值时，删除已接收的信息
                        $receiptHandle = $res->getReceiptHandle();
	                	$queue->deleteMessage($receiptHandle);
	                }
	            }

	            return; // 整个取回执消息流程完成后退出
	        }
	        catch (MnsException $e)
	        {
                $i++;
                echo "ex:{$e->getMnsErrorCode()}\n";
                echo "ReceiveMessage Failed: {$e}\n";
	        }
        }
	}
}

// 调用示例：

header('Content-Type: text/plain; charset=utf-8');

$demo = new MsgDemo(
    "1943695596114318", // 此处不需要替换修改!
    "yourAccessKeyId", // 请替换成您自己的AccessKeyId
    "yourAccessKeySecret" // 请替换成您自己的AccessKeySecret
);

echo "MsgDemo::receiveMsg SmsReport\n";
$demo->receiveMsg(
    // string $messageType 消息类型: SmsReport | SmsUp
    "SmsReport",

    // string $queueName 在云通信页面开通相应业务消息后，就能在页面上获得对应的queueName
    "Alicom-Queue-xxxxxxx-SmsReport",

    /**
     * 回调
     * @param stdClass $message 消息数据
     * @return bool 返回true，则工具类自动删除已拉取的消息。返回false，消息不删除可以下次获取
     */
    function ($message) {
        print_r($message);
        return false;
    }
);


echo "MsgDemo::receiveMsg SmsUp\n";
$demo->receiveMsg(
// string $messageType 消息类型: SmsReport | SmsUp
    "SmsUp",

    // string $queueName 在云通信页面开通相应业务消息后，就能在页面上获得对应的queueName
    "Alicom-Queue-xxxxxxx-SmsUp",

    /**
     * 回调
     * @param stdClass $message 消息数据
     * @return bool 返回true，则工具类自动删除已拉取的消息。返回false，消息不删除可以下次获取
     */
    function ($message) {
        print_r($message);
        return false;
    }
);