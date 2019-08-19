<?php
// +----------------------------------------------------------------------
// | Description: 工作台及基础
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Request;
use think\Session;
use think\Hook;
use think\Db;

class Index extends ApiCommon

{    /**
     * 用于判断权限
     * @permission 无限制
     * @allow 登录用户可访问
     * @other 其他根据系统设置
    **/    
    public function _initialize()
    {
        parent::_initialize();
        $action = [
            'permission'=>[],
            'allow'=>['fields','fieldrecord','authlist']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 获取字段属性，用于筛选或其他操作
     * @param 
     * @return
     */  
    public function fields()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $fieldModel = model('Field');
        $field_arr = $fieldModel->getField($param);        
        return resultArray(['data' => $field_arr]);
    }

    /**
     * 获取字段修改记录
     * @param 
     * @return
     */
    public function fieldRecord()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $actionRecordModel = model('ActionRecord');
        $data = $actionRecordModel->getDataList($param);
        if (!$data) {
            return resultArray(['data' => '暂无数据']);
        }
        return resultArray(['data' => $data]);
    }   

    //权限数据返回
    public function authList()
    {   
        $userInfo = $this->userInfo;
        $u_id = $userInfo['id'];
        $ruleMap = [];
        $adminTypes = adminGroupTypes($u_id);
        if (in_array(1,$adminTypes)) {
            $ruleMap['status'] = 1;
        } else {
            $userModel = new \app\admin\model\User();
            $groups = $userModel->get($u_id)->groups;
            $ruleIds = [];
            foreach($groups as $k => $v) {
                if (stringToArray($v['rules'])) {
                    $ruleIds = array_merge($ruleIds, stringToArray($v['rules']));
                }
            }
            $ruleIds = array_unique($ruleIds);
            $ruleMap['id'] = array('in', $ruleIds);
            $ruleMap['status'] = 1;     
        }
        $newRuleIds = [];
        // 重新设置ruleIds，除去部分已删除或禁用的权限。
        $rules = Db::name('admin_rule')->where($ruleMap)->order('types asc')->select();
        foreach ($rules as $k => $v) {
            $newRuleIds[] = $v['id'];
            $rules[$k]['name'] = strtolower($v['name']);
        }
        $tree = new \com\Tree();
        $rulesList = $tree->list_to_tree($rules, 'id', 'pid', 'child', 0, true, array('pid'));
        //权限数组
        $authList = rulesListToArray($rulesList, $newRuleIds);

        //系统设置权限（1超级管理员2系统设置管理员3部门与员工管理员4审批流管理员5工作台管理员6客户管理员7项目管理员8公告管理员）
        $settingList = ['0' => 'system','1' => 'user','2' => 'permission','3' => 'examineFlow','4' => 'oa','5' => 'crm','6' => 'work'];
        $adminTypes = adminGroupTypes($u_id);
        $newSetting = [];
        foreach ($settingList as $k=>$v) {
            $check = false;     
            if (in_array('1', $adminTypes) || in_array('2', $adminTypes)) {
                $check = true;
            } else {
                if ($v == 'user' && in_array('3', $adminTypes)) $check = true;              
                if ($v == 'permission' && in_array('3', $adminTypes)) $check = true;                
                if ($v == 'examineFlow' && in_array('4', $adminTypes)) $check = true;               
                if ($v == 'oa' && in_array('5', $adminTypes)) $check = true;                
                if ($v == 'crm' && in_array('6', $adminTypes)) $check = true;               
                if ($v == 'work' && in_array('7', $adminTypes)) $check = true;               
            }
            if ($check == true) {
                $newSetting['manage'][$v] = $check;
            }
        }
        if ($authList && $newSetting) {
            $authList = array_merge($authList, $newSetting);
        } elseif ($newSetting) {
            $authList = $newSetting;
        }      

        return resultArray(['data' => $authList]);
    }       
}
 