<?php
//权限控制
\think\Hook::add('check_auth','app\\common\\behavior\\AuthenticateBehavior');

use think\Db;

/**
 * 处理相关团队
 * @author
 * @param types 类型
 * @param types 类型ID
 * @param type  权限 1只读2读写
 * @param user_id [array] 协作人
 * @param is_del 1 移除操作, 2编辑操作, 3添加操作
 * @param owner_user_id 操作人
 * @param is_module 相关 1相关，不进行数据权限判断
 */
function teamUserId($types, $types_id, $type, $user_id, $is_del, $owner_user_id, $is_module = 0)
{
    $userModel = new \app\admin\model\User();
    $authIds = [];
	switch ($types) {
        case 'crm_leads' : 
            $data_name = 'leads_id';
            $authIds = $userModel->getUserByPer('crm', 'leads', 'teamsave');
            break;
        case 'crm_customer' : 
            $data_name = 'customer_id'; 
            $authIds = $userModel->getUserByPer('crm', 'customer', 'teamsave');
            break;
        case 'crm_contacts' : 
            $data_name = 'contacts_id';
            $authIds = $userModel->getUserByPer('crm', 'contacts', 'teamsave'); 
            break;
        case 'crm_business' : 
            $data_name = 'business_id'; 
            $authIds = $userModel->getUserByPer('crm', 'business', 'teamsave');
            break;
        case 'crm_contract' : 
            $data_name = 'contract_id'; 
            $authIds = $userModel->getUserByPer('crm', 'contract', 'teamsave');
            break;
    }
    if (!is_array($types_id) && $types_id) {
        $types_id = [$types_id];
    }
    $errorMessage = [];
    foreach ($types_id as $k=>$v) {
        $resData = db($types)->where([$data_name => $v])->field('name,owner_user_id,rw_user_id,ro_user_id')->find();
        if (!in_array($resData['owner_user_id'],$authIds) && $resData['owner_user_id'] && $is_module !== 1) {
            $errorMessage[] = $resData['name'].'处理团队操作失败，错误原因：无权限';
            continue;
        }  
        $type = $type ? : 1;
        $data = [];
        //读写
        $old_rw_user_id = stringToArray($resData['rw_user_id']) ? : []; //去重
        //只读
        $old_ro_user_id = stringToArray($resData['ro_user_id']) ? : []; //去重          
        if ($is_del == 1) {
            $all_rw_user_id = $old_rw_user_id ? array_diff($old_rw_user_id, $user_id) : ''; // 差集
            $data['rw_user_id'] = $all_rw_user_id ? arrayToString($all_rw_user_id) : ''; //去空

            $all_ro_user_id = $old_ro_user_id ? array_diff($old_ro_user_id, $user_id) : ''; // 差集
            $data['ro_user_id'] = $all_ro_user_id ? arrayToString($all_ro_user_id) : ''; //去空           
        } elseif ($is_del == 2) {
            if ($type == 2) {
                $all_ro_user_id = $old_ro_user_id ? array_diff($old_ro_user_id, $user_id) : $user_id; // 差集
                $all_rw_user_id = $old_rw_user_id ? array_merge($old_rw_user_id, $user_id) : $user_id; // 合并
            } else {
                $all_rw_user_id = $old_rw_user_id ? array_diff($old_rw_user_id, $user_id) : $user_id; // 差集
                $all_ro_user_id = $old_ro_user_id ? array_merge($old_ro_user_id, $user_id) : $user_id; // 合并
            }
            $data['rw_user_id'] = $all_rw_user_id ? arrayToString($all_rw_user_id) : ''; //去空
            $data['ro_user_id'] = $all_ro_user_id ? arrayToString($all_ro_user_id) : ''; //去空         
        } else {
            $del_ro_user_id = []; //需要删除的只读
            $del_rw_user_id = []; //需要删除的读写
            foreach ($user_id as $key=>$val) {
                if (in_array($val, $old_ro_user_id) && !in_array($val, $old_rw_user_id) && $type == 2) {
                    $del_ro_user_id[] = $val;
                }
                if (in_array($val, $old_rw_user_id) && !in_array($val, $old_ro_user_id) && $type == 1) {
                    $del_rw_user_id[] = $val;
                }                
            }
            if ($type == 2) {
                $all_rw_user_id = $old_rw_user_id ? array_diff(array_merge($old_rw_user_id, $user_id), $del_rw_user_id) : $user_id; // 合并
                $all_ro_user_id = $old_ro_user_id ? array_diff($old_ro_user_id, $del_ro_user_id) : $user_id; // 合并
                $data['rw_user_id'] = $all_rw_user_id ? arrayToString($all_rw_user_id) : ''; //去空 
                if ($del_ro_user_id) {
                    $data['ro_user_id'] = $all_ro_user_id ? arrayToString($all_ro_user_id) : ''; //去空         
                }                
            } else {
                $all_rw_user_id = $old_rw_user_id ? array_diff($old_rw_user_id, $del_rw_user_id) : $user_id; // 合并
                $all_ro_user_id = $old_ro_user_id ? array_diff(array_merge($old_ro_user_id, $user_id), $del_ro_user_id) : $user_id; // 合并                
                $data['ro_user_id'] = $all_ro_user_id ? arrayToString($all_ro_user_id) : ''; //去空 
                if ($del_rw_user_id) {
                    $data['rw_user_id'] = $all_rw_user_id ? arrayToString($all_rw_user_id) : ''; //去空         
                }                             
            }
        }
        $upData = db($types)->where([$data_name => $v])->update($data);
        if (!$upData) {
            $errorMessage[] = $resData['name'].'处理团队操作失败'; 
        }
    }
    return $errorMessage ? : 1;
}

//根据时间段获取所包含的年份
function getYearByTime($start_time, $end_time)
{
    $yearArr = [];
    $monthArr = monthList($start_time, $end_time);
    foreach ($monthArr as $v) {
        $yearArr[date('Y',$v)] = date('Y',$v);
    }
    return $yearArr;
}

//根据时间段获取所包含的月份
function getmonthByTime($start_time, $end_time)
{
    $monthList = [];
    $monthArr = monthList($start_time, $end_time);
    foreach ($monthArr as $v) {
        $monthList[date('Y',$v)][] = date('m',$v);
    }
    return $monthList;
}