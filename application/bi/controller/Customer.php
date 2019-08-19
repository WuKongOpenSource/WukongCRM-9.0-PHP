<?php
// +----------------------------------------------------------------------
// | Description: 商业智能-客户分析
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\bi\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;
use think\Db;

class Customer extends ApiCommon
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
            'allow'=>['statistics','total','recordtimes','recordlist','recordmode','conversion','conversioninfo','pool','poollist','usercycle','productcycle','addresscycle','addressanalyse','portrait']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 员工客户分析
     * @author Michael_xu
     * @param 
     * @return
     */
    public function statistics()
    {
        if (!checkPerByAction('bi', 'customer' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }        
        $customerModel = new \app\crm\model\Customer();
        $param = $this->param;
        if ($param['type']) {
            $timeArr = getTimeByType($param['type']);
            $param['start_time'] = $timeArr[0];
            $param['end_time'] = $timeArr[1];
        }
        $list = $customerModel->getStatistics($param);
        return resultArray(['data' => $list]);
    }

    /**
     * 员工客户总量分析
     * @author 
     * @param 
     * @return
     */
    public function total()
    {
        if (!checkPerByAction('bi', 'customer' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        $customerModel = new \app\crm\model\Customer();
        $userModel = new \app\admin\model\User();
        $biCustomerModel = new \app\bi\model\Customer();
        $param = $this->param;
        if(empty($param['type']) && empty($param['start_time'])){
            $param['type'] = 'month';
        }
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = array($param['user_id']);
        } else {
            if ($param['structure_id']) {
                $map_user_ids = $userModel->getSubUserByStr($param['structure_id'], 2);
            }
        }
        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集

        $company = $biCustomerModel->getParamByCompany($param);
        $datas = array();
        for ($i=1; $i <= $company['j']; $i++) { 
            $whereArr = [];
            $whereArr['create_user_id'] = array('in',$userIds);
            $item = array();
            //时间段
            $timeArr = $biCustomerModel->getStartAndEnd($param,$company['year'],$i);
            $item['type'] = $timeArr['type'];
            $day = $timeArr['day']?$timeArr['day']:'1';
            $start_time = strtotime($timeArr['year'].'-'.$timeArr['month'].'-'.$day);
            $next_day = $timeArr['next_day']?$timeArr['next_day']:'1';
            $end_time = strtotime($timeArr['next_year'].'-'.$timeArr['next_month'].'-'.$next_day);
            $create_time = [];
            if ($start_time && $end_time) {
                $create_time = array('between',array($start_time,$end_time));
            }
            $whereArr['create_time'] = $create_time;
            $item['customer_num'] = $customerModel->getDataCount($whereArr);
            $whereArr['deal_status'] = '已成交';
            $item['deal_customer_num'] = $customerModel->getDataCount($whereArr);
            $item['start_time'] = $start_time;
            $item['end_time'] = $end_time;
            $datas[] = $item;
        }
        return resultArray(['data' => $datas]);
    }

    /**
     * 员工客户跟进次数分析
     * @author 
     * @param 
     * @return
     */
    public function recordTimes()
    {
        if (!checkPerByAction('bi', 'customer' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        $biCustomerModel = new \app\bi\model\Customer();
        $biRecordModel = new \app\bi\model\Record();
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        
        if(empty($param['type']) && empty($param['start_time'])){
            $param['type'] = 'month';
        }
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = array($param['user_id']);
        } else {
            if ($param['structure_id']) {
                $map_user_ids = $userModel->getSubUserByStr($param['structure_id'], 2);
            }
        }
        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集
        
        $company = $biCustomerModel->getParamByCompany($param);
        $datas = array();
        for ($i=1; $i <= $company['j']; $i++) { 
            $whereArr = [];
            $whereArr['create_user_id'] = array('in',$userIds);
            $item = array();
            //时间段
            $timeArr = $biCustomerModel->getStartAndEnd($param,$company['year'],$i);
            $item['type'] = $timeArr['type'];
            $day = $timeArr['day']?$timeArr['day']:'1';
            $start_time = strtotime($timeArr['year'].'-'.$timeArr['month'].'-'.$day);
            $next_day = $timeArr['next_day']?$timeArr['next_day']:'1';
            $end_time = strtotime($timeArr['next_year'].'-'.$timeArr['next_month'].'-'.$next_day);
            $create_time = [];
            if ($start_time && $end_time) {
                $create_time = array('between',array($start_time,$end_time));
            }
            $whereArr['create_time'] = $create_time;
            /*跟进次数*/
            $item['dataCount'] = $biRecordModel->getRecordNum($whereArr);
            /*跟进客户数*/
            $item['customerCount'] = $biRecordModel->getCustomerNum($whereArr);
            $item['start_time'] = $start_time;
            $item['end_time'] = $end_time;
            $datas[] = $item;
        }
        return resultArray(['data' => $datas]);
    }

    /**
     * 员工客户跟进次数分析 具体员工列表
     * @author 
     * @param 
     * @return
     */
    public function recordList()
    {
        if (!checkPerByAction('bi', 'customer' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        $biRecordModel = new \app\bi\model\Record();
        $param = $this->param;
        if ($param['type']) {
            $timeArr = getTimeByType($param['type']);
            $param['start_time'] = $timeArr[0];
            $param['end_time'] = $timeArr[1];
        }        
        $list = $biRecordModel->getDataList($param);
        return resultArray(['data' => $list]);
    }

    /**
     * 员工跟进方式分析
     * @author 
     * @param 
     * @return
     */
    public function recordMode()
    {
        if (!checkPerByAction('bi', 'customer' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        $biCustomerModel = new \app\bi\model\Customer();
        $biRecordModel = new \app\bi\model\Record();
        $param = $this->param;
        $whereArr = array();
        $whereArr = $biCustomerModel->getParamByWhere($param,'record');
        //跟进类型
        $record_type = db('crm_config')->where(['name' => 'record_type'])->find();
        if ($record_type) {
            $record_categorys = json_decode($record_type['value']);        
        } else {
            $record_categorys = array('打电话','发邮件','发短信','见面拜访','活动');
        }
        $count = $biRecordModel->getRecordNum($whereArr);

        $datas = array();
        foreach ($record_categorys as $key => $value) {
            $item = array();
            $whereArr['category'] = $value;
            $item['category'] = $value;
            $item['recordNum'] = $allCustomer = $biRecordModel->getRecordNum($whereArr);
            if(empty($allCustomer) || empty($count)){
                $item['proportion'] = 0;
            }else{
                $item['proportion'] = round(($allCustomer/$count),4)*100;
            }
            $datas[] = $item;
        }
        return resultArray(['data' => $datas]);
    }

    /**
     * 客户转化率分析
     * @author 
     * @param 
     * @return
     */
    public function conversion()
    {
        if (!checkPerByAction('bi', 'customer' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        $customerModel = new \app\crm\model\Customer();
        $userModel = new \app\admin\model\User();
        $biCustomerModel = new \app\bi\model\Customer();
        $param = $this->param;
        
        if(empty($param['type']) && empty($param['start_time'])){
            $param['type'] = 'month';
        }
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = array($param['user_id']);
        } else {
            if ($param['structure_id']) {
                $map_user_ids = $userModel->getSubUserByStr($param['structure_id'], 2);
            }
        }
        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集

        $company = $biCustomerModel->getParamByCompany($param);
        $datas = array();
        for ($i=1; $i <= $company['j']; $i++) { 
            $whereArr = [];
            $whereArr['create_user_id'] = array('in',$userIds);
            $item = array();
            //时间段
            $timeArr = $biCustomerModel->getStartAndEnd($param,$company['year'],$i);
            $item['type'] = $timeArr['type'];
            $day = $timeArr['day']?$timeArr['day']:'1';
            $start_time = strtotime($timeArr['year'].'-'.$timeArr['month'].'-'.$day);
            $next_day = $timeArr['next_day']?$timeArr['next_day']:'1';
            $end_time = strtotime($timeArr['next_year'].'-'.$timeArr['next_month'].'-'.$next_day);
            $create_time = [];
            if ($start_time && $end_time) {
                $create_time = array('between',array($start_time,$end_time));
            }
            $whereArr['create_time'] = $create_time;
            $item['customer_num'] = $customer_num = $customerModel->getDataCount($whereArr);
            $whereArr['deal_status'] = '已成交';
            $item['deal_customer_num'] = $deal_customer_num = $customerModel->getDataCount($whereArr);
            if ($customer_num== 0 || $deal_customer_num == 0) {
                $item['proportion'] = 0;
            } else {
                $item['proportion'] = round(($item['deal_customer_num']/$item['customer_num']),4)*100;
            }
            $item['start_time'] = $start_time;
            $item['end_time'] = $end_time;
            $datas[] = $item;
        }
        return resultArray(['data' => $datas]);
    }

    /**
     * 客户转化率分析具体数据
     * @return [type] [description]
     */
    public function conversionInfo()
    {
        if (!checkPerByAction('bi', 'customer' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        $customerModel = new \app\bi\model\Customer();
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $whereArr = $customerModel->getParamByWhere($param);
        $whereArr['deal_status'] = array('eq','已成交');
        $list = $customerModel->getWhereByList($whereArr);
        return resultArray(['data' => $list]);
    }

    /**
     * 公海客户分析
     * @return [type] [description]
     */
    public function pool()
    {
        if (!checkPerByAction('bi', 'customer' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        $actionRecordModel = new \app\bi\model\ActionRecord();
        $userModel = new \app\admin\model\User();
        $biCustomerModel = new \app\bi\model\Customer();
        $param = $this->param;
        
        if (empty($param['type']) && empty($param['start_time'])) {
            $param['type'] = 'month';
        }
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = array($param['user_id']);
        } else {
            if ($param['structure_id']) {
                $map_user_ids = $userModel->getSubUserByStr($param['structure_id'], 2);
            }
        }
        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集

        $company = $biCustomerModel->getParamByCompany($param);
        $datas = array();
        for ($i=1; $i <= $company['j']; $i++) { 
            $whereArr = [];
            $whereArr['user_id'] = array('in',$userIds);
            $item = array();
            //时间段
            $timeArr = $biCustomerModel->getStartAndEnd($param,$company['year'],$i);
            $item['type'] = $timeArr['type'];
            $day = $timeArr['day']?$timeArr['day']:'1';
            $start_time = strtotime($timeArr['year'].'-'.$timeArr['month'].'-'.$day);
            $next_day = $timeArr['next_day']?$timeArr['next_day']:'1';
            $end_time = strtotime($timeArr['next_year'].'-'.$timeArr['next_month'].'-'.$next_day);
            $create_time = [];
            if ($start_time && $end_time) {
                $create_time = array('between',array($start_time,$end_time));
            }
            $whereArr['create_time'] = $create_time;
            $whereArr['content'] = '将客户放入公海';
            $item['put_in'] = $actionRecordModel->getDataList($whereArr);
            $whereArr['content'] = '领取了客户';
            $item['receive'] = $actionRecordModel->getDataList($whereArr);
            $item['start_time'] = $start_time;
            $item['end_time'] = $end_time;
            $datas[] = $item;
        }
        return resultArray(['data' => $datas]);
    }

    /**
     * 公海客户分析 具体列表
     * @return [type] [description]
     */
    public function poolList()
    {
        if (!checkPerByAction('bi', 'customer' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        $param = $this->param;
        $userModel = new \app\admin\model\User();
        $actionRecordModel = new \app\bi\model\ActionRecord();
        $customerModel = new \app\crm\model\Customer();
        $structureModel = new \app\admin\model\Structure();
        //员工IDS
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = array($param['user_id']);
        } else {
            if ($param['structure_id']) {
                $map_user_ids = $userModel->getSubUserByStr($param['structure_id'], 2);
            }
        }
        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集
        $where['id'] = array('in',$userIds);
        $where['type'] = 1;
        $userList = db('admin_user')->where($where)->field('id,username,structure_id,realname')->select();
        if ($param['type']) {
            $timeArr = getTimeByType($param['type']);
            $param['start_time'] = $timeArr[0];
            $param['end_time'] = $timeArr[1];
        }        
        if ($param['start_time'] && $param['end_time']) {
            $create_time = array('between',array($param['start_time'],$param['end_time']));
        }
        $whereArr['create_time'] = $create_time;
        foreach ($userList as $k=>$v) {
            $structure_info = $structureModel->getDataByID($v['structure_id']);
            $customer_num = 0;
            $whereArr['user_id'] = $v['id'];
            $whereArr['content'] = '将客户放入公海';
            $userList[$k]['put_in'] = $actionRecordModel->getDataList($whereArr);
            $whereArr['content'] = '领取了客户';
            $userList[$k]['receive'] = $actionRecordModel->getDataList($whereArr);

            $where_c['create_time'] = $create_time;
            $where_c['owner_user_id'] = $v['id'];
            $customer_num = $customerModel->getDataCount($where_c);
            $userList[$k]['customer_num'] = $customer_num;
            $userList[$k]['username'] = $structure_info['name'];
        }
        return resultArray(['data' => $userList]);
    }

    /**
     * 员工客户成交周期 
     * @return [type] [description]
     */
    public function userCycle()
    {
        if (!checkPerByAction('bi', 'customer' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        $customerModel = new \app\crm\model\Customer();
        $userModel = new \app\admin\model\User();
        $biCustomerModel = new \app\bi\model\Customer();
        $param = $this->param;
        if(empty($param['type']) && empty($param['start_time'])){
            $param['type'] = 'month';
        }
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = array($param['user_id']);
        } else {
            if ($param['structure_id']) {
                $map_user_ids = $userModel->getSubUserByStr($param['structure_id'], 2);
            }
        }
        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集
        $company = $biCustomerModel->getParamByCompany($param);
        $datas = array();
        for ($i=1; $i <= $company['j']; $i++) { 
            $whereArr['owner_user_id'] = array('in',$userIds);
            $item = array();
            //时间段
            $timeArr = $biCustomerModel->getStartAndEnd($param,$company['year'],$i);
            $item['type'] = $timeArr['type'];
            $day = $timeArr['day']?$timeArr['day']:'1';
            $start_time = strtotime($timeArr['year'].'-'.$timeArr['month'].'-'.$day);
            $next_day = $timeArr['next_day']?$timeArr['next_day']:'1';
            $end_time = strtotime($timeArr['next_year'].'-'.$timeArr['next_month'].'-'.$next_day);
            $create_time = [];
            if ($start_time && $end_time) {
                $create_time = array('between',array($start_time,$end_time));
            }
            $whereArr['create_time'] = $create_time;
            $whereArr['deal_status'] = '已成交';
            $item['customer_num'] = $customerModel->getDataCount($whereArr);
            //周期
            $cycle = $biCustomerModel->getWhereByCycle($whereArr);
            $item['cycle'] = $cycle ? $cycle : 0;
            $item['start_time'] = $start_time;
            $item['end_time'] = $end_time;
            $datas['items'][] = $item;
        }
        $where['id'] = array('in',$userIds);
        $where['type'] = 1;
        $userList = db('admin_user')->where($where)->field('id,username,realname')->select();
        $create_time = [];
        if (!empty($param['start_time'])) {
            $where_c['create_time'] = array('between',array($param['start_time'],$param['end_time']));
        } else {
            $create_time = getTimeByType($param['type']);
            if ($create_time) {
                $where_c['create_time'] = array('between',array($create_time[0],$create_time[1]));
            }
        }
        foreach ($userList as $k=>$v) {
            $customer_num = 0;
            $where_c['owner_user_id'] = array('eq',$v['id']);
            $where_c['deal_status'] = '已成交';
            $customer_num = $customerModel->getDataCount($where_c);
            $customer_cycle = $biCustomerModel->getWhereByCycle($where_c);
            $userList[$k]['customer_num'] = $customer_num;
            $userList[$k]['cycle'] = $customer_cycle?$customer_cycle:0;
        }
        $datas['users'] = $userList;
        return resultArray(['data' => $datas]);
    }

    /**
     * 产品成交周期
     * @return [type] [description]
     */
    public function productCycle()
    {
        if (!checkPerByAction('bi', 'customer' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }      
        $biCustomerModel = new \app\bi\model\Customer(); 
        $productModel = new \app\bi\model\Product();
        $param = $this->param;
        $list = $productModel->getDealByProduct($param);
        $datas = array();
        foreach ($list as $key => $value) {
            $item = array();
            //周期
            $customer_ids = $productModel->getCycleByProduct($param,$value['product_id']);
            $whereArr = array();
            $whereArr['customer_id'] = array('in',$customer_ids);
            $cycle = $biCustomerModel->getWhereByCycle($whereArr);
            $item['product_name'] = $value['product_name'];
            $item['customer_num'] = $value['num'];
            $item['cycle'] = $cycle?$cycle:0;
            $datas[] = $item;
        }
        return resultArray(['data' => $datas]);
    }

    /**
     * 地区成交周期
     * @return [type] [description]
     */
    public function addressCycle()
    {
        if (!checkPerByAction('bi', 'customer' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        $userModel = new \app\admin\model\User();
        $customerModel = new \app\crm\model\Customer();
        $biCustomerModel = new \app\bi\model\Customer();
        $address_arr = array('北京','上海','天津','广东','浙江','海南','福建','湖南','湖北','重庆','辽宁','吉林','黑龙江','河北','河南','山东','陕西','甘肃','青海','新疆','山西','四川','贵州','安徽','江西','江苏','云南','内蒙古','广西','西藏','宁夏',
        );
        $param = $this->param;
        if(empty($param['type']) && empty($param['start_time'])){
            $param['type'] = 'month';
        }
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = array($param['user_id']);
        } else {
            if ($param['structure_id']) {
                $map_user_ids = $userModel->getSubUserByStr($param['structure_id'], 2);
            }
        }
        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集
        $datas = array();
        foreach ($address_arr as $key => $value) {
            $item = array();
            $whereArr = array();
            if (!empty($param['start_time'])) {
                $start_time = $param['start_time'];
                $end_time = $param['end_time'];
                $whereArr['create_time'] = array('between',array($param['start_time'],$param['end_time']));
            } else {
                $create_time = getTimeByType($param['type']);
                $start_time = $create_time[0];
                $end_time = $create_time[1];
                if ($create_time) {
                    $whereArr['create_time'] = array('between',array($create_time[0],$create_time[1]));
                }
            }
            $whereArr['owner_user_id'] = array('in',$userIds);
            $whereArr['address'] = array('like','%'.$value.'%');
            $item['address'] = $value;
            $whereArr['deal_status'] = '已成交';
            $item['customer_num'] = $dealCustomer = $customerModel->getDataCount($whereArr);
            //周期
            $cycle = $biCustomerModel->getWhereByCycle($whereArr);
            $item['cycle'] = $cycle?$cycle:0;
            $datas[] = $item;
        }
        return resultArray(['data' => $datas]);
    }

    /**
     * 客户所在城市分析
     * @author 
     * @param 
     * @return
     */
    public function addressAnalyse()
    {
        if (!checkPerByAction('bi', 'portrait' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        $customerModel = new \app\crm\model\Customer();
        $userModel = new \app\admin\model\User();
        $address_arr = array('北京','上海','天津','广东','浙江','海南','福建','湖南','湖北','重庆','辽宁','吉林','黑龙江','河北','河南','山东','陕西','甘肃','青海','新疆','山西','四川','贵州','安徽','江西','江苏','云南','内蒙古','广西','西藏','宁夏',
        );
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = array($param['user_id']);
        } else {
            if ($param['structure_id']) {
                $map_user_ids = $userModel->getSubUserByStr($param['structure_id'], 2);
            }
        }
        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集
        $datas = array();
        foreach ($address_arr as $key => $value) {
            $item = array();
            $whereArr = array();
            $whereArr['address'] = array('like','%'.$value.'%');
            $whereArr['owner_user_id'] = array('in',$userIds);
            $item['address'] = $value;
            $item['allCustomer'] = $allCustomer = $customerModel->getDataCount($whereArr);
            $whereArr['deal_status'] = '已成交';
            $item['dealCustomer'] = $dealCustomer = $customerModel->getDataCount($whereArr);
            $datas[] = $item;
        }
        return resultArray(['data' => $datas]);
    }
    
    /**
     * 客户行业/级别/来源分析
     * @author 
     * @param 
     * @return
     */
    public function portrait()
    {
        if (!checkPerByAction('bi', 'portrait' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        $biCustomerModel = new \app\bi\model\Customer();
        $customerModel = new \app\crm\model\Customer();
        $param = $this->param;
        $whereArr = array();
        $whereArr['types'] = array('eq','crm_customer');
        $whereArr['field'] = array('eq',$param['type_analyse']);
        $setting = $biCustomerModel->getOptionByField($whereArr);
        $setting[] = '未知';
        $datas = array();
        foreach ($setting as $key => $value) {
            $item = array();
            $where = array();
            $where = $biCustomerModel->getParamByWhere($param);
            if($value != '未知'){
                $where[$param['type_analyse']] = array('eq',$value);
            }else{
                $where[$param['type_analyse']] = array('eq','');
            }
            $item[$param['type_analyse']] = $value;
            $item['allCustomer'] = $customerModel->getDataCount($where);

            $where['deal_status'] = '已成交';
            $item['dealCustomer'] = $customerModel->getDataCount($where);
            $datas[] = $item;
        }
        return resultArray(['data' => $datas]);
    }
}
