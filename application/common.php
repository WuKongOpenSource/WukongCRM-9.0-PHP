<?php
error_reporting(E_ERROR | E_PARSE );
/**
 * 行为绑定
 */
\think\Hook::add('app_init','app\\common\\behavior\\InitConfigBehavior');

use app\common\adapter\AuthAdapter;
use app\admin\model\User as UserModel;
use app\admin\model\Field as FieldModel;
use think\Request;
use think\Db;
use extend\email\Email;
use think\Lang;
use think\helper\Time;

/**
 * 对象 转 数组
 *
 * @param object $obj 对象
 * @return array
 */
function object_to_array($obj) {
    $obj = (array)$obj;
    foreach ($obj as $k => $v) {
        if (gettype($v) == 'resource') {
            return;
        }
        if (gettype($v) == 'object' || gettype($v) == 'array') {
            $obj[$k] = (array)object_to_array($v);
        }
    }
    return $obj;
}

/**
 * 数组 转 对象
 *
 * @param array $arr 数组
 * @return object
 */
function array_to_object($arr) {
    if (gettype($arr) != 'array') {
        return;
    }
    foreach ($arr as $k => $v) {
        if (gettype($v) == 'array' || getType($v) == 'object') {
            $arr[$k] = (object)array_to_object($v);
        }
    }
    return (object)$arr;
}

/**
 * 返回对象
 * @param $array 响应数据
 */
function resultArray($array)
{
    if(isset($array['data'])) {
        $array['error'] = '';
        $code = 200;
    } elseif (is_array($array['error'])) {
        $code = 402; //返回数组格式
        $array['data'] = '';
    } elseif (isset($array['error'])) {
        $code = 400;
        $array['data'] = '';
    }
    return json([
        'code'  => $code,
        'data'  => $array['data'],
        'error' => $array['error']
    ]);
}

/**
 * 调试方法
 * @param  array   $data  [description]
 */
function p($data,$die=1)
{
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    if ($die) die;
}

/**
 * 用户密码加密方法
 * @param  string $str      加密的字符串
 * @param  [type] $auth_key 加密符
 * @param  [string] $username 用户名
 * @return string           加密后长度为32的字符串
 */
function user_md5($str, $auth_key = '', $username = '')
{
    return '' === $str ? '' : md5(sha1($str) . md5($str.$auth_key));
}

/**
 * 金额展示规则,超过1万时以万为单位，低于1万时以千为单位，低于1千时以元为单位
 * @author Michael_xu
 * @param  string $money      金额
 * @return string           
 */
function money_view($money)
{
    $data = '0元';
    if (($money/10000) > 1) {
        $data = is_int($money/10000) ? ($money/10000).'万' : rand(($money/10000),2).'万';
    } elseif (($money/1000) > 1) {
        $data = is_int($money/1000) ? ($money/1000).'千' : rand(($money/1000),2).'千';
    } else {
        $data = $money.'元';
    }
    return $data;
}

/**
 * 高级筛选条件
 * @author Michael_xu
 * @param  $array 条件数组
 * @param  $module 相关模块
 * @return string           
 */
function where_arr($array = [], $m = '', $c = '', $a = '')
{
    $userModel = new UserModel();
    $checkStatusList = ['待审核','审核中','审核通过','审核失败','已撤回','未提交','已作废'];
    $checkStatusArray = ['待审核' => '0','审核中'=>'1','审核通过'=>'2','审核失败'=>'3','已撤回'=>'4','未提交'=>'5','已作废'=>'6'];
    //查询自定义字段模块多选字段类型
    $check_field_arr = [];
    //特殊字段

    //过滤系统参数
    $unset_arr = ['page','limit','order_type','order_field'];
    if (!is_array($array)) {
       return []; 
    }
    $types = $c;
    foreach ($array as $k=>$v) {
        if (!in_array($k, $unset_arr)) {
            $c = $types.'.';
            if ($k == 'customer_name') {
                $k = 'name';
                $c = 'customer.';
            }
            if ($k == 'contract_name') {
                $k = 'name';
                $c = 'contract.';
            }
            if ($k == 'business_name') {
                $k = 'name';
                $c = 'business.';
            }
            if ($k == 'contacts_name') {
                $k = 'name';
                $c = 'contacts.';
            }
            if ($k == 'check_status' && is_array($v) && in_array($v['value'],$checkStatusList)) {
                $v['value'] = $checkStatusArray[$v['value']] ? : '0';
            }
            if (is_array($v)) {
                if ($v['state']) {
                    $address_where[] = '%'.$v['state'].'%';
                    if ($v['city']) {
                        $address_where[] = '%'.$v['city'].'%';
                        if ($v['area']) {
                            $address_where[] = '%'.$v['area'].'%';
                        }
                    }
                    if ($v['condition'] == 'not_contain') {
                        $where[$c.$k] = ['notlike', $address_where, 'OR'];
                    } else {
                        $where[$c.$k] = ['like', $address_where, 'AND'];
                    }
                } elseif (!empty($v['start']) || !empty($v['end'])) {
                    if ($v['start'] && $v['end']) {
                        $where[$c.$k] = ['between', [$v['start'], $v['end']]];
                    } elseif ($v['start']) {
                        $where[$c.$k] = ['egt', $v['start']];
                    } else {
                        $where[$c.$k] = ['elt', $v['end']];
                    }
                } elseif (!empty($v['start_date']) || !empty($v['end_date'])) {
                    if ($v['start_date'] && $v['end_date']) {
                        $where[$c.$k] = ['between', [$v['start_date'], $v['end_date']]];
                    } elseif ($v['start_date']) {
                        $where[$c.$k] = ['egt', $v['start_date']];
                    } else {
                        $where[$c.$k] = ['elt', $v['end_date']];
                    }                                     
                } elseif (!empty($v['value']) || $v['value'] === '0') {
                    if (in_array($k, $check_field_arr)) {
                        $where[$c.$k] = field($v['value'], 'contains');
                    } else {
                        $where[$c.$k] = field($v['value'], $v['condition']);
                    }
                } elseif (in_array($v['condition'], ['is_empty','is_not_empty'])) {
                    $where[$c.$k] = field($v['value'], $v['condition']);
                } else {
                    $where[$c.$k] = $v;
                }                  
            } elseif (!empty($v)) {
                $where[$c.$k] = field($v);
            } else {
                $where[$c.$k] = $v;
            }
        }
    }    
    return $where ? : [];
}

/**
 * 根据搜索生成where条件
 * @author Michael_xu
 * @param  string $search 搜索内容
 * @param  $condition 搜索条件
 * @return array           
 */
