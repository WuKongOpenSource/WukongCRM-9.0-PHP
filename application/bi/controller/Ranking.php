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
        $userModel = new \app\admin\model\User();
        $contractModel = new \app\bi\model\Contract();
        $param = $this->param;
        $whereArr = $this->com($param,'contract');
        $whereArr['check_status'] = array('eq',2);
        $userList = $contractModel->getSortByMoney($whereArr);
        foreach ($userList as $key => $value) {
            $user = $userModel->getUserById($value['owner_user_id']);
            $userList[$key]['user_name'] = $user['realname'];
            $userList[$key]['structure_name'] = $user['structure_name'];
        }
        return resultArray(['data' => $userList]);
    }

    /**
     * 回款金额排序
     * @return
     */
    public function receivables()
    {        
    	$userModel = new \app\admin\model\User();
        $receivablesModel = new \app\bi\model\Receivables();
        $param = $this->param;
        $whereArr = $this->com($param,'receivables');
        $whereArr['check_status'] = array('eq',2);
        $userList = $receivablesModel->getSortByMoney($whereArr);
        foreach ($userList as $key => $value) {
            $user = $userModel->getUserById($value['owner_user_id']);
            $userList[$key]['user_name'] = $user['realname'];
            $userList[$key]['structure_name'] = $user['structure_name'];
        }
        return resultArray(['data' => $userList]);
    }

    /**
     * 签约合同排序
     * @return
     */
    public function signing()
    {        
    	$userModel = new \app\admin\model\User();
        $contractModel = new \app\bi\model\Contract();
        $param = $this->param;
        $whereArr = $this->com($param,'contract');
        $whereArr['check_status'] = array('eq',2);
        $userList = $contractModel->getSortByCount($whereArr);
        foreach ($userList as $key => $value) {
            $user = $userModel->getUserById($value['owner_user_id']);
            $userList[$key]['user_name'] = $user['realname'];
            $userList[$key]['structure_name'] = $user['structure_name'];
        }
        return resultArray(['data' => $userList]);
    }

    /**
     * 新增客户排序
     * @return
     */
    public function addCustomer()
    {        
    	$userModel = new \app\admin\model\User();
        $customerModel = new \app\bi\model\Customer();
        $param = $this->param;
        $whereArr = $this->com($param,'record');
        $userList = $customerModel->getSortByCount($whereArr);
        foreach ($userList as $key => $value) {
            $user = $userModel->getUserById($value['owner_user_id']);
            $userList[$key]['user_name'] = $user['realname'];
            $userList[$key]['structure_name'] = $user['structure_name'];
        }
        return resultArray(['data' => $userList]);
    }

    /**
     * 新增联系人排序
     * @return
     */
    public function addContacts()
    {        
    	$userModel = new \app\admin\model\User();
        $contactsModel = new \app\bi\model\Contacts();
        $param = $this->param;
        $whereArr = $this->com($param,'record');
        $userList = $contactsModel->getSortByCount($whereArr);
        foreach ($userList as $key => $value) {
            $user = $userModel->getUserById($value['owner_user_id']);
            $userList[$key]['user_name'] = $user['realname'];
            $userList[$key]['structure_name'] = $user['structure_name'];
        }
        return resultArray(['data' => $userList]);
    }

    /**
     * 跟进次数排行
     * @return
     */
    public function recordNun()
    {        
    	$userModel = new \app\admin\model\User();
        $recordModel = new \app\bi\model\Record();
        $param = $this->param;
        $whereArr = $this->com($param,'record');
        $userList = $recordModel->getSortByCount($whereArr);
        foreach ($userList as $key => $value) {
            $user = $userModel->getUserById($value['create_user_id']);
            $userList[$key]['user_name'] = $user['realname'];
            $userList[$key]['structure_name'] = $user['structure_name'];
        }
        return resultArray(['data' => $userList]);
    }

    /**
     * 跟进客户数排行
     * @return
     */
    public function recordCustomer()
    {         
    	$userModel = new \app\admin\model\User();
        $recordModel = new \app\bi\model\Record();
        $param = $this->param;
        $whereArr = $this->com($param,'record');
        $userList = $recordModel->getSortByCustomer($whereArr);
        foreach ($userList as $key => $value) {
            $user = $userModel->getUserById($value['create_user_id']);
            $userList[$key]['user_name'] = $user['realname'];
            $userList[$key]['structure_name'] = $user['structure_name'];
        }
        return resultArray(['data' => $userList]);
    }

    /**
     * 出差次数排行
     * @return
     */
    public function examine()
    {         
        $userModel = new \app\admin\model\User();
        $examineModel = new \app\bi\model\Examine();
        $param = $this->param;
        $whereArr = $this->com($param,'record');
        $whereArr['category_id'] = array('eq',3);
        $userList = $examineModel->getSortByExamine($whereArr);
        foreach ($userList as $key => $value) {
            $user = $userModel->getUserById($value['create_user_id']);
            $userList[$key]['user_name'] = $user['realname'];
            $userList[$key]['structure_name'] = $user['structure_name'];
        }
        return resultArray(['data' => $userList]);
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
        $userList = $productModel->getSortByProduct($param);
        foreach ($userList as $key => $value) {
            $user = $userModel->getUserById($value['owner_user_id']);
            $userList[$key]['user_name'] = $user['realname'];
            $userList[$key]['structure_name'] = $user['structure_name'];
        }
        return resultArray(['data' => $userList]);
    }

    /**
     * 查询条件
     * @return
     */    
    public function com($param, $type = '')
    {
        $userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin();
        $perUserIds = $userModel->getUserByPer('bi', 'ranking', 'read'); //权限范围内userIds
        $whereData = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds = $whereData['userIds'];        
        $between_time = $whereData['between_time'];   
        if ($type == 'contract') {
            $where_time = 'order_date';
        } elseif ($type == 'record') {
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
        $whereArr['create_user_id'] = array('in',$userIds);
        return $whereArr;
    }
}