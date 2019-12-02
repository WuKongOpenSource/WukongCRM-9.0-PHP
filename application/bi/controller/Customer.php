<?php
// +----------------------------------------------------------------------
// | Description: 商业智能-客户分析
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\bi\controller;

use app\admin\controller\ApiCommon;
use app\bi\model\Customer as CustomerModel;
use app\admin\model\User as UserModel;
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
        if (!checkPerByAction('bi', 'customer' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
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
     * @author zhi
     * @param 
     * @return
     */
    public function total()
    {
        $customerModel = new \app\crm\model\Customer();
        $userModel = new \app\admin\model\User();
        $biCustomerModel = new \app\bi\model\Customer();
        $adminModel = new \app\admin\model\Admin(); 
        $param = $this->param;
        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $whereArr = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds = $whereArr['userIds'];

        if(empty($param['type']) && empty($param['start_time'])){
            $param['type'] = 'month';
        }

        $time = getTimeArray();
        $where = [
            'create_user_id' => implode(',',$userIds),
            'deal_status' => '已成交'
        ];
        $sql = [];

        foreach ($time['list'] as $val) {
            $whereArr = $where;
            $whereArr['type'] = $val['type'];
            $whereArr['start_time'] = $val['start_time'];
            $whereArr['end_time'] = $val['end_time'];
            $sql[] = $customerModel->getAddDealSql($whereArr);
        }

        $sql = implode(' UNION ALL ', $sql);
        
        $list = queryCache($sql);
        return resultArray(['data' => $list]);
    }

    /**
     * 员工客户跟进次数分析
     * @author zhi
     * @param 
     * @return
     */
    public function recordTimes()
    {
        $biCustomerModel = new \app\bi\model\Customer();
        $biRecordModel = new \app\bi\model\Record();
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

        $sql = $biRecordModel
            ->field([
                "FROM_UNIXTIME(create_time, '{$time['time_format']}')" => 'type',
                'COUNT(DISTINCT(types_id))' => 'customerCount',
                'COUNT(*)' => 'dataCount'
            ])
            ->where([
                'create_time' => ['BETWEEN', $time['between']],
                'create_user_id' => ['IN', $userIds],
                'types' => 'crm_customer',
            ])
            ->group('type')
            ->fetchSql()
            ->select();
        $res = queryCache($sql);
        $res = array_column($res, null, 'type');

        foreach ($time['list'] as &$val) {
            $val['customerCount'] = (int) $res[$val['type']]['customerCount'];
            $val['dataCount'] = (int) $res[$val['type']]['dataCount'];
        }
        return resultArray(['data' => $time['list']]);
    }

    /**
     * 员工客户跟进次数分析 具体员工列表
     * @author zhi
     * @param 
     * @return
     */
    public function recordList()
    {
        $biRecordModel = new \app\bi\model\Record();
        $userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin(); 
        $param = $this->param;
        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $whereArr = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds = $whereArr['userIds'];

        $time = getTimeArray();
        $sql = $biRecordModel
            ->field([
                'create_user_id',
                'COUNT(DISTINCT(types_id))' => 'customer_num',
                'COUNT(*)' => 'record_num'
            ])
            ->where([
                'create_time' => ['BETWEEN', $time['between']],
                'create_user_id' => ['IN', $userIds],
                'types' => 'crm_customer',
            ])
            ->group('create_user_id')
            ->fetchSql()
            ->select();
        
        $list = queryCache($sql);
        $list = array_column($list, null, 'create_user_id');

        $user_list = $userModel->field(['id', 'realname'])
            ->where(['id' => ['IN', $userIds]])
            ->cache(true, config('bi_cache_time'))
            ->select();

        $data = [];
        foreach ($userIds as $val) {
            $item = [];
            $item['customer_num'] = $list[$val]['customer_num'];
            $item['record_num'] = $list[$val]['record_num'];
            $item['realname'] = $userModel->getUserById($val)['realname'];
            $data[] = $item;
        }

        return resultArray(['data' => $data]);
    }

    /**
     * 员工跟进方式分析
     * @author zhi
     * @param 
     * @return
     */
    public function recordMode()
    {
        $biCustomerModel = new \app\bi\model\Customer();
        $biRecordModel = new \app\bi\model\Record();
        $param = $this->param;
        $whereArr = $biCustomerModel->getParamByWhere($param,'record');
        
        //跟进类型
        $record_type = db('crm_config')->where(['name' => 'record_type'])->find();
        if ($record_type) {
            $record_categorys = json_decode($record_type['value']);        
        } else {
            $record_categorys = array('打电话','发邮件','发短信','见面拜访','活动');
        }

        $sql = $biRecordModel
            ->field([
                'category',
                'COUNT(*)' => 'count'
            ])
            ->where([
                'create_time' => $whereArr['create_time'],
                'create_user_id' => $whereArr['create_user_id'],
                'types' => 'crm_customer',
            ])
            ->group('category')
            ->fetchSql()
            ->select();

        $list = queryCache($sql);
        $list = array_column($list, null, 'category');
        $sum = array_sum(array_column($list, 'count'));
        
        $res = [];
        foreach ($record_categorys as $val) {
            $item['category'] = $val;
            if ($sum) {
                $item['recordNum'] = (int) $list[$val]['count'];
                $item['proportion'] = round($item['recordNum'] / $sum, 4) * 100;
            } else {
                $item['recordNum'] = $item['proportion'] = 0;
            }
            $res[] = $item;
        }
        return resultArray(['data' => $res]);
    }

    /**
     * 客户转化率分析
     * @author zhi
     * @param 
     * @return
     */
    public function conversion()
    {
        $customerModel = new \app\crm\model\Customer();
        $userModel = new \app\admin\model\User();
        $biCustomerModel = new \app\bi\model\Customer();
        $adminModel = new \app\admin\model\Admin(); 
        $param = $this->param;
        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $whereArr = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds = $whereArr['userIds'];
        $user_ids = implode(',',$userIds);
        
        if(empty($param['type']) && empty($param['start_time'])){
            $param['type'] = 'month';
        }

        $time = getTimeArray();
        $sql = [];
        foreach ($time['list'] as $val) {
            $sql[] = $customerModel->getAddDealSql([
                'create_user_id' => $user_ids,
                'type' => $val['type'],
                'start_time' => $val['start_time'],
                'end_time' => $val['end_time'],
                'deal_status' => '已成交',
            ]);
        }
        $sql = implode(' UNION ALL ', $sql);
        $list = queryCache($sql);
        foreach ($list as &$val) {
            $val['proportion'] = $val['customer_num'] ? $val['deal_customer_num'] / $val['customer_num'] : 0;
        }
        return resultArray(['data' => $list]);
    }

    /**
     * 客户转化率分析具体数据
     * @author zhi
     * @param 
     * @return
     */
    public function conversionInfo()
    {
        $customerModel = new \app\bi\model\Customer();
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $whereArr = $customerModel->getParamByWhere($param);
        $whereArr['deal_status'] = '已成交';
        
        $list = $customerModel->getWhereByList($whereArr);
        return resultArray(['data' => $list]);
    }

    /**
     * 公海客户分析
     * @author zhi
     * @param 
     * @return
     */
    public function pool()
    {
        $actionRecordModel = new \app\bi\model\ActionRecord();
        $userModel = new \app\admin\model\User();
        $biCustomerModel = new \app\bi\model\Customer();
        $adminModel = new \app\admin\model\Admin(); 
        $param = $this->param;
        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $whereArr = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds = $whereArr['userIds'];
        if (empty($param['type']) && empty($param['start_time'])) {
            $param['type'] = 'month';
        }

        $time = getTimeArray();
        $sql = $actionRecordModel
            ->field([
                "FROM_UNIXTIME(`create_time`, '{$time['time_format']}')" => 'type',
                'SUM(CASE WHEN `content` = "将客户放入公海" THEN 1 ELSE 0 END)' => 'put_in',
                'SUM(CASE WHEN `content` = "领取了客户" THEN 1 ELSE 0 END)' => 'receive'
            ])
            ->where([
                'user_id' => ['IN', $userIds],
                'create_time' => ['BETWEEN', $time['between']],
                'content' => ['IN', ['将客户放入公海', '领取了客户']]
            ])
            ->group('type')
            ->fetchSql()
            ->select();
        $res = queryCache($sql);
        $res = array_column($res, null, 'type');
        
        foreach ($time['list'] as &$val) {
            $val['put_in'] = (int) $res[$val['type']]['put_in'];
            $val['receive'] = (int) $res[$val['type']]['receive'];
        }

        return resultArray(['data' => $time['list']]);
    }

    /**
     * 公海客户分析 具体列表
     * @author zhi
     * @param 
     * @return
     */
    public function poolList()
    {
        $userModel = new \app\admin\model\User();
        $actionRecordModel = new \app\bi\model\ActionRecord();
        $customerModel = new \app\crm\model\Customer();
        $structureModel = new \app\admin\model\Structure();
        $adminModel = new \app\admin\model\Admin(); 
        $param = $this->param;
        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $whereArr = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds = $whereArr['userIds'];
        $between_time = $whereArr['between_time'];

        $sql = CustomerModel::field([
                'COUNT(*)' => 'customer_num',
                'owner_user_id'
            ])
            ->where([
                'create_time' => ['BETWEEN', $between_time],
                'owner_user_id' => ['IN', $userIds]
            ])
            ->group('owner_user_id')
            ->fetchSql()
            ->select();
        $customer_num_list = queryCache($sql);
        $customer_num_list = array_column($customer_num_list, null, 'owner_user_id');

        $sql = $actionRecordModel
            ->field([
                'user_id',
                'SUM(CASE WHEN `content` = "将客户放入公海" THEN 1 ELSE 0 END)' => 'put_in',
                'SUM(CASE WHEN `content` = "领取了客户" THEN 1 ELSE 0 END)' => 'receive'
            ])
            ->group('user_id')
            ->where([
                'create_time' => ['BETWEEN', $between_time],
                'user_id' => ['IN', $userIds],
                'content' => ['IN', ['将客户放入公海', '领取了客户']],
                'types' => 'crm_customer',
            ])
            ->fetchSql()
            ->select();
        $action_record_list = queryCache($sql);
        $action_record_list = array_column($action_record_list, null, 'user_id');

        $res = [];
        foreach ($userIds as $val) {
            $item['put_in'] = $action_record_list[$val]['put_in'] ?: 0;
            $item['receive'] = $action_record_list[$val]['receive'] ?: 0;
            $item['customer_num'] = $customer_num_list[$val]['customer_num'] ?: 0;
            $user_info = $userModel->getUserById($val);
            $item['realname'] = $user_info['realname'];
            $item['username'] = $user_info['structure_name'];
            $res[] = $item;
        }
        return resultArray(['data' => $res]);
    }

    /**
     * 员工客户成交周期 
     * @author zhi
     * @param 
     * @return
     */
    public function userCycle()
    {
        $userModel = new \app\admin\model\User();
        $biCustomerModel = new \app\bi\model\Customer();
        $adminModel = new \app\admin\model\Admin(); 
        $param = $this->param;
        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $whereData = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds = $whereData['userIds'];
        $between_time = $whereData['between_time'];

        if (empty($param['type']) && empty($param['start_time'])) {
            $param['type'] = 'month';
        }
        $time = getTimeArray();
        $sql = [];

        $prefix = config('database.prefix');
        
        $sql = CustomerModel::alias('a')
            ->field([
                "FROM_UNIXTIME(`a`.`create_time`, '{$time['time_format']}')" => 'type',
                'COUNT(*)' => 'customer_num',
                'SUM(
                    CASE WHEN ISNULL(`b`.`order_date`) THEN 0 ELSE (
                        UNIX_TIMESTAMP(`b`.`order_date`) - `a`.`create_time`
                    ) / 86400 END
                )' => 'cycle_sum'
            ])
            ->join(
                "(
                    SELECT 
                        `customer_id`, MIN(`order_date`) AS `order_date` 
                    FROM
                        `{$prefix}crm_contract` 
                    WHERE
                        `check_status` = 2 
                    GROUP BY
                        `customer_id`
                ) b",
                '`a`.`customer_id` = `b`.`customer_id`',
                'LEFT'
            )
            ->where([
                'a.deal_status' => '已成交',
                'a.create_time' => ['BETWEEN', $time['between']],
                'a.owner_user_id' => ['IN', $userIds]
            ])
            ->group('type')
            ->fetchsql()
            ->select();
        $res = queryCache($sql);
        $res = array_column($res, null, 'type');
        
        foreach ($time['list'] as &$val) {
            $val['customer_num'] = (int) $res[$val['type']]['customer_num'];
            if ($res[$val['type']]['customer_num']) {
                $val['cycle'] = intval($res[$val['type']]['cycle_sum'] / $res[$val['type']]['customer_num']);
            } else {
                $val['cycle'] = 0;
            }
        }
        
        $datas = ['items' => $time['list']];

        $sql = CustomerModel::alias('a')
            ->field([
                'a.owner_user_id',
                'COUNT(*)' => 'customer_num',
                'SUM(
                    CASE WHEN  ISNULL(b.order_date) THEN 0 ELSE (
                        UNIX_TIMESTAMP(b.order_date) - a.create_time
                    ) / 86400 END
                )' => 'cycle_sum'
            ])
            ->join(
                "(
                    SELECT 
                        `customer_id`, 
                        MIN(`order_date`) AS `order_date` 
                    FROM 
                        `{$prefix}crm_contract` 
                    WHERE 
                        `check_status` = 2 
                    GROUP BY 
                        `customer_id`
                ) b",
                'a.customer_id = b.customer_id',
                'LEFT'
            )
            ->where([
                'a.deal_status' => '已成交',
                'a.create_time' => ['BETWEEN', $time['between']],
                'a.owner_user_id' => ['IN', $userIds]
            ])
            ->group('a.owner_user_id')
            ->fetchSql()
            ->select();
        $res = queryCache($sql);
        $res = array_column($res, null, 'owner_user_id');
        
        $user_data = [];
        foreach ($userIds as $val) {
            $item['customer_num'] = $res[$val]['customer_num'];
            $item['cycle'] = $res[$val]['customer_num'] ? intval($res[$val]['cycle_sum'] / $res[$val]['customer_num']) : 0;
            $item['realname'] = $userModel->getUserById($val)['realname'];
            $user_data[] = $item;
        }
        $datas['users'] = $user_data;

        return resultArray(['data' => $datas]);
    }

    /**
     * 产品成交周期
     * @author zhi
     * @param 
     * @return
     */
    public function productCycle()
    {     
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
     * @author zhi
     * @param 
     * @return
     */
    public function addressCycle()
    {
        $userModel = new \app\admin\model\User();
        $customerModel = new \app\crm\model\Customer();
        $biCustomerModel = new \app\bi\model\Customer();
        $address_arr = \app\crm\model\Customer::$address;
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
        $time = getTimeArray();

        $prefix = config('database.prefix');
        $sql = CustomerModel::alias('a')
            ->field([
                'SUBSTR(`a`.`address`, 1, 2)' => 'addr',
                'COUNT(*)' => 'customer_num',
                'SUM(
                    CASE WHEN  ISNULL(b.order_date) THEN 0 ELSE (
                        UNIX_TIMESTAMP(b.order_date) - a.create_time
                    ) / 86400 END
                )' => 'cycle_sum'
            ])
            ->join(
                "(
                    SELECT 
                        `customer_id`, 
                        MIN(`order_date`) AS `order_date` 
                    FROM 
                        `{$prefix}crm_contract` 
                    WHERE 
                        `check_status` = 2 
                    GROUP BY 
                        `customer_id`
                ) b",
                'a.customer_id = b.customer_id',
                'LEFT'
            )
            ->where([
                'a.deal_status' => '已成交',
                'a.create_time' => ['BETWEEN', $time['between']],
                'a.owner_user_id' => ['IN', $userIds]
            ])
            ->group('addr')
            ->fetchSql()
            ->select();
        $list = queryCache($sql);
        $list = array_column($list, null, 'addr');
        $list['黑龙江'] = $list['黑龙'];
        $list['内蒙古'] = $list['内蒙'];
        $res = [];
        foreach ($address_arr as $val) {
            $item['address'] = $val;
            $item['customer_num'] = $list[$val]['customer_num'];
            $item['cycle'] = $list[$val]['customer_num'] ? intval($list[$val]['cycle_sum'] / $list[$val]['customer_num']) : 0;
            $res[] = $item;
        }
        
        return resultArray(['data' => $res]);
    }

    /**
     * 客户所在城市分析
     * @author zhi
     * @param 
     * @return
     */
    public function addressAnalyse()
    {
        // $customerModel = new \app\crm\model\Customer();
        $userModel = new \app\admin\model\User();
        $address_arr = \app\crm\model\Customer::$address;
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
        
        $time = getTimeArray();
        $sql = CustomerModel::alias('a')
            ->field([
                'SUBSTR(`address`, 1, 2)' => 'addr',
                'COUNT(*)' => 'allCustomer',
                'SUM(
                    CASE WHEN `deal_status` = "已成交" THEN  1 ELSE 0 END
                )' => 'dealCustomer',
            ])
            ->where([
                'create_time' => ['BETWEEN', $time['between']],
                'owner_user_id' => ['IN', $userIds]
            ])
            ->group('addr')
            ->fetchSql()
            ->select();
        $list = queryCache($sql);
        $list = array_column($list, null, 'addr');
        $list['黑龙江'] = $list['黑龙'];
        $list['内蒙古'] = $list['内蒙'];
        $data = [];
        foreach ($address_arr as $val) {
            $item['address'] = $val;
            $item['allCustomer'] = $list[$val]['allCustomer'];
            $item['dealCustomer'] = $list[$val]['dealCustomer'];
            $data[] = $item;
        }
        return resultArray(['data' => $data]);
    }
    
    /**
     * 客户行业/级别/来源分析
     * @author zhi
     * @param 
     * @return
     */
    public function portrait()
    {
        $biCustomerModel = new \app\bi\model\Customer();
        $userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin(); 
        $param = $this->param;
        $perUserIds = $userModel->getUserByPer('bi', 'customer', 'read'); //权限范围内userIds
        $whereData = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds = $whereData['userIds'];
        if (!in_array($param['type_analyse'], ['industry', 'source', 'level'])) {
            return resultArray(['error' => '参数错误']);
        }
        $whereArr = array();
        $whereArr['types'] = 'crm_customer';
        $whereArr['field'] = $param['type_analyse'];
        $setting = $biCustomerModel->getOptionByField($whereArr);
        $time = getTimeArray();
        $sql = CustomerModel::field([
                "(
                    CASE WHEN 
                        `{$param['type_analyse']}` = '' 
                    THEN '(空)' 
                    ELSE {$param['type_analyse']} END
                )" => $param['type_analyse'],
                'COUNT(*)' => 'allCustomer',
                'SUM(
                    CASE WHEN `deal_status` = "已成交" THEN  1 ELSE 0 END
                )' => 'dealCustomer',
            ])
            ->where([
                'create_time' => ['BETWEEN', $time['between']],
                'owner_user_id' => ['IN', $userIds]
            ])
            ->group($param['type_analyse'])
            ->fetchSql()
            ->select();
        $list = queryCache($sql);
        $list = array_column($list, null, $param['type_analyse']);
        $other_keys = array_diff(array_keys($list), $setting);
        $setting = array_merge($setting, $other_keys);

        $data = [];
        foreach ($setting as $val) {
            $item = [];

            $item[$param['type_analyse']] = $val;
            $item['allCustomer'] = $list[$val]['allCustomer'] ?: 0;
            $item['dealCustomer'] = $list[$val]['dealCustomer'] ?: 0;

            $data[] = $item;
        }

        return resultArray(['data' => $data]);
    }
}