function field($search, $condition = '')
{
    switch (trim($condition)) {
        case "is" : $where = ['eq',$search];break;
        case "isnot" :  $where = ['neq',$search];break;
        case "contains" :  $where = ['like','%'.$search.'%'];break;
        case "not_contain" :  $where = ['notlike','%'.$search.'%'];break;
        case "start_with" :  $where = ['like',$search.'%'];break;
        case "end_with" :  $where = ['like','%'.$search];break;
        case "is_empty" :  $where = ['eq',''];break;
        case "is_not_empty" :  $where = ['neq',''];break;
        case "eq" : $where = ['eq',$search];break;
        case "neq" : $where = ['neq',$search];break;        
        case "gt" :  $where = ['gt',$search];break;
        case "egt" :  $where = ['egt',$search];break;
        case "lt" :  
                if (strtotime($search) !== false && strtotime($search) != -1) {
                    $where = ['lt',strtotime($search)];
                } else {
                    $where = ['lt',$search];
                }
                break;
        case "elt" :  $where = ['elt',$search];break;
        case "in" :  $where = ['in',$search];break;
        default : $where = ['eq',$search]; break;      
    }
    return $where;
}

/**
 * 将单个搜索转换为高级搜索格式
 * @author Michael_xu
 * @param  string $value 搜索内容
 * @param  $condition 搜索条件
 * @return array           
 */
function field_arr($value, $condition = '')
{
    if (is_array($value)) {

    } else {
        $condition = $condition ? : 'eq';
        $where_arr = ['value' => $value,'condition' => $condition];
    }
    
    return $where_arr;    
}

/**
 * 记录操作日志 
 * @author Michael_xu
 * @param  $id array  操作对象id数组
 * @return       
 */
function actionLog($id, $join_user_ids='', $structure_ids='', $content='')
{
    if (!is_array($id)) {
        $idArr[] = $id;
    } else {
        $idArr = $id;
    }
    $header = Request::instance()->header();
    $authKey = $header['authkey'];       
    $cache = cache('Auth_'.$authKey);  
    if (!$cache) {
        return false;
    }
    $userInfo = $cache['userInfo'];
    $category = $userInfo['id'] == 1 ? '管理员' : '员工';

    $request = request();
    $m = strtolower($request->module());
    $c = strtolower($request->controller());
    $a = strtolower($request->action());
	
    $res_action = true;
    foreach ($idArr as $v) {
        $data = [];
        $data['user_id'] = $userInfo['id'];
        $data['module_name'] = $module_name = $m;
        $data['controller_name'] = $controller_name = $c;
        $data['action_name'] = $action_name = $a;
        $data['action_id'] = $v;
        $data['create_time'] = time();
        $data['content'] = $content ? : lang('ACTIONLOG', [$category, $userInfo['realname'], date('Y-m-d H:i:s'), lang($action_name), $v, lang($controller_name)]);
        $data['join_user_ids'] = $join_user_ids ? : ''; //抄送人
        $data['structure_ids'] = $structure_ids ? : ''; //抄送部门
        if ($action_name == 'delete' || $action_name == 'commentdel') {
            $data['action_delete'] = 1;
        }
        if (!db('admin_action_log')->insert($data)) {
            $res_action = false;
        }      
    }
    
    if ($res_action) {
        return true;
    } else {
        return false;
    }
}

/**
 * 判断操作权限
 * @author Michael_xu 
 * @param  
 * @return       
 */    
function checkPerByAction($m, $c, $a)
{
    /*获取头部信息*/ 
    $header = Request::instance()->header();
    $authKey = $header['authkey'];       
    $cache = cache('Auth_'.$authKey);
    if (!$cache) {
        return false;
    }
    $userInfo = $cache['userInfo'];
    $adminTypes = adminGroupTypes($userInfo['id']);
    if (in_array(1,$adminTypes)) {
        return true;
    }
    if (empty($m) && empty($c) && empty($a)) {
        $request = Request::instance();
        $m = strtolower($request->module());
        $c = strtolower($request->controller());
        $a = strtolower($request->action());
    }
    $authAdapter = new AuthAdapter($authKey);
    $ruleName = $m.'-'.$c.'-'.$a;
    if (!$authAdapter->checkIntime($ruleName, $userInfo['id'])) {
        return false;
    }
    return true;
}

/**
 * 给树状菜单添加level并去掉没有子菜单的菜单项
 * @param  array   $data  [description]
 * @param  integer $root  [description]
 * @param  string  $child [description]
 * @param  string  $level [description]
 */
function memuLevelClear($data, $root=1, $child='children', $level='level')
{
    if (is_array($data)) {
        foreach($data as $key => $val){
        	// $data[$key]['selected'] = false;
        	$data[$key]['level'] = $root;
        	if (!empty($val[$child]) && is_array($val[$child])) {
				$data[$key][$child] = memuLevelClear($val[$child],$root+1);
        	}else if ($root<3&&$data[$key]['menu_type']==1) {
        		unset($data[$key]);
        	}
        	if (empty($data[$key][$child])&&($data[$key]['level']==1)&&($data[$key]['menu_type']==1)) {
        		unset($data[$key]);
        	}
        }
        return array_values($data);
    }
    return array();
}

/**
 * [rulesDeal 给树状规则表处理成 module-controller-action ]
 * @AuthorHTL
 * @DateTime 
 * @param     [array]                   $data [树状规则数组]
 * @return    [array]                         [返回数组]
 */
function rulesDeal($data)
{   
    if (is_array($data)) {
        $ret = [];
        foreach ($data as $k1 => $v1) {
            $str1 = $v1['name'];
            if (is_array($v1['children'])) {
                foreach ($v1['children'] as $k2 => $v2) {
                    $str2 = $str1.'-'.$v2['name'];
                    if (is_array($v2['children'])) {
                        foreach ($v2['children'] as $k3 => $v3) {
                            $str3 = $str2.'-'.$v3['name'];
                            $ret[] = $str3;
                        }
                    }else{
                        $ret[] = $str2;
                    }
                }
            }else{
                $ret[] = $str1;
            }
        }
        return $ret;
    }
    return [];
}

/**
 * 获取下属userId
 * @author Michael_xu
 * @param $self == true  包含自己
 * @param $type == 0  下属userid
 * @param $type == 1  全部userid
 * @param $user_id 需要查询的user_id
 */
function getSubUserId($self = true, $type = 0, $user_id = '')
{   
    $request = Request::instance();
    $header = $request->header();    
    $authKey = $header['authkey'];
    $cache = cache('Auth_'.$authKey);
    if (!$user_id) {
        if (!$cache) {
            return false;
        }
        $userInfo = $cache['userInfo'];
        $user_id = $userInfo['id'];
        $adminTypes = adminGroupTypes($user_id);  
        if (in_array(1,$adminTypes)) {
            $type = 1;
        }        
    }  

    $belowIds = [];
    if (empty($type)) {
        if ($user_id) {
            $belowIds = getSubUser($user_id);
        }
    } else {
        $belowIds = getSubUser(0);
    }   
    if ($self == true) {
        $belowIds[] = $user_id;
    } else {
        $belowIds = $belowIds ? array_diff($belowIds,array($user_id)) : [];
    }
    return array_unique($belowIds);
}

/**
 * 获取下属userId
 * @author Michael_xu
 */
