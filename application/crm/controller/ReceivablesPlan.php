<?php
// +----------------------------------------------------------------------
// | Description: 回款计划
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;

class ReceivablesPlan extends ApiCommon
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
            'allow'=>['index','save','read','update','delete']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    } 

    /**
     * 回款计划列表
     * @author Michael_xu
     * @return 
     */
    public function index()
    {
        $receivablesPlanModel = model('ReceivablesPlan');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $data = $receivablesPlanModel->getDataList($param);       
        return resultArray(['data' => $data]);
    }

    /**
     * 添加回款计划
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function save()
    {
        $receivablesPlanModel = model('ReceivablesPlan');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $param['owner_user_id'] = $userInfo['id'];

        $res = $receivablesPlanModel->createData($param);
        if ($res) {
            return resultArray(['data' => '添加成功']);
        } else {
            return resultArray(['error' => $receivablesPlanModel->getError()]);
        }
    }

    /**
     * 回款计划详情
     * @author Michael_xu
     * @param  
     * @return 
     */
    public function read()
    {
        $receivablesPlanModel = model('ReceivablesPlan');
        $param = $this->param;
        $data = $receivablesPlanModel->getDataById($param['id']);
        if (!$data) {
            return resultArray(['error' => $receivablesPlanModel->getError()]);
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 编辑回款计划
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function update()
    {    
        $receivablesPlanModel = model('ReceivablesPlan');
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $userInfo = $this->userInfo;
        $plan_id = $param['id'];

        $dataInfo = db('crm_receivables_plan')->where(['plan_id' => $plan_id])->find();
        //根据合同权限判断
        $contractData = db('crm_contract')->where(['contract_id' => $dataInfo['contract_id']])->find();
        $auth_user_ids = $userModel->getUserByPer('crm', 'contract', 'update');
        //读写权限
        $rwPre = $userModel->rwPre($userInfo['id'], $contractData['ro_user_id'], $contractData['rw_user_id'], 'update');       
        if (!in_array($contractData['owner_user_id'],$auth_user_ids) && !$rwPre) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        $res = $receivablesPlanModel->updateDataById($param, $param['id']);
        if ($res) {
            return resultArray(['data' => '编辑成功']);
        } else {
            return resultArray(['error' => $receivablesPlanModel->getError()]);
        }       
    } 

    /**
     * 删除回款计划
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function delete()
    {
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $userInfo = $this->userInfo;
        $plan_id = $param['id'];
        if ($plan_id) {
            $dataInfo = db('crm_receivables_plan')->where(['plan_id' => $plan_id])->find();
            if (!$dataInfo) {
                return resultArray(['error' => '数据不存在或已删除']);
            }
            $receivablesInfo = db('crm_receivables')->where(['receivables_id' => $dataInfo['receivables_id']])->find();
            if ($receivablesInfo) {
                return resultArray(['error' => '已关联回款《'.$receivablesInfo['number'].'》，不能删除']);
            }
            //根据合同权限判断
            $contractData = db('crm_contract')->where(['contract_id' => $dataInfo['contract_id']])->find();
            $auth_user_ids = $userModel->getUserByPer('crm', 'contract', 'delete');
            //读写权限
            $rwPre = $userModel->rwPre($userInfo['id'], $contractData['ro_user_id'], $contractData['rw_user_id'], 'update');       
            if (!in_array($contractData['owner_user_id'],$auth_user_ids) && !$rwPre) {
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode(['code'=>102,'error'=>'无权操作']));
            }
            $res = model('ReceivablesPlan')->delDataById($plan_id);
            if (!$res) {
                return resultArray(['error' => model('ReceivablesPlan')->getError()]);
            }
            return resultArray(['data' => '删除成功']);
        } else {
            return resultArray(['error'=>'参数错误']);
        }        
    }     
}
