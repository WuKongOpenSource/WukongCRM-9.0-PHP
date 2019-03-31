<?php
// +----------------------------------------------------------------------
// | Description: CRM工作台
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;

class Index extends ApiCommon
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
            'allow'=>['index','achievementdata','funnel','saletrend']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    } 

    //月份数组
    protected $monthName = [
        '01'    =>  'january',
        '02'    =>  'february',
        '03'    =>  'march',
        '04'    =>  'april',
        '05'    =>  'may',
        '06'    =>  'june',
        '07'    =>  'july',
        '08'    =>  'august',
        '09'    =>  'september',
        '10'    =>  'october',
        '11'    =>  'november',
        '12'    =>  'december',
    ]; 

    /**
     * CRM工作台（销售简报）
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function index()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $userModel = new \app\admin\model\User();
        //员工IDS
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = $param['user_id'];
        } elseif ($param['structure_id']) {
            $map_structure_user_ids = $userModel->getSubUserByStr($param['structure_id']);
            if ($map_user_ids && $map_structure_user_ids) {
                $map_user_ids = array_merge($map_user_ids,$map_structure_user_ids);
            } elseif ($map_structure_user_ids) {
                $map_user_ids = $map_structure_user_ids;
            }
        } else {
           $map_user_ids = [$userInfo['id']]; 
        }
        $perUserIds = getSubUserId(); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集
        $where['owner_user_id'] = array('in',$userIds);        

        $between_time = getTimeByType($param['type']);
        $where['create_time'] = array('between',$between_time);
        $customerNum = 0; //录入客户
        $contactsNum = 0; //新增联系人
        $businessNum = 0; //新增商机
        $businessStatusNum = 0; //阶段变化的商机
        $contractNum = 0; //新增合同
        $recordNum = 0; //新增跟进记录
        $receivablesNum = 0; //新增回款

        $customerNum = db('crm_customer')->where($where)->count('customer_id');
        $contactsNum = db('crm_contacts')->where($where)->count('contacts_id');
        $businessNum = db('crm_business')->where($where)->count('business_id');
        $contractNum = db('crm_contract')->where($where)->count('contract_id');
        $receivablesNum = db('crm_receivables')->where($where)->count('receivables_id');

        unset($where['owner_user_id']);
        $where['create_user_id'] = array('in',$userIds);
        $recordNum = db('admin_record')->where($where)->count('record_id');

        $where['owner_user_id'] = array('in',$userIds);     
        unset($where['create_time']);
        $where['status_time'] = array('between',$between_time);
        $businessStatusNum = db('crm_business')->where($where)->count('business_id');

        $data = [];
        $data['customerNum'] = $customerNum;
        $data['contactsNum'] = $contactsNum;
        $data['businessNum'] = $businessNum;
        $data['contractNum'] = $contractNum;
        $data['recordNum'] = $recordNum;
        $data['receivablesNum'] = $receivablesNum;
        $data['businessStatusNum'] = $businessStatusNum;
        return resultArray(['data' => $data]);  	
    }

    /**
     * 业绩指标
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function achievementData()
    {
        $param = $this->param;
        $userModel = new \app\admin\model\User();
        $userInfo = $this->userInfo;
        $user_id = $param['user_id'] ? : ['-1'];
        $structure_id = $param['structure_id'] ? : ['-1'];
        $where = [];
        //员工IDS
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = $param['user_id'];
        } elseif ($param['structure_id']) {
            $map_structure_user_ids = $userModel->getSubUserByStr($param['structure_id']);
            if ($map_user_ids && $map_structure_user_ids) {
                $map_user_ids = array_merge($map_user_ids,$map_structure_user_ids);
            } elseif ($map_structure_user_ids) {
                $map_user_ids = $map_structure_user_ids;
            }
        } else {
           $map_user_ids = [$userInfo['id']]; 
        }
        $status = $param['status'] ? : 1; //1合同目标2回款目标    

        $perUserIds = getSubUserId(); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : array($userInfo['id']); //数组交集
        $where['owner_user_id'] = array('in',$userIds);

        $start_time = $param['start_time'] ? : strtotime(date('Y-01-01',time()));
        $end_time = $param['end_time'] ? strtotime(date('Y-m-01', $param['end_time']) . ' +1 month -1 day') : strtotime(date('Y-m-01', time()) . ' +1 month -1 day');
        $between_time = array($start_time,$end_time);
        $where['create_time'] = array('between',$between_time);

        //合同金额
        $where_contract = $where;
        $where_contract['check_status'] = 2; //审核通过
        $contractMoney = db('crm_contract')->where($where_contract)->sum('money');

        //回款金额
        $where_receivables = $where;
        $where_receivables['check_status'] = 2; //审核通过
        $receivablesMoney = db('crm_receivables')->where($where_receivables)->sum('money');

        //业绩目标
        $where_achievement = [];
        $where_achievement['status'] = $status;
        //获取时间段包含年份
        $year = getYearByTime($start_time, $end_time);
        $where_achievement['year'] = array('in',$year);
        $where_achievement_str = '(( `obj_id` IN ('.implode(',',$user_id).') AND `type` = 3 ) OR ( `obj_id` IN ('.implode(',',$structure_id).') AND `type` = 2 ) )';
       // p($where_achievement);
        $achievement = db('crm_achievement')->where($where_achievement)->where($where_achievement_str)->select();
      //  p(  db('crm_achievement')->getLastSql() );
        $achievementMoney = 0.00;
        //获取需要查询的月份
        $month = getmonthByTime($start_time, $end_time);
        foreach ($achievement as $k=>$v) {
            foreach ($month as $key=>$val) {
                if ($v['year'] == $key) {
                    foreach ($val as $key1=>$val1) {
                        $achievementMoney += $v[$this->monthName[$val1]];                      
                    }
                } 
            }
        }
        $data = [];
        $data['contractMoney'] = $contractMoney ? : '0.00';
        $data['receivablesMoney'] = $receivablesMoney ? : '0.00';
        $data['achievementMoney'] = $achievementMoney ? : '0.00';
        //完成率
        $rate = 0.00;
        if ($status == 1) {
            $rate = $achievementMoney ? round(($contractMoney/$achievementMoney),4) : 0.00;
        } else {
            $rate = $achievementMoney ? round(($receivablesMoney/$achievementMoney),4) : 0.00;
        }
        $data['rate'] = $rate *100;
        return resultArray(['data' => $data]);
    }

    /**
     * 销售漏斗
     * @author Michael_xu
     * @param 
     * @return
     */
    public function funnel()
    {
        $businessModel = new \app\crm\model\Business();
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $userInfo = $this->userInfo;

        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = $param['user_id'];
        } elseif ($param['structure_id']) {
            $map_structure_user_ids = $userModel->getSubUserByStr($param['structure_id']);
            if ($map_user_ids && $map_structure_user_ids) {
                $map_user_ids = array_merge($map_user_ids,$map_structure_user_ids);
            } elseif ($map_structure_user_ids) {
                $map_user_ids = $map_structure_user_ids;
            }
        } else {
           $map_user_ids = [$userInfo['id']]; 
        }
        unset($param['user_id']);
        unset($param['structure_id']);
        $perUserIds = getSubUserId(); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : array($userInfo['id']); //数组交集  
        $param['userIds'] = $userIds ? : [];        
		$param['end_time'] = $param['end_time']+3600*24;
        $list = $businessModel->getFunnel($param);
        return resultArray(['data' => $list]);
    }    

    //销售趋势
    public function saletrend()
    {
        $receivablesModel = new \app\crm\model\Receivables();
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $userInfo = $this->userInfo;

        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = $param['user_id'];
        } elseif ($param['structure_id']) {
            $map_structure_user_ids = $userModel->getSubUserByStr($param['structure_id']);
            if ($map_user_ids && $map_structure_user_ids) {
                $map_user_ids = array_merge($map_user_ids,$map_structure_user_ids);
            } elseif ($map_structure_user_ids) {
                $map_user_ids = $map_structure_user_ids;
            }
        } else {
           $map_user_ids = [$userInfo['id']]; 
        }

        $perUserIds = getSubUserId(); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : array($userInfo['id']); //数组交集

        $year = $param['year'];
        $chartParam['year'] = $year;
        $chartParam['userIds'] = $userIds ? : [];
        $chartList = $receivablesModel->getStatistics($chartParam); //柱状图
        return resultArray(['data' => $chartList]);
    }

    /**
     * 回款计划提醒
     * @author Michael_xu
     * @param day 最近7天 15天...
     * @return 
     */
    public function receivablesPlan()
    {
        $param = $this->param;
        $where = [];
        //员工IDS
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids[] = $param['user_id'];
        } elseif ($param['structure_id']) {
            $map_user_ids = $userModel->getSubUserByStr($param['structure_id']);
        }

        $perUserIds = $userModel->getUserByPer(); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : array($userInfo['id']); //数组交集
        $where['owner_user_id'] = array('in',$userIds);        

        //已逾期
        $return_date = array('< time',date('Y-m-d',time()));
        $where['status'] = 0;
        if ($param['day']) {
            $return_date = array('between time',array(date('Y-m-d',time()),date('Y-m-d',strtotime(date('Y-m-d',time()))+86399+(86400*(int)$param['day']))));
        }
        $where['return_date'] = $return_date;
        $planList = db('crm_receivables_plan')->where($where)->select();

        return resultArray(['data' => $planList]);
    }

    /**
     * 待跟进客户
     * @author Michael_xu
     * @param day 最近3天 7天...
     * @return 
     */
    public function noFollowUp()
    {
        $param = $this->param;
        $where = [];
        //员工IDS
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids[] = $param['user_id'];
        } elseif ($param['structure_id']) {
            $map_user_ids = $userModel->getSubUserByStr($param['structure_id']);
        }

        $perUserIds = $userModel->getUserByPer(); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : array($userInfo['id']); //数组交集
        $where['owner_user_id'] = array('in',$userIds);        

        $day = (int)$param['day'] ? : 3;
        $where['next_time'] = array('between',array(strtotime(date('Y-m-d',time())),strtotime(date('Y-m-d',time()))+86399+(86400*(int)$param['day'])));
        $customerList = db('crm_customer')->where($where)->select();
        return resultArray(['data' => $customerList]);
    }          
}