function getSubUser($userId, $queried = [])
{
    $sub_user = db('admin_user')->where(['parent_id'=>$userId])->column('id');
    if ($sub_user) {
        foreach ($sub_user as $v) {
            if (in_array($v, $queried)) {
                continue;
            }
            $queried[] = $v;
            $son_user = [];
            $son_user = getSubUser($v, $queried);
            if (!empty($son_user)) {
                $sub_user = array_merge($sub_user, $son_user);
            }
        }
    }
    return $sub_user;
}

/**
 * 阿里大于短信发送
 * @param unknown $appkey 
 * @param unknown $secret
 * @param unknown $signName 短信签名
 * @param unknown $smsParam
 * @param unknown $templateCode 短信模板
 * @param unknown $send_mobile 接收手机号
 * @param unknown $code 短信验证码
 * @param unknown $template_code 模板参数
 */
function aliSmsSend($send_mobile, $code, $signName, $templateCode) {
    $appkey = '';
    $secret = '';
    import('alimsg.api.Sms',EXTEND_PATH);
    header('Content-Type: text/plain; charset=utf-8');
    $sms = new Sms( $appkey, $secret);
    $template_code = Array("code"=>$code);
    $response = $sms->sendSms($signName,$templateCode,$send_mobile, $template_code);
    $data = object_to_array($response);
    if ( $data['Message']=='OK' && $data['Code']=="OK") {
        return true;
    } else {
        return false;
    }
}

/**
 * 发送邮件
 * @param unknown $toemail
 * @param unknown $title
 * @param unknown $content
 * @return boolean
 */
function emailSend($email_host, $email_id, $email_pass, $email_addr, $toemail, $title, $content){
    $result=false;
    try {
        $mail = new Email();
        $mail->setServer($email_host, $email_id, $email_pass);
        $mail->setFrom($email_addr);
        $mail->setReceiver($toemail);
        $mail->setMailInfo($title, $content);
        $result=$mail->sendMail();
    } catch (\Exception $e) {
        $result=false;
    }
    return $result;
}

/**
 * 发送站内信
 * @author Michael_xu
 * @param  $user_id 接收人user_id
 * @param  $action_id 操作id
 * @param  $sysMessage 1为系统消息
 * @param  $content 消息内容
 * @return 
 */
function sendMessage($user_id, $content, $action_id, $sysMessage = 0)
{
    $content = trim($content);
    if (!$user_id) return false;
    if (!$content) return false;
    if (!is_array($user_id)) {
        $user_ids[] = $user_id;
    } else {
        $user_ids = $user_id;
    }
    $user_ids = array_unique(array_filter($user_ids));
    $request = request();
    $m = strtolower($request->module());
    $c = strtolower($request->controller());
    $a = strtolower($request->action());

    $userInfo = [];
    if ($sysMessage == 0) {
        $header = $request->header();
        $authkey = $header['authkey'];
        $cache = cache('Auth_'.$authkey); 
        if (!$cache) {
            return false;
        }
        $userInfo = $cache['userInfo'];
    }    
    foreach ($user_ids as $v) {
        $data = [];
        $data['content'] = $content;
        $data['from_user_id'] = $userInfo['id'] ? : 0;
        $data['to_user_id'] = $v;
        $data['read_time'] = 0;
        $data['send_time'] = time();
        $data['module_name'] = $m;
        $data['controller_name'] = $c;
        $data['action_name'] = $a;        
        $data['action_id'] = $action_id;        
        db('admin_message')->insert($data); 
    }  
    return true;
}

/**
 * 格式化字节大小
 * @param  number $size     字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string               格式化后的带单位的大小
 * @author
 */
function format_bytes($size, $delimiter = '') {
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
    return round($size, 2) . $delimiter . $units[$i];
}

/**
 * 数据修改日志
 * @param $types 类型 
 * @param $action_id 操作ID
 * @param $newData 新数据
 * @param $newData 新数据
 * @author Michael_xu
 * @return 
 */
function updateActionLog($user_id, $types, $action_id, $oldData = [], $newData = [], $content = '')
{
    if (is_array($oldData) && is_array($newData) && $user_id) {
        $differentData = array_diff_assoc($newData, $oldData); //获取差异值
        $fieldModel = new FieldModel();
        $userModel = new UserModel();
        $structureModel = new \app\admin\model\Structure();
        $field_arr = $fieldModel->getField(['types' => $types,'unFormType' => ['file','form']]); //获取字段属性
        $newFieldArr = array();
        foreach ($field_arr as $k=>$v) {
            $newFieldArr[$v['field']] = $v;
        }
        $unField = ['update_time']; //定义过滤字段
        $message = [];
        $un_form_type = ['file','form'];
        foreach ($differentData as $k=>$v) {
            if ($newFieldArr[$k] && !in_array($newFieldArr[$k]['form_type'],$un_form_type)) {
                $field_name = '';
                $field_name = $newFieldArr[$k]['name'];
                $new_value = $v;
                $old_value = $oldData[$k] ? : '空';       
                if ($newFieldArr[$k]['form_type'] == 'datetime') {
                    $new_value = $v ? date('Y-m-d', $v) : '';
                    $old_value = date('Y-m-d', $oldData[$k]);
                    if (empty($v) && empty($oldData[$k])) continue;                
                } elseif ($newFieldArr[$k]['form_type'] == 'user') {
                    $new_value = $v ? implode(',',$userModel->getUserNameByArr(stringToArray($v))) : '';
                    $old_value = $v ? implode(',',$userModel->getUserNameByArr(stringToArray($oldData[$k]))) : '';
                } elseif ( $newFieldArr[$k]['form_type'] == 'structure') {
                    $new_value = $v ? implode(',',$structureModel->getStructureNameByArr(stringToArray($v))) : '';
                    $old_value = $v ? implode(',',$structureModel->getStructureNameByArr(stringToArray($oldData[$k]))) : ''; 
                } elseif ($newFieldArr[$k]['form_type'] == 'business_status') {
                    $new_value = $v ? db('crm_business_status')->where(['status_id' => $v])->value('name') : '';
                    $old_value = $v ? db('crm_business_status')->where(['status_id' => $oldData[$k]])->value('name') : '';
                } elseif ($newFieldArr[$k]['form_type'] == 'business_type') {
                    $new_value = $v ? db('crm_business_type')->where(['type_id' => $v])->value('name') : '';
                    $old_value = $v ? db('crm_business_type')->where(['type_id' => $oldData[$k]])->value('name') : '';
                } elseif ($newFieldArr[$k]['form_type'] == 'customer') {
                    $new_value = $v ? db('crm_customer')->where(['customer_id' => $v])->value('name') : '';
                    $old_value = $v ? db('crm_customer')->where(['customer_id' => $oldData[$k]])->value('name') : '';
                } elseif ($newFieldArr[$k]['form_type'] == 'category') {
                    $new_value = $v ? db('crm_product_category')->where(['category_id' => $v])->value('name') : '';
                    $old_value = $v ? db('crm_product_category')->where(['category_id' => $oldData[$k]])->value('name') : '';
                } elseif ($newFieldArr[$k]['form_type'] == 'business') {
                    $new_value = $v ? db('crm_business')->where(['business_id' => $v])->value('name') : '';
                    $old_value = $v ? db('crm_business')->where(['business_id' => $oldData[$k]])->value('name') : '';
                } elseif ($newFieldArr[$k]['field'] == 'check_status') {
                    $statusArr = ['0'=>'待审核','1'=>'审核中','2'=>'审核通过','3'=>'已拒绝','4'=>'已撤回','5'=>'未提交'];
                    $new_value = $statusArr[$v];
                    $old_value = $statusArr[$oldData[$k]];
                }               
                $message[] = '将 '."'".$field_name."'".' 由 '.$old_value.' 修改为 '.$new_value;
            }
        }
        if ($message) {
            $data = [];
            $data['user_id'] = $user_id;
            $data['create_time'] = time();
            $data['types'] = $types;
            $data['action_id'] = $action_id;
            $data['content'] = implode('.|.', $message);
            db('admin_action_record')->insert($data);
        }
    } elseif ($content) {
        $data = [];
        $data['user_id'] = $user_id;
        $data['create_time'] = time();
        $data['types'] = $types;
        $data['action_id'] = $action_id;
        $data['content'] = $content;
        db('admin_action_record')->insert($data);        
    }
}

