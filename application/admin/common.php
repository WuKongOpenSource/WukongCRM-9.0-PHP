<?php
//权限控制
\think\Hook::add('check_auth','app\\common\\behavior\\AuthenticateBehavior');
use think\Db;

function structureList($structid,$str){
    $str .= $structid.',';
    if(Db::name('AdminStructure')->where('pid ='.$structid)->find() ){
        $list = Db::name('AdminStructure')->field('id,name,pid')->where('pid ='.$structid)->select();
        foreach($list as $value){
            $str = structureList($value['id'],$str);
        }
    }
    return $str;
}

/**
 * cookies加密函数
 * @param string 加密后字符串
 */
function encrypt($data, $key = '5k-72crm') 
{ 
    $cryptdes = new com\Cryptdes($key);
    return $cryptdes->encrypt($data);
    // $prep_code = serialize($data); 
    // $block = mcrypt_get_block_size('des', 'ecb'); 
    // if (($pad = $block - (strlen($prep_code) % $block)) < $block) { 
    //     $prep_code .= str_repeat(chr($pad), $pad); 
    // } 
    // $encrypt = mcrypt_encrypt(MCRYPT_DES, $key, trim($prep_code), MCRYPT_MODE_ECB); 
    // return base64_encode($encrypt); 
} 

/**
 * cookies 解密密函数
 * @param array 解密后数组
 */
function decrypt($data, $key = '5k-72crm') 
{ 
    $cryptdes = new com\Cryptdes($key);
    return $cryptdes->decrypt($data);
    // $str = base64_decode($str); 
    // $str = mcrypt_decrypt(MCRYPT_DES, $key, $str, MCRYPT_MODE_ECB); 
    // $block = mcrypt_get_block_size('des', 'ecb'); 
    // $pad = ord($str[($len = strlen($str)) - 1]); 
    // if ($pad && $pad < $block && preg_match('/' . chr($pad) . '{' . $pad . '}$/', $str)) { 
    //     $str = substr($str, 0, strlen($str) - $pad); 
    // } 
    // return unserialize($str); 
}

/**
 * 部门树形数组
 * @param type 0 下属数组， 1包含自己
 */
function getSubObj($id, $objList, $separate, $is_first = 0) {
    $array = array(); 
    foreach ($objList as $key => $value) {
        if ($key == 0 && $is_first == 1) {
            if ($value['id'] == 1) {
                $id = 0;
            } else {
                $id = $value['pid'];
            }
        }
        if ($id == $value['pid']) {
            $array[] = array('id' => $value['id'], 'name' => $separate.$value['name']);
            $array = array_merge($array, getSubObj($value['id'], $objList, $separate.'--'));
        }
    }
    return $array;
}

/**
 * 解析sql语句
 * @param  string $content sql内容
 * @param  int $limit  如果为1，则只返回一条sql语句，默认返回所有
 * @param  array $prefix 替换表前缀
 * @return array|string 除去注释之后的sql语句数组或一条语句
 */
function parse_sql($sql = '', $limit = 0, $prefix = []) {
    // 被替换的前缀
    $from = '';
    // 要替换的前缀
    $to = '';
    // 替换表前缀
    if (!empty($prefix)) {
        $to   = current($prefix);
        $from = current(array_flip($prefix));
    }
    if ($sql != '') {
        // 纯sql内容
        $pure_sql = [];
        // 多行注释标记
        $comment = false;
        // 按行分割，兼容多个平台
        $sql = str_replace(["\r\n", "\r"], "\n", $sql);
        $sql = explode("\n", trim($sql));
        // 循环处理每一行
        foreach ($sql as $key => $line) {
            // 跳过空行
            if ($line == '') {
                continue;
            }
            // 跳过以#或者--开头的单行注释
            if (preg_match("/^(#|--)/", $line)) {
                continue;
            }
            // 跳过以/**/包裹起来的单行注释
            if (preg_match("/^\/\*(.*?)\*\//", $line)) {
                continue;
            }
            // 多行注释开始
            if (substr($line, 0, 2) == '/*') {
                $comment = true;
                continue;
            }
            // 多行注释结束
            if (substr($line, -2) == '*/') {
                $comment = false;
                continue;
            }
            // 多行注释没有结束，继续跳过
            if ($comment) {
                continue;
            }
            // 替换表前缀
            if ($from != '') {
                $line = str_replace('`'.$from, '`'.$to, $line);
            }
            if ($line == 'BEGIN;' || $line =='COMMIT;') {
                continue;
            }
            // sql语句
            array_push($pure_sql, $line);
        }
        // 只返回一条语句
        if ($limit == 1) {
            return implode($pure_sql, "");
        }
        // 以数组形式返回sql语句
        $pure_sql = implode($pure_sql, "\n");
        $pure_sql = explode(";\n", $pure_sql);
        return $pure_sql;
    } else {
        return $limit == 1 ? '' : [];
    }
}

function sendRequest($url, $params = array() , $headers = array()) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if (!empty($params)) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    }
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $res = curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $value = curl_exec($ch);
    if (curl_errno($ch)) {
        $return = array(0, '连接服务器出错', -1);
    } else {
        if (!$value) {
            $return = array(0, '服务器返回数据异常', -1);
        }
        $return = $value;
    }
    curl_close($ch);
    return $return;
}
