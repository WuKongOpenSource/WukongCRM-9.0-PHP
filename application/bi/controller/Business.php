<?php
// +----------------------------------------------------------------------
// | Description: 商业智能-商机分析
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------

namespace app\bi\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;

class Business extends ApiCommon
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
            'allow'=>['funnel','businesstrend','trendlist','win']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 销售漏斗
     * @author Michael_xu
     * @param
     * @return
     */
    public function funnel()
    {
        if (!checkPerByAction('bi', 'business' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        $businessModel = new \app\crm\model\Business();
        $userModel = new \app\admin\model\User();
        $param = $this->param;

        //查询员工IDS
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = [$param['user_id']];
        } else {
            if ($param['structure_id']) {
                $map_user_ids = $userModel->getSubUserByStr($param['structure_id'], 2);
            }
        }
        $perUserIds = $userModel->getUserByPer('bi', 'business', 'read'); //权限范围内userIds
        $userIds = $map_user_ids ? array_intersect($map_user_ids, $perUserIds) : $perUserIds; //数组交集
        $param['userIds'] = $userIds ? : [];

        $list = $businessModel->getFunnel($param);
        return resultArray(['data' => $list]);
    }

    /**
     * 新增商机数与金额趋势分析
     * @return [type] [description]
     */
    public function businessTrend()
    {
        if (!checkPerByAction('bi', 'business' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        $businessModel = new \app\bi\model\Business();
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
        $perUserIds = $userModel->getUserByPer('bi', 'business', 'read'); //权限范围内userIds
        if(!isset($perUserIds)){
            $perUserIds = [];
        }
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

            $item['business_num'] = $businessModel->getDataCount($whereArr);
            $item['business_money'] = $businessModel->getDataMoney($whereArr);
            $item['start_time'] = $start_time;
            $item['end_time'] = $end_time;
            $datas[] = $item;
        }
        return resultArray(['data' => $datas]);
    }

    /**
     * 新增商机数与金额趋势分析 列表
     * @return [type] [description]
     */
    public function trendList()
    {
        if (!checkPerByAction('bi', 'business' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        $businessModel = new \app\bi\model\Business();
        $crmBusinessModel = new \app\crm\model\Business();
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        if ($param['type']) {
            $timeArr = getTimeByType($param['type']);
            $param['start_time'] = $timeArr[0];
            $param['end_time'] = $timeArr[1];
        }
        $dataList = $businessModel->getDataList($param);
        foreach ($dataList as $k => $v) {
            $business_info = $crmBusinessModel->getDataById($v['business_id']);
            $dataList[$k]['business_name'] = $business_info['name'];
            $dataList[$k]['create_time'] = date('Y-m-d',strtotime($business_info['create_time']));
            $dataList[$k]['customer_id'] = $v['customer_id'];
            $customer = db('crm_customer')->field('name')->where('customer_id',$v['customer_id'])->find();
            $dataList[$k]['customer_name'] = $customer['name'];
            $create_user_id_info = isset($v['create_user_id']) ? $userModel->getUserById($v['create_user_id']) : [];
            $dataList[$k]['create_user_name'] = $create_user_id_info['realname'];
            $owner_user_id_info = isset($v['owner_user_id']) ? $userModel->getUserById($v['owner_user_id']) : [];
            $dataList[$k]['owner_user_name'] = $owner_user_id_info['realname'];
            $dataList[$k]['status_id_info'] = db('crm_business_status')->where('status_id',$v['status_id'])->value('name');//销售阶段
            $dataList[$k]['type_id_info'] = db('crm_business_type')->where('type_id',$v['type_id'])->value('name');//商机状态组
        }
        return resultArray(['data' => $dataList]);
    }

     /**
     * 赢单机会转化率趋势分析
     * @author Michael_xu
     * @param
     * @return
     */
    public function win()
    {
        if (!checkPerByAction('bi', 'business' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        $businessModel = new \app\bi\model\Business();
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
        $perUserIds = $userModel->getUserByPer('bi', 'business', 'read'); //权限范围内userIds
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

            $item['business_num'] = $businessModel->getDataCount($whereArr);
            $whereArr['is_end'] = 1;
            $item['business_end'] = $businessModel->getDataCount($whereArr);

            if($item['business_num']== 0 || $item['business_end'] == 0){
                $item['proportion'] = 0;
            }else{
                $item['proportion'] = round(($item['business_end']/$item['business_num']),4)*100;
            }
            $item['start_time'] = $start_time;
            $item['end_time'] = $end_time;
            $datas[] = $item;
        }
        return resultArray(['data' => $datas]);
    }
}
