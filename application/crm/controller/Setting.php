<?php
// +----------------------------------------------------------------------
// | Description: 客户模块设置
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;

class Setting extends ApiCommon
{
	/**
     * 用于判断权限
     * @permission 无限制
     * @allow 登录用户可访问
     * @other 其他根据系统设置
    **/    
    public function _initialize()
    {
        $action = [
            'permission'=>[''],
            'allow'=>['config','configdata','team','teamsave','contractday','recordlist','recordedit']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }

        $userInfo = $this->userInfo;
        //权限判断
        $unAction = ['team','teamsave','recordlist'];
        $adminTypes = adminGroupTypes($userInfo['id']);
        if (!in_array(6,$adminTypes) && !in_array(1,$adminTypes) && !in_array(2,$adminTypes) && !in_array($a, $unAction)) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }        
    } 

    /**
     * 客户相关配置
     * @author Michael_xu
     * @return 
     */
    public function config()
    {
    	$configModel = model('ConfigData');
		$param = $this->param;
        if ((int)$param['follow_day'] > (int)$param['deal_day']) {
           return resultArray(['error' => '成交设置时长不能大于跟进设置时长']); 
        }
        $res = $configModel->createData($param);
        if ($res) {
            return resultArray(['data' => '设置成功']);
        } else {
        	return resultArray(['error' => $configModel->getError()]);
        }    	
    }

    /**
     * 客户相关配置(详情)
     * @author Michael_xu
     * @return 
     */
    public function configData()
    {
        $configModel = model('ConfigData');
        $data = $configModel->getData();
        return resultArray(['data' => $data]);      
    }    

    /**
     * 相关团队列表
     * @author Michael_xu
     * @param type 1负责人，2只读，3读写
     * @return 
     */
    public function team()
    {
        $param = $this->param;
        $userModel = new \app\admin\model\User();
        if (!$param['types'] || !$param['types_id']) {
            return resultArray(['error' => '参数错误']);
        }
        switch ($param['types']) {
            case 'crm_leads' : $dataModel = new \app\crm\model\Leads(); break;
            case 'crm_customer' : $dataModel = new \app\crm\model\Customer(); break;
            case 'crm_contacts' : $dataModel = new \app\crm\model\Contacts(); break;
            case 'crm_business' : $dataModel = new \app\crm\model\Business(); break;
            case 'crm_contract' : $dataModel = new \app\crm\model\Contract(); break;
        }
        $resData = $dataModel->getDataById($param['types_id']);
        $ro_user_ids = $resData['ro_user_id'] ? array_filter(explode(',',$resData['ro_user_id'])) : []; //只读权限
        $rw_user_ids = $resData['rw_user_id'] ? array_filter(explode(',',$resData['rw_user_id'])) : []; //读写权限

        $ro_user_arr = [];
        $rw_user_arr = [];
        $owner_user_arr = ['1' => ['user_id' => $resData['owner_user_id'],'type' => 0,'group_name' => '负责人','authority' => '负责人权限']]; //负责人

        //转换为二维数组
        foreach ($ro_user_ids as $k=>$v) {
            $ro_user_arr[$k]['user_id'] = $v;
            $ro_user_arr[$k]['type'] = 1;
            $ro_user_arr[$k]['group_name'] = '普通成员';
            $ro_user_arr[$k]['authority'] = '只读';
        }

        foreach ($rw_user_ids as $k=>$v) {
            $rw_user_arr[$k]['user_id'] = $v;
            $rw_user_arr[$k]['type'] = 2;
            $rw_user_arr[$k]['group_name'] = '普通成员';
            $rw_user_arr[$k]['authority'] = '读写';            
        }        
        
        $user_list = array_merge($owner_user_arr, $rw_user_arr, $ro_user_arr);
        $new_user_list = [];
        foreach ($user_list as $k=>$v) {
            if ($v['user_id']) {
                $userInfo = [];
                $userInfo = $userModel->getUserById($v['user_id']) ? : [];
                $userInfo['group_name'] = $v['group_name'];
                $userInfo['authority'] = $v['authority'];
                $userInfo['type'] = $v['type'];                
                $new_user_list[] = $userInfo;
            }
        }
        return resultArray(['data' => $new_user_list]);      
    } 

    /**
     * 相关团队创建
     * @author Michael_xu
     * @param type 1负责人，2只读，3读写
     * @param user_id 协作人
     * @param types 类型
     * @param is_del 1 移除操作
     * @return 
     */
    public function teamSave()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $settingModel = model('Setting');
        $userModel = new \app\admin\model\User();
        $types_id = $param['types_id'];
        if (!$param['types'] || !$types_id) {
            return resultArray(['error' => '参数错误']);
        }
        if (!$param['user_id']) {
            return resultArray(['error' => '请先选择协作人']);
        }
        $errorMessage = [];
        foreach ($types_id as $k=>$v) {
            $error = false;
            $typesName = '';
            //权限判断
            switch ($param['types']) {
                case 'crm_customer' : 
                    $typesName = '客户';
                    $customerModel = new \app\crm\model\Customer();
                    $dataInfo = db('crm_customer')->where(['customer_id' => $v])->find();
                    //判断权限
                    $auth_user_ids = $userModel->getUserByPer('crm', 'customer', 'teamSave');
                    //判断是否客户池数据
                    $wherePool = $customerModel->getWhereByPool();
                    $resPool = db('crm_customer')->alias('customer')->where(['customer.customer_id' => $v])->where($wherePool)->find();
                    if (!$resPool && !in_array($dataInfo['owner_user_id'],$auth_user_ids)) {
                        $error = true;
                        $errorMessage[] = "客户'".$dataInfo['name']."'操作失败，错误原因：无权操作";
                    }
                    continue;
                case 'crm_business' : 
                    $typesName = '商机';
                    $businessModel = new \app\crm\model\Business();
                    $dataInfo = db('crm_business')->where(['business_id' => $v])->find();
                    //判断权限
                    $auth_user_ids = $userModel->getUserByPer('crm', 'business', 'teamSave');
                    if (!in_array($dataInfo['owner_user_id'],$auth_user_ids)) {
                        $error = true;
                        $errorMessage[] = "商机'".$dataInfo['name']."'操作失败，错误原因：无权操作";
                    }                          
                    continue;
                case 'crm_contract' : 
                    $typesName = '合同';
                    $contractModel = new \app\crm\model\Contract();
                    $dataInfo = db('crm_contract')->where(['contract_id' => $v])->find();
                    //判断权限
                    $auth_user_ids = $userModel->getUserByPer('crm', 'contract', 'teamSave');
                    if (!in_array($dataInfo['owner_user_id'],$auth_user_ids)) {
                        $error = true;
                        $errorMessage[] = "合同'".$dataInfo['name']."'操作失败，错误原因：无权操作";
                    }                         
                    continue;
            }
            if ($error !== true) {
                $param['type_id'] = $v;
                $param['type'] = $param['type'] ? : 1;
                $param['is_del'] = $param['is_del'] ? : 3;
                $param['owner_user_id'] = $userInfo['id'];
                $res = $settingModel->createTeamData($param);
                if (!$res) {
                    $errorMessage[] = $typesName.$dataInfo['name']."'操作失败，错误原因：修改失败";
                }                
            }          
        }
        if ($errorMessage) {
            return resultArray(['error' => $errorMessage]);
        } else {
            return resultArray(['data' => '保存成功']);
        }
    } 

    /**
     * 合同到期提醒天数
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function contractDay()
    {
        $param = $this->param;
        $data = [];
        $contract_day = $param['contract_day'] ? intval($param['contract_day']) : 0; 
        $contract_config = $param['contract_config'] ? intval($param['contract_config']) : 0;
        $res = db('crm_config')->where(['name' => 'contract_config'])->update(['value' => $contract_config]);
        if ($contract_day && $contract_config == 1) $res = db('crm_config')->where(['name' => 'contract_day'])->update(['value' => $contract_day]);
        return resultArray(['data' => '设置成功']);        
    }

    /**
     * 记录类型编辑
     * @author zhi
     * @return
     */
    public function recordEdit()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        //权限判断
        $adminTypes = adminGroupTypes($userInfo['id']);
        if (!in_array(3,$adminTypes) && !in_array(1,$adminTypes) && !in_array(2,$adminTypes)) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        if ($param['value']) {
            $array = json_encode($param['value']);
            $record_type = db('crm_config')->where(['name' => 'record_type'])->find();
            if($record_type){
                $res = db('crm_config')->where(['name' => 'record_type'])->update(['value' => $array]);
            }else{
                $data = array();
                $data['name'] = 'record_type';
                $data['value'] = $array;
                $data['description'] = '跟进记录类型';
                $res = db('crm_config')->insert($data);
            }            
            if ($res) {
                return resultArray(['data' => '设置成功']);
            } else {
                return resultArray(['error' => '设置失败，请重试！']);
            }
        } else {
            $record_type = db('crm_config')->where(['name' => 'record_type'])->find();
            $record_type['value'] = json_decode($record_type['value']);
            return resultArray(['data' => $record_type]);
        }
    }
   
    /**
     * 跟进记录 记录方式展示
     * @author zhi
     * @return 
     */
    public function recordList()
    {
        $record_type = db('crm_config')->where(['name' => 'record_type'])->find();
        if($record_type){
            $arr = json_decode($record_type['value']);
            return resultArray(['data' => $arr]);
        }else{
            return resultArray(['data' => array()]);
        }
    }
}