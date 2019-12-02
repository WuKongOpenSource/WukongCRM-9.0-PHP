<?php
// +----------------------------------------------------------------------
// | Description: 商业智能-员工业绩分析
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\bi\controller;

use app\admin\controller\ApiCommon;
use app\crm\model\Contract AS ContractModel;
use app\crm\model\Receivables AS ReceivablesModel;
use think\Hook;
use think\Request;

class Contract extends ApiCommon
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
            'allow'=>['analysis','summary']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
        if (!checkPerByAction('bi', 'contract' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }         
    }

    /**
     * 合同数量分析/金额分析/回款金额分析
     * @return 
     */
    public function analysis()
    {
        $userModel = new \app\admin\model\User();
        $receivablesModel = new \app\bi\model\Receivables();
        $biContractModel = new \app\bi\model\Contract();
        $adminModel = new \app\admin\model\Admin(); 
        $param = $this->param;
        $perUserIds = $userModel->getUserByPer('bi', 'contract', 'read'); //权限范围内userIds
        $whereArr = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds = $whereArr['userIds'];

        $year = $param['year'] ? : date('Y');
        $start_time = strtotime(date(($year - 1) . '-01-01'));
        $end_time = strtotime('+2 year', $start_time) - 1;
        $time = getTimeArray($start_time, $end_time);
        
        if ($param['type'] == 'back') {
            $model = new ReceivablesModel;
            $time_field = 'return_time';
        } else {
            $model = new ContractModel;
            $time_field = 'order_date';
        }
        if ($param['type'] == 'count') {
            $field['COUNT(*)'] = 'total';
        } else {
            $field['SUM(`money`)'] = 'total';
        }
        $between_time = [date('Y-m-d', $time['between'][0]), date('Y-m-d', $time['between'][1])];
        $field["SUBSTR(`{$time_field}`, 1, 7)"] = 'type';
        $sql = $model->field($field)
            ->where([
                'owner_user_id' => ['IN', $userIds],
                'check_status' => 2,
                $time_field => ['BETWEEN', $between_time]
            ])
            ->group('type')
            ->fetchSql()
            ->select();
        $res = queryCache($sql);
        $res = array_column($res, null, 'type');

        $data = [];
        for ($i = 12; $i < 24; $i++) {
            $k = $time['list'][$i]['type'];
            $k2 = $time['list'][$i - 1]['type'];
            $k3 = $time['list'][$i - 11]['type'];
            $item = [
                'type' => $i - 11 . '月'
            ];
            $item['month'] = $res[$k] ? $res[$k]['total'] : 0;
            $item['lastMonth'] = $res[$k2] ? $res[$k2]['total'] : 0;
            $item['lastYeatMonth'] = $res[$k3] ? $res[$k3]['total'] : 0;
            // 环比
            $item['chain_ratio'] = $item['lastMonth'] ? round(($item['month'] / $item['lastMonth']), 4) * 100 : 0;
            // 同比
            $item['year_on_year'] = $item['year_on_year'] ? round(($item['month'] / $item['year_on_year']), 4) * 100 : 0;

            $data[] = $item;
        }
        return resultArray(['data' => $data]);
    }
    
    /**
     * 合同汇总表
     * @return 
     */
    public function summary()
    {
        $userModel = new \app\admin\model\User();
        $receivablesModel = new \app\bi\model\Receivables();
        $biContractModel = new \app\bi\model\Contract();
        $biCustomerModel = new \app\bi\model\Customer();
        $adminModel = new \app\admin\model\Admin(); 
        $param = $this->param;
        $perUserIds = $userModel->getUserByPer('bi', 'contract', 'read'); //权限范围内userIds
        $whereArr = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds = $whereArr['userIds'];
        if(empty($param['type']) && empty($param['start_time'])){
            $param['type'] = 'month';
        }

        $time = getTimeArray();
        $between_time = [date('Y-m-d', $time['between'][0]), date('Y-m-d', $time['between'][1])];
        $sql = ContractModel::field([
                'SUBSTR(`order_date`, 1, 7)' => 'type',
                'COUNT(*)' => 'count',
                'SUM(`money`)' => 'money'
            ])
            ->where([
                'owner_user_id' => ['IN', $userIds],
                'check_status' => 2,
                'order_date' => ['BETWEEN', $between_time]
            ])
            ->group('type')
            ->fetchSql()
            ->select();
        
        $contract_data = queryCache($sql);
        $contract_data = array_column($contract_data, null, 'type');
        
        $sql = ReceivablesModel::field([
                'SUBSTR(`return_time`, 1, 7)' => 'type',
                'SUM(`money`)' => 'money'
            ])
            ->where([
                'owner_user_id' => ['IN', $userIds],
                'check_status' => 2,
                'return_time' => ['BETWEEN', $between_time]
            ])
            ->group('type')
            ->fetchSql()
            ->select();
        $receivables_data = queryCache($sql);
        $receivables_data = array_column($receivables_data, null, 'type');
        
        $items = [];
        $count_zong = 0;
        $money_zong = 0;
        $back_zong = 0;
        foreach ($time['list'] as $val) {
            $item = ['type' => $val['type']];
            $count_zong += $item['count'] = $contract_data[$val['type']]['count'] ?: 0;
            $money_zong += $item['money'] = $contract_data[$val['type']]['money'] ?: 0;
            $back_zong += $item['back'] = $receivables_data[$val['type']]['money'] ?: 0;
            $items[] = $item;
        }
        $data = [
            'items' => $items,
            'count_zong' => $count_zong,
            'money_zong' => $money_zong,
            'back_zong' => $back_zong,
            'w_back_zong' => $money_zong - $back_zong,
        ];
        return resultArray(['data' => $data]);
    }
}
