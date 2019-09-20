<?php
namespace com;

/**
 * 对称加密解密
 */
class Cryptdes
{
    // 加密方式
    const METHOD = 'AES-256-CBC';
    public $key;
    public $error = '';
    
    public $ivStatus;


    public function __construct($key, $ivStatus = true)
    {
        if (!function_exists('openssl_encrypt') || !function_exists('openssl_decrypt')) {
            $this->error = '未启用 openssl 扩展，请开启。';
        }
        $this->ivStatus = $ivStatus;
        $this->key = $key;
    }

    /**
     * 加密
     */
    public function encrypt($data)
    {
        $str_padded = json_encode($data);
        if (strlen($str_padded) % 16) {
            $str_padded = str_pad($str_padded, strlen($str_padded) + 16 - strlen($str_padded) % 16, "\0");
        }
        if ($this->ivStatus) {
            $iv = $this->makeIv();
            $code = openssl_encrypt($str_padded, SELF::METHOD, $this->key, OPENSSL_NO_PADDING, $iv);
            return [
                'iv' => base64_encode($iv),
                'code' => base64_encode($code)
            ];
        } else {
            $code = openssl_encrypt($str_padded, SELF::METHOD, $this->key, OPENSSL_NO_PADDING);
            return base64_encode($code);
        }
    }

    /**
     * 解密
     */
    public function decrypt($code, $iv = '')
    {
        $code = base64_decode($code);
        $iv = base64_decode($iv);
        
        $res = openssl_decrypt($code, SELF::METHOD, $this->key, OPENSSL_NO_PADDING, $iv);
        return json_decode(trim($res), true);
    }

    /**
     * 生成 初始化向量 iv
     */
    public function makeIv()
    {
        $ivlen = openssl_cipher_iv_length(SELF::METHOD);
        return openssl_random_pseudo_bytes($ivlen);
    }
}