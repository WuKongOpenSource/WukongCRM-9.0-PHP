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
        if (!checkPerByAction('bi', 'business' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
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
        $businessModel = new \app\crm\model\Business();
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $param['perUserIds'] = $userModel->getUserByPer('bi', 'business', 'read'); //权限范围内userIds
        $list = $businessModel->getFunnel($param);
        return resultArray(['data' => $list]);
    }  

    /**
     * 新增商机数与金额趋势分析
     * @return 
     */
    public function businessTrend()
    {
        $businessModel = new \app\crm\model\Business();
        $param = $this->param;
        $userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin(); 
        $param = $this->param;
        $perUserIds = $userModel->getUserByPer('bi', 'business', 'read'); //权限范围内userIds
        $whereArr = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds = $whereArr['userIds'];

        if(empty($param['type']) && empty($param['start_time'])){
            $param['type'] = 'month';
        }

        $time = getTimeArray();
        $where = [
            'owner_user_id' => implode(',',$userIds),
        ];
        $sql = [];

        foreach ($time['list'] as $val) {
            $whereArr = $where;
            $whereArr['type'] = $val['type'];
            $whereArr['start_time'] = $val['start_time'];
            $whereArr['end_time'] = $val['end_time'];
            $sql[] = $businessModel->getTrendql($whereArr);
        }

        $sql = implode(' UNION ALL ', $sql);
        $list = queryCache($sql);
        return resultArray(['data' => $list]);
    }

    /**
     * 新增商机数与金额趋势分析 列表
     * @return 
     */
    public function trendList()
    {
        $businessModel = new \app\bi\model\Business();
        $crmBusinessModel = new \app\crm\model\Business();
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        
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
        $businessModel = new \app\crm\model\Business();
        $userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin(); 
        $param = $this->param;
        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $whereArr = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds = $whereArr['userIds'];
        if(empty($param['type']) && empty($param['start_time'])){
            $param['type'] = 'month';
        }

        $time = getTimeArray();
        $sql = $businessModel->field([
                "FROM_UNIXTIME(create_time, '{$time['time_format']}')" => 'type',
                'COUNT(*)' => 'business_num',
                'SUM(
                    CASE WHEN
                        `is_end` = 1
                    THEN 1 ELSE 0 END
                )' => 'business_end'
            ])
            ->where([
                'owner_user_id' => ['IN', $userIds],
                'create_time' => ['BETWEEN', $time['between']]
            ])
            ->group('type')
            ->fetchSql()
            ->select();
        $res = queryCache($sql);
        $res = array_column($res, null, 'type');
        foreach ($time['list'] as $key =>$val) {
            $val['business_num'] = (int) $res[$val['type']]['business_num'];
            $val['business_end'] = (int) $res[$val['type']]['business_end'];
            if($res[$val['type']]['business_num']== 0 || $res[$val['type']]['business_end'] == 0){
                $val['proportion'] = 0;
            }else{
                $val['proportion'] = round(($res[$val['type']]['business_end']/$res[$val['type']]['business_num']),4)*100;
            }
            $time['list'][$key] = $val;
        }
        return resultArray(['data' => $time['list']]);
    }
}