/**
 * 截取字符串
 * @param $start 开始截取位置
 * @param $length 截取长度
 * @author Michael_xu
 * @return 
 */
function msubstr($str, $start = 0, $length, $charset="utf-8", $suffix=true) {
    if (function_exists("mb_substr")) {
        $slice = mb_substr($str, $start, $length, $charset);
    } elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
        if (false === $slice) {
            $slice = '';
        }
    } else {
        $re['utf-8']  = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("",array_slice($match[0], $start, $length));
    }
    if (utf8_strlen($str) < $length) $suffix = false;
    return $suffix ? $slice.'...' : $slice;
}

function utf8_strlen($string = null) {
    preg_match_all("/./us", $string, $match);
    return count($match[0]);
}

/**
 * 合法性验证
 * @param client_sign 签名参数值，使用相同规则对提交参数进行加密验证
 * @author Michael_xu
 * @return 
 */
function checkVerify($saftCode = '5kcrm@'){
    $parmList = Request::instance()->post();
    $header = $request->header();
    $parmList['sessionId'] = $header['sessionId'];
    $authkey = $header['authKey'];
    $clientSign = $parmList['client_sign'];

    if ($clientSign) {
        unset($parmList['client_sign']);
        if (count($parmList) > 0) {
            // 对要签名参数按照签名格式组合
            foreach ($parmList as $key=>$value){
                if (isset($_POST[$key])) {
                    $parame[$key] = $key.'='.trim($_POST[$key]);
                }else{
                    return false;
                }
            }
            ksort($parame); //参数排序
            $returnValue = implode( "&", $parame); //拼接字符串
            if ($returnValue) {
                //base64加密
                $signCalc = base64_encode(hash_hmac("sha1", $returnValue, $saftCode.$authkey, $raw_output=false));
                // 检查参数签名是否一致
                if (trim($clientSign) != trim($signCalc)) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    } else {
        //签名认证错误
        return false;
    }
}

/**
 * 数组转换字符串（以逗号隔开）
 * @param 
 * @author Michael_xu
 * @return 
 */
function arrayToString($array)
{
    if (!is_array($array)) {
        $data_arr[] = $array;
    } else {
    	$data_arr = $array;
    }
    $data_arr = array_filter($data_arr); //数组去空
    $data_arr = array_unique($data_arr); //数组去重
    $data_arr = array_merge($data_arr);
    $string = $data_arr ? ','.implode(',', $data_arr).',' : '';
    return $string ? : '';
}

/**
 * 字符串转换数组（以逗号隔开）
 * @param 
 * @author Michael_xu
 * @return 
 */
function stringToArray($string)
{
    if (is_array($string)) {
        $data_arr = array_unique(array_filter($string));
    } else {
        $data_arr = $string ? array_unique(array_filter(explode(',', $string))) : [];
    }
    $data_arr = $data_arr ? array_merge($data_arr) : [];
    return $data_arr ? : [];
}

/**
 * 根据时间戳获取星期几
 * @param $time 要转换的时间戳
 */
function getTimeWeek($time, $i = 0)
{
    $weekarray = array("日", "一", "二", "三", "四", "五", "六");
    $oneD = 24 * 60 * 60;
    return "星期" . $weekarray[date("w", $time + $oneD * $i)];
}

/**
 * 二维数组排序(选择)
 * @param $select 要进行排序的select结果集
 * @param $field  排序的字段
 * @param $order 排序方式1降序2升序
 */
function sort_select($select=array(), $field, $order=1)
{
    $count = count($select);
    if ($order == 1) {
        for ($i=0; $i < $count; $i++) {
            $k = $i;
            for ($j=$i; $j < $count; $j++) { 
                if ($select[$k][$field] < $select[$j][$field]) {
                    $k = $j;
                }
            }
            $temp = $select[$i];
            $select[$i] = $select[$k];
            $select[$k] = $temp;
        }
        return $select;
    } else {
        for ($i=0; $i < $count; $i++) {
            $k = $i;
            for ($j=$i; $j < $count; $j++) { 
                if ($select[$k][$field] > $select[$j][$field]) {
                    $k = $j;
                }
            }
            $temp = $select[$i];
            $select[$i] = $select[$k];
            $select[$k] = $temp;
        }
        return $select;
    }
}

/**
 * 将秒数转换为时间 (年、天、小时、分、秒）
 * @param 
 */
function getTimeBySec($time){
    if (is_numeric($time)) {
        $value = array(
          "years" => 0, "days" => 0, "hours" => 0,
          "minutes" => 0, "seconds" => 0,
        );
        if ($time >= 31556926) {
            $value["years"] = floor($time/31556926);
            $time = ($time%31556926);
            $t .= $value["years"] ."年";
        }
        if ($time >= 86400) {
            $value["days"] = floor($time/86400);
            $time = ($time%86400);
            $t .= $value["days"] ."天";
        }
        if ($time >= 3600) {
            $value["hours"] = floor($time/3600);
            $time = ($time%3600);
            $t .= $value["hours"] ."小时";
        }
        if ($time >= 60) {
            $value["minutes"] = floor($time/60);
            $time = ($time%60);
            $t .= $value["minutes"] ."分钟";
        }
        if ($time < 60) {
            $value["seconds"] = floor($time);
            $t .= $value["seconds"] ."秒";
        }
        return $t;
    } else {
        return (bool) FALSE;
    }
}
/*
 *根据年月计算有几天
 */
function getmonthByYM($param)
{
    $month = $param['month'] ? $param['month'] : date('m',time());
    $year = $param['year'] ? $param['year'] : date('Y',time());
    if (in_array($month, array('1', '3', '5', '7', '8', '01', '03', '05', '07', '08', '10', '12'))) {  
        $days = '31';  
    } elseif ($month == 2) { 
        if ($year % 400 == 0 || ($year % 4 == 0 && $year % 100 !== 0)) {
            //判断是否是闰年  
            $days = '29';  
        } else {  
            $days = '28';  
        } 
    } else {  
        $days = '30';  
    }
    return $days;
}
/**
 * 根据时间戳计算当月天数
 * @param 
 */
function getmonthdays($time){
    $month = date('m',$time);
    $year = date('Y',$time);
    if (in_array($month, array('1', '3', '5', '7', '8', '01', '03', '05', '07', '08', '10', '12'))) {  
        $days = '31';  
    } elseif ($month == 2) { 
        if ($year % 400 == 0 || ($year % 4 == 0 && $year % 100 !== 0)) {
            //判断是否是闰年  
            $days = '29';  
        } else {  
            $days = '28';  
        } 
    } else {  
        $days = '30';  
    }
    return $days;
}

/** 
 * 生成从开始时间到结束时间的日期数组
 * @param type，默认时间戳格式
 * @param type = 1 时，date格式
 * @param type = 2 时，获取每日开始、结束时间
 */ 
function dateList($start, $end, $type = 0){
    if (!is_numeric($start) || !is_numeric($end) || ($end<=$start)) return '';
    $i = 0;
    //从开始日期到结束日期的每日时间戳数组
    $d = array();
    if ($type == 1) {
        while ($start <= $end) {
            $d[$i] = date('Y-m-d', $start);
            $start = $start+86400;
            $i++;
        }
    } else {
        while ($start <= $end) {
            $d[$i] = $start;
            $start = $start+86400;
            $i++;
        }
    }
    if ($type == 2) {
        $list = array();
        foreach ($d as $k=>$v) {
            $list[$k] = getDateRange($v);
        }
        return $list;
    } else {
        return $d;
    }
}

/** 
 * 获取指定日期开始时间与结束时间
 */
function getDateRange($timestamp){
    $ret = array();
    $ret['sdate'] = strtotime(date('Y-m-d',$timestamp));
    $ret['edate'] = strtotime(date('Y-m-d',$timestamp))+86400;
    return $ret;
}

/** 
* 生成从开始月份到结束月份的月份数组
* @param int $start 开始时间戳
* @param int $end 结束时间戳
*/ 
function monthList($start,$end){
	if (!is_numeric($start) || !is_numeric($end) || ($end <= $start)) return '';
	$start = date('Y-m',$start);
	$end = date('Y-m',$end);
	//转为时间戳
	$start = strtotime($start.'-01');
	$end = strtotime($end.'-01');
	$i = 0;
	$d = array();
	while ($start <= $end) {
		//这里累加每个月的的总秒数 计算公式：上一月1号的时间戳秒数减去当前月的时间戳秒数
		$d[$i] = $start;
		$start += strtotime('+1 month',$start)-$start;
		$i++;
	} 
	return $d;
}

/**
 * 人民币转大写
 * @param
 */
function cny($ns){
    static $cnums = array("零","壹","贰","叁","肆","伍","陆","柒","捌","玖"), 
    $cnyunits = array("圆","角","分"), 
    $grees = array("拾","佰","仟","万","拾","佰","仟","亿"); 
    list($ns1,$ns2) = explode(".",$ns,2); 
    $ns2 = array_filter(array($ns2[1],$ns2[0])); 
    $ret = array_merge($ns2,array(implode("", _cny_map_unit(str_split($ns1), $grees)), "")); 
    $ret = implode("",array_reverse(_cny_map_unit($ret,$cnyunits))); 
    return str_replace(array_keys($cnums), $cnums,$ret); 
}

function _cny_map_unit($list,$units) {
    $ul = count($units); 
    $xs = array(); 
    foreach (array_reverse($list) as $x) { 
        $l = count($xs); 
        if ($x!="0" || !($l%4)) {
            $n = ($x=='0'?'':$x).($units[($l-1)%$ul]); 
        } else {
            $n = is_numeric($xs[0][0]) ? $x : ''; 
        }
        array_unshift($xs, $n); 
    } 
    return $xs; 
}

/**
 * 根据类型获取开始结束时间戳数组
 * @param 
 */
function getTimeByType($type = 'today')
{
    switch ($type) {
        case 'yesterday' : $timeArr = Time::yesterday(); break;
        case 'week' : $timeArr = Time::week(); break;
        case 'lastWeek' : $timeArr = Time::lastWeek(); break;
        case 'month' : $timeArr = Time::month(); break;
        case 'lastMonth' : $timeArr = Time::lastMonth(); break;
        case 'quarter' :
            //本季度
            $month=date('m');
            if ($month == 1 || $month == 2 || $month == 3) {
                $daterange_start_time = strtotime(date('Y-01-01 00:00:00'));
                $daterange_end_time = strtotime(date("Y-03-31 23:59:59"));
            } elseif ($month == 4 || $month == 5 || $month == 6) {
                $daterange_start_time = strtotime(date('Y-04-01 00:00:00'));
                $daterange_end_time = strtotime(date("Y-06-30 23:59:59"));
            } elseif ($month == 7 || $month == 8 || $month == 9) {
                $daterange_start_time = strtotime(date('Y-07-01 00:00:00'));
                $daterange_end_time = strtotime(date("Y-09-30 23:59:59"));
            } else {
                $daterange_start_time = strtotime(date('Y-10-01 00:00:00'));
                $daterange_end_time = strtotime(date("Y-12-31 23:59:59"));
            }
            $timeArr = array($daterange_start_time,$daterange_end_time);
            break;
        case 'lastQuarter' : 
            //上季度
            $month = date('m');
            if ($month == 1 || $month == 2 ||$month == 3) {
                $year = date('Y')-1;
                $daterange_start_time = strtotime(date($year.'-10-01 00:00:00'));
                $daterange_end_time = strtotime(date($year.'-12-31 23:59:59'));
            } elseif ($month == 4 || $month == 5 ||$month == 6) {
                $daterange_start_time = strtotime(date('Y-01-01 00:00:00'));
                $daterange_end_time = strtotime(date("Y-03-31 23:59:59"));
            } elseif ($month == 7 || $month == 8 ||$month == 9) {
                $daterange_start_time = strtotime(date('Y-04-01 00:00:00'));
                $daterange_end_time = strtotime(date("Y-06-30 23:59:59"));
            } else {
                $daterange_start_time = strtotime(date('Y-07-01 00:00:00'));
                $daterange_end_time = strtotime(date("Y-09-30 23:59:59"));
            }            
            $timeArr = array($daterange_start_time,$daterange_end_time);           
            break;        
        case 'year' : $timeArr = Time::year(); break;
        case 'lastYear' : $timeArr = Time::lastYear(); break;
        default : $timeArr = Time::today(); break;
    }
    return $timeArr;
}

/**
 * 服务器附件完整路径处理
 * @param
 */
function getFullPath($path)
{
    if ($path) {
        return $_SERVER['REQUEST_SCHEME'] . '://'.$_SERVER['HTTP_HOST'].substr($_SERVER["SCRIPT_NAME"],0,-10).substr(str_replace(DS, '/', $path),1);
    } else {
        return '';
    }
}

/*取得文件后缀*/
function getExtension($filename){
    $mytext = substr($filename, strrpos($filename, '.')+1);
    return $mytext;
}

/**
 * 生成编号
 * @param prefix 前缀
 * @author Michael_xu
 * @return 
 */
function prefixNumber($prefix, $number_id = 0, $str = 5)
{
    return $prefixNumber = $prefix.str_pad($number_id,$str,0,STR_PAD_LEFT); //填充字符串的左侧（将字符串填充为新的长度）
}

/**
* curl 模拟GET请求
* @author lee
***/
function curl_get($url){
    //初始化
    $ch = curl_init();
    //设置抓取的url
    curl_setopt($ch, CURLOPT_URL, $url);
    //设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // https请求 不验证hosts 
    //执行命令
    $output = curl_exec($ch);
    curl_close($ch); //释放curl句柄
    return $output; 
}

/**
 * 地址坐标转换
 * @param prefix 前缀
 * @author Michael_xu
 * @return 
 */
function get_lng_lat($address){
    $map_ak = config('map_ak');
    $url = "http://api.map.baidu.com/geocoder/v2/?address=$address&output=json&ak=$map_ak&callback=showLocation";
    $ret_script = curl_get($url);
    preg_match_all("/\{.*?\}}/is", $ret_script, $matches);
    $ret_arr = json_decode($matches[0][0],true);
    if ($ret_arr['status'] == 0) { //成功
        $location['lng'] = $ret_arr['result']['location']['lng'];
        $location['lat'] = $ret_arr['result']['location']['lat'];
        return $location;
    } else {
        return false;
    }
}

/**
 * 导出数据为excel表格
 *@param $data    一个二维数组,结构如同从数据库查出来的数组
 *@param $title   excel的第一行标题,一个数组,如果为空则没有标题
 *@param $filename 下载的文件名
 *@param exportexcel($arr,array('id','账户','密码','昵称'),'文件名!');
*/
function exportexcel($data=array(),$title=array(),$filename='report'){
    header("Content-type:application/octet-stream");
    header("Accept-Ranges:bytes");
    header("Content-type:application/vnd.ms-excel");  
    header("Content-Disposition:attachment;filename=".$filename.".xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    //导出xls 开始
    if (!empty($title)){
        foreach ($title as $k => $v) {
            $title[$k]=iconv("UTF-8", "GB2312",$v);
        }
        $title= implode("\t", $title);
        echo "$title\n";
    }
    if (!empty($data)){
        foreach($data as $key=>$val){
            foreach ($val as $ck => $cv) {
                $data[$key][$ck]=iconv("UTF-8", "GB2312", $cv);
            }
            $data[$key]=implode("\t", $data[$key]);
        }
        echo implode("\n",$data);
    }
}

//根据数据库查询出来数组获取某个字段拼接字符串
function getFieldArray($array = array(),$field=''){
	if(is_array($array) && $field){
		$ary = array();
		foreach($array as $value){
			$ary[] = $value[$field];
		}
	    $str = implode(',',$ary);
		return $str;
	} else {
		return false;
	}
}

/**
* 检查该字段若必填，加上"*"
* @param is_null     是否为空 0否  1是
* @param name 字段名称
**/
function sign_required($is_null, $name){
	if ($is_null == 1) {
		return '*'.$name;
	} else {
		return $name;
	}
}

/**
 * [获取是否有管理员角色 adminGroupTypes]
 * @param  user_id  当前人ID
 * @return  
 */
function adminGroupTypes($user_id)
{
    $userModel = new UserModel();
    $groupsArr = $userModel->get($user_id)->groups;
    $groupids = [];
    if ($groupsArr) {
        foreach ($groupsArr as $key=>$val) {
            $groupids[] = $val['id'];
        }        
    }
    $types = db('admin_group')->where(['id' => ['in',$groupids]])->group('types')->column('types');
    if ($user_id == 1) {
       $types[] = 1; 
    }
    return $types ? : [];
}

/**
 * [权限数组]
 * @param ruleIds 当前人权限id
 * @return  
 */
function rulesListToArray($rulesList, $ruleIds = [])
{
    $newList = [];
    if (!is_array($rulesList)) {
        return array();
    } else {
        foreach ($rulesList as $k=>$v) {
            if (!is_array($v['children'])) continue;
            foreach ($v['children'] as $k1 => $v1) {
                if (!is_array($v1['children'])) continue;
                foreach ($v1['children'] as $k2 => $v2) {
                    $check = false;
                    if (in_array($v2['id'], $ruleIds)) {
                        $check = true;
                    }
                    $newList[$v['name']][$v1['name']][$v2['name']] = $check;
                }
            }
        }
    }
    return $newList ? : [];
}

/**
 * [获取下一审批信息 nextCheckData]
 * @param  user_id  审批申请人ID
 * @param  flow_id  审批流ID
 * @param  types 关联对象
 * @param  types_id 联对象ID
 * @param  order_id 审批排序ID
 * @return  
 */
function nextCheckData($user_id, $flow_id, $types, $types_id, $order_id, $check_user_id)
{
    $new_order_id = $order_id;
    $max_order_id = db('admin_examine_step')->where(['flow_id' => $flow_id])->max('order_id'); //审批流最大排序ID
    $examineStepModel = new \app\admin\model\ExamineStep();

    $stepInfo = $examineStepModel->getStepByOrder($flow_id, $new_order_id); //审批步骤
    $next_user_ids = [];      
    $is_end = 0; //审批结束
    //固定流程（status 1负责人主管，2指定用户（任意一人），3指定用户（多人会签），4上一级审批人主管）
    
    //当前步骤审批人user_id
    $step_user_ids = $examineStepModel->getUserByStep($stepInfo['step_id'], $user_id);
    if ($step_user_ids) {
        if (!$order_id) {
            //创建时使用
            $sub_user_ids = stringToArray($step_user_ids);
        } else {
            if ($stepInfo['status'] == 3) {
                //会签（并关系）
                //当前步骤已审批user_id
                $check_user_ids = $examineStepModel->getUserByCheck($types, $types_id, $new_order_id);
                $user_ids[] = $check_user_id;
                $check_user_ids = $check_user_ids ? array_merge($check_user_ids, $user_ids) : $user_ids;
                //剩余审批人user_id
                $sub_user_ids = $check_user_ids ? array_diff(explode(',',$step_user_ids), $check_user_ids) : $step_user_ids; 
                $sub_user_ids = array_unique(array_filter($sub_user_ids));          
                if (!$sub_user_ids) {
                    $is_end = 1;
                }            
            } else {
                $is_end = 1;
            }      
        }
    } else {
        $is_end = 1;
    }
    if ($is_end == 1) {
        $next_order_id = $new_order_id+1;
    } else {
        $next_order_id = $new_order_id;
    }
    //当前审批步骤已结束
    if ($is_end == 1) {
        //当前审批流程是否结束
        $stepInfo = $examineStepModel->getStepByOrder($flow_id, $next_order_id); //审批步骤
        $next_step_user_ids = $examineStepModel->getUserByStep($stepInfo['step_id'], $user_id);
        $next_user_ids = stringToArray($next_step_user_ids);
    } else {
        $next_user_ids = array_unique(array_filter($sub_user_ids));
    }
    if (!$next_user_ids && $next_order_id <= $max_order_id) {
        $newRes = [];
        $newRes = nextCheckData($user_id, $flow_id, $types, $types_id, $next_order_id, $check_user_id);
        $next_user_ids = $newRes['next_user_ids'];
    }
    $data = [];
    $data['order_id'] = ($next_order_id <= $max_order_id) ? $next_order_id : $max_order_id;
    $data['next_user_ids'] = $next_user_ids ? : '';
    return $data;      
}

/**
 * 解析获取php.ini 的upload_max_filesize（单位：byte）
 * @param $dec int 小数位数
 * @return float （单位：byte）
 * */
function get_upload_max_filesize_byte($dec=2){
    $max_size=ini_get('upload_max_filesize');
    preg_match('/(^[0-9\.]+)(\w+)/',$max_size,$info);
    $size=$info[1];
    $suffix=strtoupper($info[2]);
    $a = array_flip(array("B", "KB", "MB", "GB", "TB", "PB"));
    $b = array_flip(array("B", "K", "M", "G", "T", "P"));
    $pos = $a[$suffix]&&$a[$suffix]!==0?$a[$suffix]:$b[$suffix];
    return round($size*pow(1024,$pos),$dec);
}

/**
 * 模拟post进行url请求
 * @param string $url
 * @param string $param
 */
function curl_post($url = '', $post = array()) 
{
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
    curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post); // Post提交的数据包
    curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    $res = curl_exec($curl); // 执行操作
    if (curl_errno($curl)) {
        echo 'Errno'.curl_error($curl);//捕抓异常
    }
    curl_close($curl); // 关闭CURL会话
    return $res; // 返回数据，json格式
}

/**
 * 输出json字符串，用于接口调试
 *
 * @return void
 * @author ymob
 */
function vdd()
{
    header('Content-Type: text/json; charset=utf-8');
    $args = func_get_args();
    foreach ($args as &$val) {
        if ($val instanceof think\Model) {
            $val = $val->getLastSql();
        }
    }
    if (count($args) < 2) {
        $args = array_shift($args);
    }
    die(json_encode($args));
}


/**
 * 根据时间查询参数返回时间条件 （用于统计分析模块）
 *
 * @param array $where
 * @param string $field
 * @return void
 * @author ymob
 */
function getWhereTimeByParam(&$where, $field = 'create_time')
{
    $param = request()->param();
    if (empty($param['type']) && empty($param['start_time'])) {
        $param['type'] = 'month';
    }
    if (!empty($param['start_time'])) {
        $where[$field] = ['between', [$param['start_time'], $param['end_time']]];
    } else {
        $timeRange = getTimeByType($param['type']);
        if ($timeRange) {
            $where[$field] = ['between', [$timeRange[0], $timeRange[1]]];
        }
    }
}

/**
 * 根据员工、部门查询参数返回员工、部门条件 （用于统计分析模块）
 *
 * @param array $where
 * @param string $field
 * @param string $m
 * @param string $c
 * @param string $a
 * @return void
 * @author ymob
 */
function getWhereUserByParam(&$where, $field = 'owner_user_id', $m = '', $c = '', $a = '')
{
    $param = request()->param();
    $userModel = new UserModel();

    $map_user_ids = [];
    if ($param['user_id']) {
        $map_user_ids = array($param['user_id']);
    } else {
        if ($param['structure_id']) {
            $map_user_ids = $userModel->getSubUserByStr($param['structure_id'], 2);
        }
    }
    $perUserIds = $userModel->getUserByPer($m, $c, $a); //权限范围内userIds
    $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集
    $where[$field] = array('in',$userIds);
}

/**
 * 获取客户浏览器类型
 */
function getBrowser()
{
    $Browser = $_SERVER['HTTP_USER_AGENT'];
    if (preg_match('/MSIE/i', $Browser)) {
        $Browser = 'MSIE';
    } elseif (preg_match('/Firefox/i', $Browser)) {
        $Browser = 'Firefox';
    } elseif (preg_match('/Chrome/i', $Browser)) {
        $Browser = 'Chrome';
    } elseif (preg_match('/Safari/i', $Browser)) {
        $Browser = 'Safari';
    } elseif (preg_match('/Opera/i', $Browser)) {
        $Browser = 'Opera';
    } else {
        $Browser = 'Other';
    }
    return $Browser;
}

/**
 * 获取客户端系统
 */
function getOS()
{
    $agent = $_SERVER['HTTP_USER_AGENT'];
    if (preg_match('/win/i', $agent)) {
        if (preg_match('/nt 6.1/i', $agent)) {
            $OS = 'Windows 7';
        } else if (preg_match('/nt 6.2/i', $agent)) {
            $OS = 'Windows 8';
        } else if (preg_match('/nt 10.0/i', $agent)) {
            $OS = 'Windows 10';
        } else {
            $OS = 'Windows';
        }
    } elseif (preg_match('/mac/i', $agent)) {
        $OS = 'MAC';
    } elseif (preg_match('/linux/i', $agent)) {
        $OS = 'Linux';
    } elseif (preg_match('/unix/i', $agent)) {
        $OS = 'Unix';
    } elseif (preg_match('/bsd/i', $agent)) {
        $OS = 'BSD';
    } else {
        $OS = 'Other';
    }
    return $OS;
}

/**
 * 根据IP获取地址
 */
function getAddress($ip)
{
    $res = file_get_contents("http://ip.360.cn/IPQuery/ipquery?ip=" . $ip);
    $res = json_decode($res, 1);
    if ($res && $res['errno'] == 0) {
        return explode("\t", $res['data'])[0];
    } else {
        return '';
    }
}

/**
 * 下载服务器文件
 *
 * @param string $file  文件路径
 * @param string $name  下载名称
 * @param boolean $del  下载后删除
 * @return void
 * @author Ymob
 */
function download($file, $name = '', $del = false)
{
    if (!file_exists($file)) {
        return resultArray([
            'error' => '文件不存在',
        ]);
    }
    // 仅允许下载 public 目录下文件
    $res = strpos(realpath($file), realpath('./public'));
    if ($res !== 0) {
        return resultArray([
            'error' => '文件路径错误',
        ]);
    }

    $fp = fopen($file, 'r');
    $size = filesize($file);

    //下载文件需要的头
    header("Content-type: application/octet-stream");
    header("Accept-Ranges: bytes");
    header('ResponseType: blob');
    header("Accept-Length: $size");
    $file_name = $name != '' ? $name : pathinfo($file, PATHINFO_BASENAME);
    // urlencode 处理中文乱码
    header("Content-Disposition:attachment; filename=" . urlencode($file_name));

    // 导出数据时  csv office Excel 需要添加bom头
    if (pathinfo($file, PATHINFO_EXTENSION) == 'csv') {
	    echo "\xEF\xBB\xBF"; 	// UTF-8 BOM
    }

    $fileCount = 0;
    $fileUnit = 1024;
    while (!feof($fp) && $size - $fileCount > 0) {
        $fileContent = fread($fp, $fileUnit);
        echo $fileContent;
        $fileCount += $fileUnit;
    }
    fclose($fp);

    // 删除
    if ($del) @unlink($file);
    die();
}

/**
 * 临时目录生成文件名，并返回绝对路径
 *
 * @param   string  $ext       文件类型后缀
 * @param   string  $path      临时文件目录 默认 ./public/temp/
 * @return  string  $file_path 文件名称绝对路径
 * @author ymob
 */
function tempFileName($ext = '')
{
    // 临时目录
    $path = TEMP_DIR . date('Ymd') . DS; 
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }
    
    $ext = trim($ext, '.');
    do {
        $temp_file = md5(time() . rand(1000, 9999));
        $file_path = $path . $temp_file . '.' . $ext;
    } while (file_exists($file_path));
    return $file_path;
}

/**
 * 递归删除目录
 *
 * @param string $dir
 * @return void
 * @author Ymob
 */
function delDir($dir)
{
    $dh = opendir($dir);
    while ($file = readdir($dh)) {
        if ($file != "." && $file != "..") {
            $fullpath = $dir . "/" . $file;
            if (!is_dir($fullpath)) {
                @unlink($fullpath);
            } else {
                deldir($fullpath);
            }
        }
    }

    closedir($dh);
    //删除当前文件夹：
    @rmdir($dir);
}

/**
 * 商业智能 查询缓存
 *
 * @param string $sql   Sql语句
 * @param int $bi_slow_query_time   慢查询时间(毫秒)，默认读取Config(bi_slow_query_time)
 * @return mixed
 * @author Ymob
 * @datetime 2019-11-21 17:36:50
 */
function queryCache($sql = '', $bi_slow_query_time = null) {
    $key = 'BI_queryCache' . md5($sql);
    $val = cache($key);
    if (!$val) {
        $start_time = microtime(true) * 1000;
        $val = Db::query($sql);
        $end_time = microtime(true) * 1000;
        $slow_query = true;
        // 是否使用系统默认设置-慢查询时间
        if ($bi_slow_query_time === null) {
            $bi_slow_query_time = config('bi_slow_query_time');
        }
        if ($bi_slow_query_time > 0) {
            $slow_query = ($end_time - $start_time) > $bi_slow_query_time;
        }
        if ($slow_query && $val) {
            cache($key, $val, config('bi_cache_time'));
        }
    }
    return $val;
}

/**
 * 图表时间范围处理，按月/天返回时间段数组
 *
 * @param int $start    开始时间（时间戳）
 * @param int $end      结束时间（时间戳）
 * @return array 
 * @author Ymob
 * @datetime 2019-11-18 09:25:09
 */
function getTimeArray($start = null, $end = null)
{
    if ($start == null || $end == null) {
        $param = request()->param();
        switch ($param['type']) {
            // 本年
            case 'year':
                $start = strtotime(date('Y-01-01'));
                $end = strtotime('+1 year', $start) - 1;
                break;
            // 去年
            case 'lastYear':
                $start = strtotime(date(date('Y') - 1 . '-01-01'));
                $end = strtotime('+1 year', $start) - 1;
                break;
            // 本季度、上季度
            case 'quarter':
            case 'lastQuarter':
                $t = intval((date('m') - 1) / 3);
                $start_y = ($t * 3) + 1;
                $start = strtotime(date("Y-{$start_y}-01"));
                if ($param['type'] == 'lastQuarter') {  // 上季度
                    $start = strtotime('-3 month', $start);
                }
                $end = strtotime('+3 month', $start) - 1;
                break;
            // 本月、上月
            case 'month':
            case 'lastMonth':
                $start = strtotime(date('Y-m-01'));
                if ($param['type'] == 'lastMonth') {
                    $start = strtotime('-1 month', $start);
                }
                $end = strtotime('+1 month', $start) - 1;
                break;
            // 本周、上周
            case 'week':
            case 'lastWeek':
                $start = strtotime('-' . (date('w') - 1) . 'day', strtotime(date('Y-m-d')));
                if ($param['type'] == 'lastWeek') {
                    $start = strtotime('-7 day', $start);
                }
                $end = strtotime('+7 day', $start) - 1;
                break;
            // 今天、昨天
            case 'tody':
            case 'yesterday':
                $start = strtotime(date('Y-m-d'));
                if ($param['type'] == 'yesterday') {
                    $start = strtotime('-1 day', $start);
                }
                $end = strtotime('+1 day', $start) - 1;
                break;
            default:
                if ($param['start_time'] && $param['end_time']) {
                    $start = $param['start_time'];
                    $end = $param['end_time'];
                } else {
                    // 本年
                    $start = strtotime(date('Y-01-01'));
                    $end = strtotime('+1 year', $start) - 1;
                }
                break;
        }
    }

    $between = [$start, $end];
    $list = [];
    $len = ($end - $start) / 86400;
    // 大于30天 按月统计、小于按天统计
    if ($len > 30) {
        $time_format = '%Y-%m';
        while (true) {
            $start = strtotime(date('Y-m-01', $start));
            $item = [];
            $item['type'] = date('Y-m', $start);
            $item['start_time'] = $start;
            $item['end_time'] = strtotime('+1 month', $start) - 1;
            $list[] = $item;
            if ($item['end_time'] >= $end) break;
            $start = $item['end_time'] + 1;
        }
    } else {
        $time_format = '%Y-%m-%d';
        while (true) {
            $item = [];
            $item['type'] = date('Y-m-d', $start);
            $item['start_time'] = $start;
            $item['end_time'] = strtotime('+1 day', $start) - 1;
            $list[] = $item;
            if ($item['end_time'] >= $end) break;
            $start = $item['end_time'] + 1;
        }
    }

    return [
        'list' => $list,        // 时间段列表
        'time_format' => $time_format,      // 时间格式 mysql 格式化时间戳
        'between' => $between       // 开始结束时间
    ];
}

/**
 * 打印程序执行时间
 *
 * @param integer $s
 * @return void
 * @author Ymob
 * @datetime 2019-11-28 09:31:45
 */
function tt($s = 0)
{
    die((string) round(microtime(1) - ($s ?: THINK_START_TIME), 3));
}

/**
 * 数据库备份
 */
function DBBackup($file = '')
{
    $type = config('database.type');
    $host = config('database.hostname');
    $port = config('database.hostport');
    $dbname = config('database.database');
    $username = config('database.username');
    $password = config('database.password');
    $dsn = "{$type}:host={$host};dbname={$dbname};port={$port}";
    
    $save_path = dirname(APP_PATH) . DS . 'data' . DS . date('Ym') . DS;
    if (!file_exists($save_path) && !mkdir($save_path)) {
        return 'data目录无写入权限';
    }
    $file = $save_path . 'upgrade_' . date('d_H_i') . '.sql';

    if (file_exists($file)) {
        return '数据库已备份，两次间隔不小于1分钟。';
    }

    try {
        $backup = new \com\Mysqldump($dsn, $username, $password);
        $backup->start($file);
        return true;
    } catch (\Exception $e) {
        return '备份失败，请手动备份。错误原因：' . $e->getMessage();
    }

}
