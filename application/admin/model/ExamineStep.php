<?php
// +----------------------------------------------------------------------
// | Description: 审批步骤
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class ExamineStep extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'admin_examine_step';

    /**
     * 获取有效审批步骤列表
     * @param  flow_id 审批流程ID
     * @param  user_id 审批申请人ID
     * @return
     */ 
    public function getDataList($flow_id)
    {
        $userModel = new \app\admin\model\User();
        $list = $this->where(['flow_id' => $flow_id])->order('order_id asc')->select();
        foreach ($list as $k=>$v) {
            $list[$k]['user_id_info'] = $userModel->getListByStr($v['user_id']);
        }
        return $list ? : [];
    }   

	/**
     * 审批步骤(创建、编辑)
     * @param  flow_id 审批流程ID
     * @param status 1负责人主管，2指定用户（任意一人），3指定用户（多人会签），4上一级审批人主管
     * @return 
     */
    public function createStepData($data, $flow_id)
    {
    	if (!intval($flow_id)) {
			$this->error = '审批流程创建失败';
			return false;    		
    	}
        //处理数据
        $resSuccess = true;
        $dataStep = [];
        foreach ($data as $k=>$v) {
            if (!intval($v['status']) || (in_array($v['status'],[2,3]) && !$v['user_id'])) {
                $resSuccess = false;
            }
            $dataStep[$k]['relation'] = 1;
            if (in_array($v['status'],[2,3])) {
                $dataStep[$k]['user_id'] = $v['user_id'] ? arrayToString($v['user_id']) : ''; //处理user_id
                $dataStep[$k]['relation'] = ($v['status'] == 3) ? 1 : 2;
            }
            if ($v['step']) {
                $dataStep[$k]['step_id'] = $v['step'];
            }
            $dataStep[$k]['order_id'] = $k+1;
            $dataStep[$k]['flow_id'] = $flow_id;
            $dataStep[$k]['status'] = $v['status'];
            $dataStep[$k]['create_time'] = time();    
        }
        if ($resSuccess) {
            //提交事务
            $this->startTrans();
            try {
                $this->where(['flow_id' => $flow_id])->delete();
                $this->saveAll($dataStep);
                $this->commit();
                return true;                    
            } catch(\Exception $e) {
                $this->error = '审批步骤创建失败';
                $this->rollback();
                return false;
            }               
        } else {
            $this->error = '参数错误';
            return false;          
        }        
    }

	/**
     * 审批步骤(排序，防止位置情况造成排序错乱)
     * @param  flow_id 审批流程ID
     * @return 
     */ 
    public function orderData($flow_id)
    {
    	$step_list = db('admin_examine_step')->where(['flow_id' => $flow_id])->order('order_id')->select();
    	foreach ($step_list as $k=>$v) {
    		$data = [];
    		$data = ['step_id' => $v['step_id'],'order_id' => $k];
    		db('admin_examine_step')->update($data);
    	}
    }        

	/**
     * 下一审批人(审批是否结束)
     * @param  user_id  审批申请人ID
     * @param  flow_id  审批流ID
     * @param  types 关联对象
     * @param  types_id 联对象ID
     * @param  order_id 审批排序ID
     * @param status 1负责人主管，2指定用户（任意一人），3指定用户（多人会签），4上一级审批人主管
     * @param  check_user_id 当前审核人ID
     */ 
    public function nextStepUser($user_id, $flow_id, $types, $types_id, $order_id, $check_user_id)
    {
        $res = nextCheckData($user_id, $flow_id, $types, $types_id, $order_id, $check_user_id);
        return $res ? : [];
    }

	/**
     * 审批步骤权限
     * @param  step_id 审批步骤ID
     * @param  user_id 审批人ID（当前登录人）
     * @param  create_user_id 申请人ID
     * @param  types 关联对象
     * @param  types_id 联对象ID
     * @param  status 1负责人主管，2指定用户（任意一人），3指定用户（多人会签），4上一级审批人主管
     * @return 
     */ 
    public function checkExamine($user_id, $types, $types_id)
    {
        $data = $this->getDataByTypes($types, $types_id);
        $dataInfo = $data['dataInfo']; //审批主体信息
        $stepInfo = $data['stepInfo']; //审批步骤信息
        if (!$dataInfo) {
            $this->error = '参数错误！';
            return false;            
        }
        if (in_array($dataInfo['check_status'], ['2','3'])) {
            $this->error = '审批已经结束！';
            return false;
        }        
        if ($dataInfo['flow_id'] > 0) {
            //固定流程
            //当前步骤已审批user_id
            $check_user_ids = $this->getUserByCheck($types, $types_id, $dataInfo['order_id']);
            if (in_array($user_id, $check_user_ids)) {
                $this->error = '您已审核，请勿重复操作！';
                return false;            
            }
            $examine_user_id_arr = array();
            // $examine_user_id_arr = $this->getUserByStep($stepInfo['step_id'], $dataInfo['create_user_id']); //获取审批步骤审批人
            $examine_user_id_arr = $dataInfo['check_user_id']; //获取审批步骤审批人
            $examine_user_id_arr = stringToArray($examine_user_id_arr);
        } else {
            $examine_user_id_arr = $this->getUserByPer($types);
        }
    	if (!in_array($user_id, $examine_user_id_arr)) { 		
			$this->error = '没有权限';
  			return false;   		
    	}
        return true;
    }

	/**
     * 审批对象获取审批相关信息
     * @param  types 关联对象
     * @param  types_id 联对象ID
	 * @return
     */   
    public function getDataByTypes($types, $types_id)
    {
    	if (empty($types) || empty($types_id)) {
    		$this->error = '参数错误';
    		return false;
    	}

    	switch (trim($types)) {
    		case 'oa_examine' : $dataInfo = db('oa_examine')->where(['examine_id' => intval($types_id)])->field('create_user_id,check_user_id,flow_id,order_id,check_status,update_time')->find(); break;
            case 'crm_contract' : $dataInfo = db('crm_contract')->where(['contract_id' => intval($types_id)])->field('create_user_id,owner_user_id,check_user_id,flow_id,order_id,check_status,update_time')->find(); break;
            case 'crm_receivables' : $dataInfo = db('crm_receivables')->where(['receivables_id' => intval($types_id)])->field('create_user_id,owner_user_id,check_user_id,flow_id,order_id,check_status,update_time')->find(); break;
    	}
        $stepInfo = [];
        if ($dataInfo['flow_id'] && !in_array($dataInfo['check_status'],['5'])) {
            //固定审批流
            $stepInfo = db('admin_examine_step')->where(['flow_id' => $dataInfo['flow_id'],'order_id' => $dataInfo['order_id']])->find();
        }
    	$data = [];
    	$data['stepInfo'] = $stepInfo;
    	$data['step_id'] = $stepInfo['step_id'] ? : '';
    	$data['dataInfo'] = $dataInfo;
    	return $data;
    } 

    /**
     * 获取审批步骤审批人信息
     * @param  step_id 审批步骤ID
     * @param  status 1负责人主管，2指定用户（任意一人），3指定用户（多人会签），4上一级审批人主管
     * @param  user_id  审批主体，申请人user_id
     * @return
     */   
    public function getUserByStep($step_id, $user_id)
    {
        $stepInfo = db('admin_examine_step')->where(['step_id' => $step_id])->find();
        $examine_user_id_arr = [];
        //固定审批流
        switch ($stepInfo['status']) {
            case 1 :
                $examine_user_id = db('admin_user')->where(['id' => $user_id])->value('parent_id');
                if ($examine_user_id) {
                    $examine_user_id_arr[] = $examine_user_id;
                } else {
                    $examine_user_id_arr[] = 1;
                }
                break;
            case 2 : 
            case 3 :$examine_user_id_arr = stringToArray($stepInfo['user_id']); break;
            case 4 : 
                $order_id = $stepInfo['order_id'] ? $stepInfo['order_id']-1 : 0;
                $last_step_id = db('admin_examine_step')->where(['flow_id' => $stepInfo['flow_id'],'order_id' => $order_id])->value('step_id');
                $last_step_info = db('admin_examine_step')->where(['step_id' => $last_step_id])->find();
                $last_user_id = $this->getUserByStep($last_step_id, $user_id);
                if (count(stringToArray($last_user_id)) !== 1) {
                    $this->error = '审批流程出错';
                    return false;
                }
                $last_user_id_arr = stringToArray($last_user_id);
                $examine_user_id = $this->getUserByStep($last_step_id, $last_user_id_arr[0]);
                //$examine_user_id = db('admin_user')->where(['id' => $last_step_info['user_id']])->value('parent_id');
                $examine_user_id_arr = [];
                if ($examine_user_id) {
                    $examine_user_id_arr = stringToArray($examine_user_id);
                }       
                break;
            default : $examine_user_id_arr = [];
        }
        return array_unique($examine_user_id_arr) ? ','.implode(',',array_filter(array_unique($examine_user_id_arr))).',' : '';
    } 

    /**
     * 获取当前步骤已审批的user_id
     * @param  step_id 审批步骤ID
     * @param  status 1审核通过0审核失败2撤销 
     * @return
     */
    public function getUserByCheck($types, $types_id = 0, $order_id = 0, $status = 1)
    {
        if ($types_id == 0 && $order_id == 0) {
            $check_user_ids = [];
        } else {
            $check_user_ids = db('admin_examine_record')->where(['types' => $types,'types_id' => $types_id,'order_id' => $order_id,'is_end' => 0,'status' => $status])->column('check_user_id');
        }
        return $check_user_ids ? : [];   
    } 

    /**
     * 获取授权审批的user_id
     * @param  step_id 审批步骤ID
     * @param 
     * @return
     */ 
    public function getUserByPer($types)
    {
        if (!in_array($types,['oa_examine','crm_contract','crm_receivables'])) {
            $this->error = '参数错误';
            return false;
        }
        $userModel = new \app\admin\model\User();
        $adminUserId = model('User')->getAdminId(); //管理员ID
        //获取有审核权限的user_id
        switch ($types) {
            case 'oa_examine' : $examine_user_id_arr = $userModel->getUserByPer('oa', 'examine', 'check'); break;
            case 'crm_contract' : $examine_user_id_arr = $userModel->getUserByPer('crm', 'contract', 'check'); break;
            case 'crm_receivables' : $examine_user_id_arr = $userModel->getUserByPer('crm', 'receivables', 'check'); break;
        }
        $examine_user_id_arr = $examine_user_id_arr ? array_merge($examine_user_id_arr, $adminUserId) : $adminUserId;
        return $examine_user_id_arr;
    }  

    /**
     * 获取审批步骤相关userId
     * @param  flow_id 流程ID
     * @param  order_id 排序ID
     * @return
     */
    public function getStepUserByOrder($flow_id, $order_id, $user_id)
    {
        $user_ids = [];
        if ($flow_id && $order_id) {
            $stepInfo = db('admin_examine_step')->where(['flow_id' => $flow_id,'order_id' => $order_id])->find();
            $user_ids = $this->getUserByStep($stepInfo['step_id'], $user_id);
        }
        return $user_ids ? : [];
    }

    /**
     * 获取有效审批步骤列表(固定审批)
     * @param  flow_id 审批流程ID
     * @param  user_id 审批申请人ID
     * @param  check_user_id 当前操作人ID
     * @return
     */ 
    public function getStepList($flow_id, $user_id, $types, $types_id = 0, $check_user_id = 0, $action = '', $category_id = '')
    {
        $userModel = new \app\admin\model\User();
        $newlist = [];
        
        $dataInfo['order_id'] = 0;
        if ($types_id) {
            $typesInfo = $this->getDataByTypes($types, $types_id);
            $dataInfo = $typesInfo['dataInfo'];
        }
        $is_check = 0; //审批权限(1有)
        $is_recheck = 0; //撤销审批权限(1有)

        $admin_user_ids = $userModel->getAdminId();
        //创建人或负责人或管理员有撤销权限
        if ($dataInfo['create_user_id'] == $check_user_id || $dataInfo['owner_user_id'] == $check_user_id || in_array($check_user_id, $admin_user_ids)) {
            if (!in_array($dataInfo['check_status'],['2','3','4'])) {
                $is_recheck = 1;
            }
        }
        if (in_array($check_user_id, stringToArray($dataInfo['check_user_id'])) && !in_array($dataInfo['check_status'],['2','3'])) {
            $is_check = 1;
        }

        if ($action == 'view') {
            $createUserInfo = $userModel->getUserById($dataInfo['create_user_id']);
            $createUserInfo['check_time'] = $dataInfo['update_time'];
            if ($dataInfo['check_status'] == 4) {
                $createUserInfo['check_type'] = 2;
                $newlist[0]['type'] = '2'; //撤销
            } else {
                $createUserInfo['check_type'] = 3;
                $newlist[0]['type'] = '3'; //创建
            }
            $newlist[0]['user_id_info'] = array($createUserInfo);
            $newlist[0]['time'] = $dataInfo['update_time'];
        }
        $stepList = [];
        if ($dataInfo['check_status'] !== 4 || $action !== 'view') {
            $list = db('admin_examine_step')->where(['flow_id' => $flow_id])->order('order_id asc')->select();
            $is_break = false;
            foreach ($list as $k=>$v) {
                $type = 4;
                $examine_user_ids = '';
                //判断步骤审批人是否存在
                $examine_user_ids = $this->getUserByStep($v['step_id'], $user_id);
                $examine_user_arr = stringToArray($examine_user_ids);
                if ($examine_user_arr) {
                    $newStepInfo = $v;
                    $user_id_info_arr = [];
                    foreach ($examine_user_arr as $key=>$val) {
                        $user_id_info = [];
                        $user_id_info = $userModel->getUserById($val);
                        $check_type = 4; //type 0失败，1通过，2撤销，3创建，4待审核，5未提交
                        //当前步骤已审批user_id
                        $check_user_ids = [];
                        $check_user_ids = $this->getUserByCheck($types, $types_id, $v['order_id'], 1);
                        if (in_array($val, $check_user_ids)) {
                            $check_type = 1;
                            $type = 1;
                        }
                        $re_check_user_ids = $this->getUserByCheck($types, $types_id, $v['order_id'], 2); //撤销人员
                        if ($dataInfo['check_status'] == 4) {
                            if ($re_check_user_ids) {
                                $is_break = true;
                                $check_type = 2;
                                $type = 2;
                            }
                        }
                        $fail_check_user_ids = $this->getUserByCheck($types, $types_id, $v['order_id'], 0); //拒绝人员
                        if ($dataInfo['check_status'] == 3) {
                            if (in_array($val,$fail_check_user_ids)) {
                                $is_break = true;
                                $check_type = 0;
                                $type = 0;
                            }
                            //if ($action == 'view') break;
                        }
                        $user_id_info['check_type'] = $check_type;
                        $check_time = '';
                        $check_time = db('admin_examine_record')->where(['types' => $types,'types_id' => $types_id,'flow_id' => $flow_id,'order_id' => $v['order_id'],'check_user_id' => $val,'is_end' =>0])->value('check_time');
                        $user_id_info['check_time'] = $check_time ? : '';
                        $user_id_info_arr[] = $user_id_info;
                    }
                    $newStepInfo['user_id'] = $examine_user_ids;
                    $newStepInfo['user_id_info'] = $user_id_info_arr;
                    if ($dataInfo['order_id'] > $v['order_id']) {
                        $type = 1;
                    }
                    //if ($is_break !== false) break; 
                    $newStepInfo['type'] = $type;          
                    $stepList[] = $newStepInfo;
                }
            }            
        }
        $newStepList = [];
        if ($newlist && $stepList) {
            $newStepList = array_merge($newlist, $stepList);
        } elseif ($stepList) {
            $newStepList = $stepList;
        } else {
            $newStepList = $newlist;
        }
        $data['steplist'] = $newStepList ? : [];
        $data['is_check'] = $is_check;
        $data['is_recheck'] = $is_recheck;
        return $data ? : [];
    }

    /**
     * 根据order_id获取审批步骤
     * @param  flow_id 审批流程ID
     * @param  order_id 审批排序ID
     * @return
     */ 
    public function getStepByOrder($flow_id, $order_id)
    {
        $data = db('admin_examine_step')->where(['flow_id' => $flow_id,'order_id' => $order_id])->find();
        return $data ? : [];
    } 

    /**
     * 获取有效审批步骤列表(自选审批)
     * @param  types 类型
     * @param  types_id 类型ID
     * @param  action 操作类型: view、save
     * @return
     */ 
    public function getPerStepList($types, $types_id, $user_id, $check_user_id, $action = '')
    {
        $examineRecordModel = new \app\admin\model\ExamineRecord();
        $userModel = new \app\admin\model\User();
        $userList = [];
        //有效的审批记录
        $where = [];
        $where['types'] = $types;
        $where['types_id'] = $types_id;
        $where['is_end'] = 0;
        $recordList = $examineRecordModel->getDataList($where);
    
        $typeInfo = $this->getDataByTypes($types, $types_id);
        $dataInfo = $typeInfo['dataInfo'];
        $createUserInfo = $userModel->getUserById($dataInfo['create_user_id']);
        $userList[0]['userInfo'] = $createUserInfo;
        $userList[0]['type'] = '3'; //创建
        $userList[0]['time'] = $dataInfo['update_time'] ? : '';
 
        //type 0失败，1通过，2撤销，3创建，4待审核，5未提交
        $i = 1;
        foreach ($recordList as $k=>$v) {
            $userList[$i]['userInfo'] = $userModel->getUserById($v['check_user_id']);
            $userList[$i]['type'] = $v['status'];
            $userList[$i]['time'] = $v['check_time'];
            $i++;
        }
        if ($dataInfo['check_status'] <= 1 && $dataInfo['check_user_id']) {
            $check_user_id_arr = stringToArray($dataInfo['check_user_id']);
            $userList[$i]['userInfo'] = $userModel->getUserById($check_user_id_arr[0]);
            $userList[$i]['type'] = '4';
        }
        if ($dataInfo['check_status'] == 5 && $dataInfo['check_user_id']) {
            $userList = [];
            $check_user_id_arr = stringToArray($dataInfo['check_user_id']);
            $userList[0]['userInfo'] = $userModel->getUserById($check_user_id_arr[0]);
            $userList[0]['type'] = '5';
        }        
        $is_check = 0; //审批权限(1有)
        $is_recheck = 0; //撤销审批权限(1有)

        $admin_user_ids = $userModel->getAdminId();
        //创建人或负责人或管理员有撤销权限
        if ($dataInfo['create_user_id'] == $check_user_id || $dataInfo['owner_user_id'] == $check_user_id || in_array($check_user_id, $admin_user_ids)) {
            if (!in_array($dataInfo['check_status'],['2','3','4','5'])) {
                $is_recheck = 1;
            }
        }
        if (in_array($check_user_id, stringToArray($dataInfo['check_user_id'])) && !in_array($dataInfo['check_status'],['2','3','5'])) {
            $is_check = 1;
        }

        $data['steplist'] = $userList;
        $data['is_check'] = $is_check;
        $data['is_recheck'] = $is_recheck;
        return $data ? : [];
    }           
}