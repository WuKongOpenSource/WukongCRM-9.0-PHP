<?php
// +----------------------------------------------------------------------
// | Description: 回款
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use app\admin\model\Message;
use app\admin\model\User;
use think\Hook;
use think\Request;
use think\Db;

class Receivables extends ApiCommon
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
            'allow'=>['check','revokecheck']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    } 

    /**
     * 回款列表
     * @author Michael_xu
     * @return 
     */
    public function index()
    {
        $receivablesModel = model('Receivables');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];        
        $data = $receivablesModel->getDataList($param);       
        return resultArray(['data' => $data]);
    }

    /**
     * 添加回款
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function save()
    {
        $receivablesModel = model('Receivables');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $examineStepModel = new \app\admin\model\ExamineStep();
        $param['create_user_id'] = $userInfo['id'];
        $param['owner_user_id'] = $userInfo['id'];

        if ($param['is_draft']) {
            //保存为草稿
            $param['check_status'] = 5; //草稿(未提交)
            $param['check_user_id'] = $param['check_user_id'] ? ','.$param['check_user_id'].',' : '';
        } else {        
            //审核判断（是否有符合条件的审批流）
            $examineFlowModel = new \app\admin\model\ExamineFlow();
            if (!$examineFlowModel->checkExamine($param['owner_user_id'], 'crm_receivables')) {
                return resultArray(['error' => '暂无审批人，无法创建']); 
            }
            //添加审批相关信息
            $examineFlowData = $examineFlowModel->getFlowByTypes($param['owner_user_id'], 'crm_receivables');
            if (!$examineFlowData) {
                return resultArray(['error' => '无可用审批流，请联系管理员']);
            }
            $param['flow_id'] = $examineFlowData['flow_id'];
            //获取审批人信息
            if ($examineFlowData['config'] == 1) {
                //固定审批流
                $nextStepData = $examineStepModel->nextStepUser($userInfo['id'], $examineFlowData['flow_id'], 'crm_receivables', 0, 0, 0);
                $next_user_ids = arrayToString($nextStepData['next_user_ids']) ? : '';
                $check_user_id = $next_user_ids ? : [];
                $param['order_id'] = 1;
            } else {
                $check_user_id = $param['check_user_id'] ? ','.$param['check_user_id'].',' : '';
            }
            if (!$check_user_id) {
                return resultArray(['error' => '无可用审批人，请联系管理员']);
            }
            $param['check_user_id'] = is_array($check_user_id) ? ','.implode(',',$check_user_id).',' : $check_user_id;
        }
        $res = $receivablesModel->createData($param);
        if ($res) {
            //回款计划关联
            if ($param['plan_id']) {
                db('crm_receivables_plan')->where(['plan_id' => $param['plan_id']])->update(['receivables_id' => $res['receivables_id']]);
            }
            return resultArray(['data' => '添加成功']);
        } else {
            return resultArray(['error' => $receivablesModel->getError()]);
        }
    }

    /**
     * 回款详情
     * @author Michael_xu
     * @param  
     * @return 
     */
    public function read()
    {
        $receivablesModel = model('Receivables');
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $data = $receivablesModel->getDataById($param['id']);

        //判断权限
        $auth_user_ids = $userModel->getUserByPer('crm', 'receivables', 'read');
        if (!in_array($data['owner_user_id'],$auth_user_ids)) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }         
        if (!$data) {
            return resultArray(['error' => $receivablesModel->getError()]);
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 编辑回款
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function update()
    {    
        $receivablesModel = model('Receivables');
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        //判断权限
        $dataInfo = $receivablesModel->getDataById($param['id']);
        $auth_user_ids = $userModel->getUserByPer('crm', 'receivables', 'update');
        if (!in_array($dataInfo['owner_user_id'],$auth_user_ids)) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }

        //已进行审批，不能编辑
        if (!in_array($dataInfo['check_status'],['3','4','5'])) {
            return resultArray(['error' => '当前状态为审批中或已审批通过，不可编辑']);
        }
        if ($param['is_draft']) {
            //保存为草稿
            $param['check_status'] = 5;
            $param['check_user_id'] = $param['check_user_id'] ? ','.$param['check_user_id'].',' : '';
        } else {        
            //将回款审批状态至为待审核，提交后重新进行审批
            //审核判断（是否有符合条件的审批流）
            $examineFlowModel = new \app\admin\model\ExamineFlow();
            $examineStepModel = new \app\admin\model\ExamineStep();
            if (!$examineFlowModel->checkExamine($param['user_id'], 'crm_receivables')) {
                return resultArray(['error' => '暂无审批人，无法创建']); 
            }
            //添加审批相关信息
            $examineFlowData = $examineFlowModel->getFlowByTypes($param['user_id'], 'crm_receivables');
            if (!$examineFlowData) {
                return resultArray(['error' => '无可用审批流，请联系管理员']);
            }
            $param['flow_id'] = $examineFlowData['flow_id'];
            //获取审批人信息
            if ($examineFlowData['config'] == 1) {
                //固定审批流
                $nextStepData = $examineStepModel->nextStepUser($dataInfo['owner_user_id'], $examineFlowData['flow_id'], 'crm_receivables', 0, 0, 0);
                $next_user_ids = arrayToString($nextStepData['next_user_ids']) ? : '';
                $check_user_id = $next_user_ids ? : [];
                $param['order_id'] = 1;
            } else {
                $check_user_id = $param['check_user_id'] ? ','.$param['check_user_id'].',' : '';
            }
            if (!$check_user_id) {
                return resultArray(['error' => '无可用审批人，请联系管理员']);
            }
            $param['check_user_id'] = is_array($check_user_id) ? ','.implode(',',$check_user_id).',' : $check_user_id;
            $param['check_status'] = 0;   
            $param['flow_user_id'] = '';  
        }                      

        $res = $receivablesModel->updateDataById($param, $param['id']);
        if ($res) {
            //将审批记录至为无效
            $examineRecordModel = new \app\admin\model\ExamineRecord();
            $examineRecordModel->setEnd(['types' => 'crm_receivables','types_id' => $param['id']]);            
            return resultArray(['data' => '编辑成功']);
        } else {
            return resultArray(['error' => $receivablesModel->getError()]);
        }       
    }

    /**
     * 删除回款
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function delete()
    {
        $receivablesModel = model('Receivables');
        $param = $this->param;
        $userInfo = $this->userInfo;      
        if (!is_array($param['id'])) {
            $receivables_id = [$param['id']];
        } else {
            $receivables_id = $param['id'];
        }
        $delIds = [];
        $errorMessage = [];

        //数据权限判断
        $userModel = new \app\admin\model\User();
        $auth_user_ids = $userModel->getUserByPer('crm', 'receivables', 'delete');
        $adminTypes = adminGroupTypes($userInfo['id']);
        foreach ($receivables_id as $k=>$v) {
            $isDel = true;
            //数据详情
            $data = $receivablesModel->getDataById($v);
            if (!$data) {
                $isDel = false;
                $errorMessage[] = 'id为'.$v.'的回款删除失败,错误原因：'.$receivablesModel->getError();
                continue;
            }
            if (!in_array($data['owner_user_id'],$auth_user_ids)) {
                $isDel = false;
                $errorMessage[] = '名称为'.$data['number'].'的回款删除失败,错误原因：无权操作';
                continue;
            }
            if (!in_array($data['check_status'],['0','4','5']) && !in_array(1,$adminTypes)) {
                $isDel = false;
                $errorMessage[] = '名称为'.$data['number'].'的回款删除失败,错误原因：请先撤销审核';
                continue;
            }            
            $delIds[] = $v;            
        }
        if ($delIds) {
            $data = $receivablesModel->delDatas($delIds);
            if (!$data) {
                return resultArray(['error' => $receivablesModel->getError()]);
            } 
            actionLog($delIds,'','','');         
        }
        if ($errorMessage) {
            return resultArray(['error' => $errorMessage]);
        } else {
            return resultArray(['data' => '删除成功']);
        }            
    }    
	
    /**
     * 回款审核
     * @author Michael_xu
     * @param 
     * @return
     */  
    public function check()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $user_id = $userInfo['id'];
        $receivablesModel = model('Receivables');
        $examineStepModel = new \app\admin\model\ExamineStep();
        $examineRecordModel = new \app\admin\model\ExamineRecord();
        $examineFlowModel = new \app\admin\model\ExamineFlow();

        $receivablesData = [];
        $receivablesData['update_time'] = time();
        $receivablesData['check_status'] = 1; //0待审核，1审核通中，2审核通过，3审核未通过
        //权限判断
        if (!$examineStepModel->checkExamine($user_id, 'crm_receivables', $param['id'])) {
           return resultArray(['error' => $examineStepModel->getError()]); 
        };
        //审批主体详情
        $dataInfo = $receivablesModel->getDataById($param['id']);
        $flowInfo = $examineFlowModel->getDataById($dataInfo['flow_id']);
        $is_end = 0; // 1审批结束

        $status = $param['status'] ? 1 : 0; //1通过，0驳回
        $checkData = [];
        $checkData['check_user_id'] = $user_id;
        $checkData['types'] = 'crm_receivables';
        $checkData['types_id'] = $param['id'];
        $checkData['check_time'] = time();
        $checkData['content'] = $param['content'];
        $checkData['flow_id'] = $dataInfo['flow_id'];
        $checkData['order_id'] = $dataInfo['order_id'] ? : 1;
        $checkData['status'] = $status;
        
        if ($status == 1) {
            if ($flowInfo['config'] == 1) {
                //固定流程
                //获取下一审批信息
                $nextStepData = $examineStepModel->nextStepUser($dataInfo['owner_user_id'], $dataInfo['flow_id'], 'crm_receivables', $param['id'], $dataInfo['order_id'], $user_id);
                $next_user_ids = $nextStepData['next_user_ids'] ? : [];
                $receivablesData['order_id'] = $nextStepData['order_id'] ? : '';
                if (!$next_user_ids) {
                    $is_end = 1;
                    //审批结束
                    $checkData['check_status'] = !empty($status) ? 2 : 3;
                    $receivablesData['check_user_id'] = '';
                } else {
                    //修改主体相关审批信息
                    $receivablesData['check_user_id'] = arrayToString($next_user_ids);
                }                 
            } else {
                //自选流程
                $is_end = $param['is_end'] ? 1 : '';
                $check_user_id = $param['check_user_id'] ? : '';
                if ($is_end !== 1 && empty($check_user_id)) {
                    return resultArray(['error' => '请选择下一审批人']); 
                }
                $receivablesData['check_user_id'] = arrayToString($param['check_user_id']);
            } 
            if ($is_end == 1) {
                $checkData['check_status'] = !empty($status) ? 2 : 3;
                $receivablesData['check_user_id'] = '';
                $receivablesData['check_status'] = 2;
            }                     
        } else {
            //审批驳回
            $is_end = 1;
            $receivablesData['check_status'] = 3;
            //将审批记录至为无效
            // $examineRecordModel->setEnd(['types' => 'crm_receivables','types_id' => $param['id']]);                       
        }
        //已审批人ID
        $receivablesData['flow_user_id'] = stringToArray($dataInfo['flow_user_id']) ? arrayToString(array_merge(stringToArray($dataInfo['flow_user_id']),[$user_id])) : arrayToString([$user_id]);        
        $resReceivables = db('crm_receivables')->where(['receivables_id' => $param['id']])->update($receivablesData);
        if ($resReceivables) {
            if ($status) {
                // 审批通过，通知下一审批人
                (new Message())->send(
                    Message::RECEIVABLES_TO_DO,
                    [
                        'from_user' => User::where(['id' => $dataInfo['owner_user_id']])->value('realname'),
                        'title' => $dataInfo['number'],
                        'action_id' => $param['id']
                    ],
                    stringToArray($receivablesData['check_user_id'])
                );
            } else {
                // 驳回通知负责人
                (new Message())->send(
                    Message::RECEIVABLES_REJECT,
                    [
                        'title' => $dataInfo['number'],
                        'action_id' => $param['id']
                    ],
                    $dataInfo['owner_user_id']
                );
            }

            //审批记录
            $resRecord = $examineRecordModel->createData($checkData);
            
            if ($is_end == 1 && !empty($status)) {
                //发送站内信 通过
                (new Message())->send(
					Message::RECEIVABLES_PASS,
					[
						'title' => $dataInfo['number'],
						'action_id' => $param['id']
					],
					$dataInfo['owner_user_id']
				);
            }
            return resultArray(['data' => '审批成功']);            
        } else {
            return resultArray(['error' => '审批失败，请重试！']); 
        }
    }

    /**
     * 回款撤销审核
     * @author Michael_xu
     * @param 
     * @return
     */  
    public function revokeCheck()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $user_id = $userInfo['id'];
        $receivablesModel = model('Receivables');
        $examineStepModel = new \app\admin\model\ExamineStep();
        $examineRecordModel = new \app\admin\model\ExamineRecord();
        $customerModel = model('Customer');
        $userModel = new \app\admin\model\User();

        $receivablesData = [];
        $receivablesData['update_time'] = time();
        $receivablesData['check_status'] = 0; //0待审核，1审核通中，2审核通过，3审核未通过
        //审批主体详情
        $dataInfo = $receivablesModel->getDataById($param['id']);        
        //权限判断(创建人或负责人或管理员)
        if ($dataInfo['check_status'] == 2) {
            return resultArray(['error' => '已审批结束,不能撤销']);   
        } 
        if ($dataInfo['check_status'] == 4) {
            return resultArray(['error' => '无需撤销']);   
        } 
        $admin_user_ids = $userModel->getAdminId(); 
        if ($dataInfo['owner_user_id'] !== $user_id && !in_array($user_id, $admin_user_ids)) {
            return resultArray(['error' => '没有权限']);
        }     
        
        $is_end = 0; // 1审批结束
        $status = 2; //1通过，0驳回, 2撤销
        $checkData = [];
        $checkData['check_user_id'] = $user_id;
        $checkData['types'] = 'crm_receivables';
        $checkData['types_id'] = $param['id'];
        $checkData['check_time'] = time();
        $checkData['content'] = $param['content'];
        $checkData['flow_id'] = $dataInfo['flow_id'];
        $checkData['order_id'] = $dataInfo['order_id'];
        $checkData['status'] = $status;
        
        $receivablesData['check_status'] = 4;
        $receivablesData['check_user_id'] = '';
        $receivablesData['flow_user_id'] = '';
        $resReceivables = db('crm_receivables')->where(['receivables_id' => $param['id']])->update($receivablesData);
        if ($resReceivables) {
            //将审批记录至为无效
            // $examineRecordModel->setEnd(['types' => 'crm_receivables','types_id' => $param['id']]);
            //审批记录
            $resRecord = $examineRecordModel->createData($checkData);
            return resultArray(['data' => '撤销成功']);            
        } else {
            return resultArray(['error' => '撤销失败，请重试！']); 
        }
    } 
}
