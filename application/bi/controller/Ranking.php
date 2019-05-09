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
    } 

    /**
     * 合同金额排行
     * @author Michael_xu
     * @param 
     * @return
     */
    public function contract()
    {     
        if (!checkPerByAction('bi', 'ranking' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }          
        $userModel = new \app\admin\model\User();
        $contractModel = new \app\bi\model\Contract();
        $param = $this->param;
        $whereArr = $this->com($param,'contract');
        $whereArr['check_status'] = array('eq',2);
        $userList = $contractModel->getSortByMoney($whereArr);
        foreach ($userList as $key => $value) {
            $user = $userModel->getDataById($value['owner_user_id']);
            $userList[$key]['user_name'] = $user['realname'];
            $userList[$key]['structure_name'] = $user['structure_name'];
        }
        return resultArray(['data' => $userList]);
    }

    /**
     * 回款金额排序
     * @return [type] [description]
     */
    public function receivables()
    {
        if (!checkPerByAction('bi', 'ranking' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }         
    	$userModel = new \app\admin\model\User();
        $receivablesModel = new \app\bi\model\Receivables();
        $param = $this->param;
        $userModel = new \app\admin\model\User();
        //员工IDS
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = array($param['user_id']);
        } else {
            if ($param['structure_id']) {
                $map_user_ids = $userModel->getSubUserByStr($param['structure_id'], 2);
            }
        }
        $userIds = $map_user_ids ? $map_user_ids : []; //数组交集
        $create_time = [];
        if(empty($param['type']) && empty($param['start_time'])){
            $param['type'] = 'month';
        }
        if(!empty($param['start_time'])){
            $whereArr['return_time'] = array('between',array($param['start_time'],$param['end_time']));
        }else{
            $create_time = getTimeByType($param['type']);
            if ($create_time) {
                $whereArr['return_time'] = array('between',array(date('Y-m-d',$create_time[0]),date('Y-m-d',$create_time[1])));
            }
        }
        $whereArr['create_user_id'] = array('in',$userIds);
        $whereArr['check_status'] = array('eq',2);
        $userList = $receivablesModel->getSortByMoney($whereArr);
        foreach ($userList as $key => $value) {
            $user = $userModel->getDataById($value['owner_user_id']);
            $userList[$key]['user_name'] = $user['realname'];
            $userList[$key]['structure_name'] = $user['structure_name'];
        }
        return resultArray(['data' => $userList]);
    }

    /**
     * 签约合同排序
     * @return [type] [description]
     */
    public function signing()
    {
        if (!checkPerByAction('bi', 'ranking' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }         
    	$userModel = new \app\admin\model\User();
        $contractModel = new \app\bi\model\Contract();
        $param = $this->param;
        $whereArr = $this->com($param,'contract');
        $whereArr['check_status'] = array('eq',2);
        $userList = $contractModel->getSortByCount($whereArr);
        foreach ($userList as $key => $value) {
            $user = $userModel->getDataById($value['owner_user_id']);
            $userList[$key]['user_name'] = $user['realname'];
            $userList[$key]['structure_name'] = $user['structure_name'];
        }
        return resultArray(['data' => $userList]);
    }

    /**
     * 新增客户排序
     * @return [type] [description]
     */
    public function addCustomer()
    {
        if (!checkPerByAction('bi', 'ranking' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }         
    	$userModel = new \app\admin\model\User();
        $customerModel = new \app\bi\model\Customer();
        $param = $this->param;
        $whereArr = $this->com($param,'record');
        $userList = $customerModel->getSortByCount($whereArr);
        foreach ($userList as $key => $value) {
            $user = $userModel->getDataById($value['owner_user_id']);
            $userList[$key]['user_name'] = $user['realname'];
            $userList[$key]['structure_name'] = $user['structure_name'];
        }
        return resultArray(['data' => $userList]);
    }

    /**
     * 新增联系人排序
     * @return [type] [description]
     */
    public function addContacts()
    {
        if (!checkPerByAction('bi', 'ranking' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }         
    	$userModel = new \app\admin\model\User();
        $contactsModel = new \app\bi\model\Contacts();
        $param = $this->param;
        $whereArr = $this->com($param,'record');
        $userList = $contactsModel->getSortByCount($whereArr);
        foreach ($userList as $key => $value) {
            $user = $userModel->getDataById($value['owner_user_id']);
            $userList[$key]['user_name'] = $user['realname'];
            $userList[$key]['structure_name'] = $user['structure_name'];
        }
        return resultArray(['data' => $userList]);
    }

    /**
     * 跟进次数排行
     * @return [type] [description]
     */
    public function recordNun()
    {
        if (!checkPerByAction('bi', 'ranking' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }         
    	$userModel = new \app\admin\model\User();
        $recordModel = new \app\bi\model\Record();
        $param = $this->param;
        $whereArr = $this->com($param,'record');
        $userList = $recordModel->getSortByCount($whereArr);
        foreach ($userList as $key => $value) {
            $user = $userModel->getDataById($value['create_user_id']);
            $userList[$key]['user_name'] = $user['realname'];
            $userList[$key]['structure_name'] = $user['structure_name'];
        }
        return resultArray(['data' => $userList]);
    }

    /**
     * 跟进客户数排行
     * @return [type] [description]
     */
    public function recordCustomer()
    {
        if (!checkPerByAction('bi', 'ranking' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }         
    	$userModel = new \app\admin\model\User();
        $recordModel = new \app\bi\model\Record();
        $param = $this->param;
        $whereArr = $this->com($param,'record');
        $userList = $recordModel->getSortByCustomer($whereArr);
        foreach ($userList as $key => $value) {
            $user = $userModel->getDataById($value['create_user_id']);
            $userList[$key]['user_name'] = $user['realname'];
            $userList[$key]['structure_name'] = $user['structure_name'];
        }
        return resultArray(['data' => $userList]);
    }

    /**
     * 出差次数排行
     * @return [type] [description]
     */
    public function examine()
    {
        if (!checkPerByAction('bi', 'ranking' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }         
        $userModel = new \app\admin\model\User();
        $examineModel = new \app\bi\model\Examine();
        $param = $this->param;
        $whereArr = $this->com($param,'record');
        $whereArr['category_id'] = array('eq',3);
        $userList = $examineModel->getSortByExamine($whereArr);
        foreach ($userList as $key => $value) {
            $user = $userModel->getDataById($value['create_user_id']);
            $userList[$key]['user_name'] = $user['realname'];
            $userList[$key]['structure_name'] = $user['structure_name'];
        }
        return resultArray(['data' => $userList]);
    }

    /**
     * 产品销量排行
     * @return [type] [description]
     */
    public function product()
    {
        if (!checkPerByAction('bi', 'ranking' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }         
        $userModel = new \app\admin\model\User();
        $productModel = new \app\bi\model\Product();
        $param = $this->param;
        $userList = $productModel->getSortByProduct($param);
        foreach ($userList as $key => $value) {
            $user = $userModel->getDataById($value['owner_user_id']);
            $userList[$key]['user_name'] = $user['realname'];
            $userList[$key]['structure_name'] = $user['structure_name'];
        }
        return resultArray(['data' => $userList]);
    }

    public function com($param,$type='')
    {
        $userModel = new \app\admin\model\User();
        //员工IDS
        $map_user_ids = [];
        if ($param['user_id']) {
            $map_user_ids = array($param['user_id']);
        } else {
            if ($param['structure_id']) {
                $map_user_ids = $userModel->getSubUserByStr($param['structure_id'], 2);
            }
        }
        $userIds = $map_user_ids ? $map_user_ids : []; //数组交集
        $create_time = [];
        if(empty($param['type']) && empty($param['start_time'])){
            $param['type'] = 'month';
        }
        if($type == 'contract'){
            $where_time = 'order_date';
        }else if ($type == 'record') {
            $where_time = 'create_time';
        }else{
            $where_time = 'start_time';
        }
        //时间戳：新增客户排行
        if(!empty($param['start_time'])){
            $whereArr[$where_time] = array('between',array($param['start_time'],$param['end_time']));
        }else{
            $create_time = getTimeByType($param['type']);
            if ($create_time) {
                if($type == 'contract'){
                    $whereArr[$where_time] = array('between',array(date('Y-m-d',$create_time[0]),date('Y-m-d',$create_time[1])));
                }else{
                    $whereArr[$where_time] = array('between',array($create_time[0],$create_time[1]));
                }
            }
        }
        $whereArr['create_user_id'] = array('in',$userIds);
        return $whereArr;
    }
}