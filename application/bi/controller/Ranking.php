<?php
// +----------------------------------------------------------------------
// | Description: 商业智能-排行榜
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\bi\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;
use think\Db;
use app\bi\model\Customer as CustomerModel;
use app\bi\model\Contract as ContractModel;

class Ranking extends ApiCommon
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
            'allow'=>['contract','receivables','signing','addcustomer','addcontacts','recordnun','recordcustomer','examine','product']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
        if (!checkPerByAction('bi', 'ranking' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }         
    } 

    /**
     * 合同金额排行
     * @author Michael_xu
     * @param 
     * @return
     */
    public function contract()
    {
        $param = $this->param;
        $whereArr = $this->com($param,'contract');
        $whereArr['check_status'] = 2;

        return $this->handel(
            new \app\bi\model\Contract,
            $whereArr,
            ['field' => 'SUM(`money`)', 'alias' => 'money', 'default' => '0.00']
        );
    }

    /**
     * 回款金额排序
     * @return
     */
    public function receivables()
    {
        $param = $this->param;
        $whereArr = $this->com($param, 'receivables');
        $whereArr['check_status'] = 2;

        return $this->handel(
            new \app\bi\model\Receivables,
            $whereArr,
            ['field' => 'SUM(`money`)', 'alias' => 'money', 'default' => '0.00']
        );
    }

    /**
     * 签约合同排序
     * @return
     */
    public function signing()
    {
        $param = $this->param;
        $whereArr = $this->com($param,'contract');
        $whereArr['check_status'] = 2;
        
        return $this->handel(
            new ContractModel,
            $whereArr,
            ['field' => 'COUNT(*)', 'alias' => 'count', 'default' => 0]
        );
    }

    /**
     * 新增客户排序
     * @return
     */
    public function addCustomer()
    {        
    	$param = $this->param;
        $whereArr = $this->com($param, 'customer');

        return $this->handel(
            new \app\bi\model\Customer,
            $whereArr,
            ['field' => 'COUNT(*)', 'alias' => 'count', 'default' => 0]
        );
    }

    /**
     * 新增联系人排序
     * @return
     */
    public function addContacts()
    {        
    	$param = $this->param;
        $whereArr = $this->com($param, 'contacts');

        return $this->handel(
            new \app\bi\model\Contacts,
            $whereArr,
            ['field' => 'COUNT(*)', 'alias' => 'count', 'default' => 0]
        );
    }

    /**
     * 跟进次数排行
     * @return
     */
    public function recordNun()
    {
        $param = $this->param;
        $whereArr = $this->com($param, 'record');

        return $this->handel(
            new \app\bi\model\Record,
            $whereArr,
            ['field' => 'COUNT(*)', 'alias' => 'count', 'default' => 0],
            'create_user_id'
        );
    }

    /**
     * 跟进客户数排行
     * @return
     */
    public function recordCustomer()
    {
    	$param = $this->param;
        $whereArr = $this->com($param, 'record');

        return $this->handel(
            new \app\bi\model\Record,
            $whereArr,
            ['field' => 'COUNT(DISTINCT(`types_id`))', 'alias' => 'count', 'default' => 0],
            'create_user_id'
        );
    }

    /**
     * 出差次数排行
     * @return
     */
    public function examine()
    {     
        $param = $this->param;
        $whereArr = $this->com($param, 'record');
        $whereArr['category_id'] = 3; // 审批类型，3出差

        return $this->handel(
            new \app\bi\model\Examine,
            $whereArr,
            ['field' => 'COUNT(*)', 'alias' => 'count', 'default' => 0],
            'create_user_id'
        );
    }

    /**
     * 产品销量排行
     * @return
     */
    public function product()
    {         
        $userModel = new \app\admin\model\User();
        $productModel = new \app\bi\model\Product();
        $param = $this->param;
        $list = $productModel->getSortByProduct($param);
        $list = array_column($list, null, 'owner_user_id');

        $whereArr = $this->com($param, 'contract');

        $data = [];
        foreach ($whereArr['owner_user_id'][1] as $val) {
            $user = $userModel->getUserById($val);
            $item = [];
            $item['num'] = $list[$val]['num'] ?: 0;
            $item['user_name'] = $user['realname'];
            $item['structure_name'] = $user['structure_name'];
            $data[] = $item;
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 查询条件
     * @return
     */    
    private function com($param, $type = '')
    {
        $userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin();
        $perUserIds = $userModel->getUserByPer('bi', 'ranking', 'read'); //权限范围内userIds
        $whereData = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds = $whereData['userIds'];        
        $between_time = $whereData['between_time'];   
        if ($type == 'contract') {
            $where_time = 'order_date';
        } elseif (in_array($type, ['record', 'customer', 'contacts'])) {
            $where_time = 'create_time';
        } elseif ($type == 'receivables') {
            $where_time = 'return_time';
        }else {
            $where_time = 'start_time';
        }
        //时间戳：新增客户排行
        if ($type == 'contract' || $type == 'receivables') {
            $whereArr[$where_time] = array('between',array(date('Y-m-d',$between_time[0]),date('Y-m-d',$between_time[1])));
        } else {
            $whereArr[$where_time] = array('between',array($between_time[0],$between_time[1]));
        }

        if (in_array($type, ['customer', 'contract', 'receivables', 'contacts'])) {
            $whereArr['owner_user_id'] = ['IN', $userIds];
        } else {
            $whereArr['create_user_id'] = ['IN', $userIds];
        }
        
        return $whereArr;
    }

    /**
     * 查询统计数据
     *
     * @param model $model
     * @param array $whereArr
     * @return void
     * @author Ymob
     * @datetime 2019-11-25 11:11:59
     */
    private function handel($model, $whereArr, $field, $user_field = 'owner_user_id')
    {
    	$userModel = new \app\admin\model\User();
        $sql = $model->field([
                $user_field,
                $field['field'] => $field['alias']
            ])
            ->where($whereArr)
            ->group($user_field)
            ->fetchSql()
            ->select();

        $list = queryCache($sql);
        $list = array_column($list, null, $user_field);
        $data = [];

        foreach ($whereArr[$user_field][1] as $val) {
            $user = $userModel->getUserById($val);
            $item = [];
            $item[$field['alias']] = $list[$val][$field['alias']] ?: $field['default'];
            $item['user_name'] = $user['realname'];
            $item['structure_name'] = $user['structure_name'];
            $data[] = $item;
        }
        array_multisort($data, SORT_DESC, array_column($data, $field['alias']));
        return resultArray(['data' => $data]);
    }
}