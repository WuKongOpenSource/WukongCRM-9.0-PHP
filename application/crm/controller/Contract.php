<?php
// +----------------------------------------------------------------------
// | Description: 合同
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
            'allow'=>['check','revokecheck','product']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    } 

    /**
     * 合同列表
     * @author Michael_xu
     * @return 
     */
    public function index()
    {
        $contractModel = model('Contract');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $data = $contractModel->getDataList($param);       
        return resultArray(['data' => $data]);
    }

    /**
     * 添加合同
     * @author Michael_xu
     * @param  
     * @return 
     */
    public function save()
    {
        $contractModel = model('Contract');
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
            if (!$examineFlowModel->checkExamine($param['create_user_id'], 'crm_contract')) {
                return resultArray(['error' => '暂无审批人，无法创建']); 
            }
            //添加审批相关信息
            $examineFlowData = $examineFlowModel->getFlowByTypes($param['create_user_id'], 'crm_contract');
            if (!$examineFlowData) {
                return resultArray(['error' => '无可用审批流，请联系管理员']);
            }
            $param['flow_id'] = $examineFlowData['flow_id'];
            //获取审批人信息
            if ($examineFlowData['config'] == 1) {
                //固定审批流
                $nextStepData = $examineStepModel->nextStepUser($userInfo['id'], $examineFlowData['flow_id'], 'crm_contract', 0, 0, 0);
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

        if ($contractModel->createData($param)) {
            return resultArray(['data' => '添加成功']);
        } else {
            return resultArray(['error' => $contractModel->getError()]);
        }
    }

    /**
     * 合同详情
     * @author Michael_xu
     * @param  
     * @return 
     */
    public function read()
    {
        $contractModel = model('Contract');
        $userModel = new \app\admin\model\User();
        $receivablesModel = new \app\crm\model\Receivables();
        $param = $this->param;
        $userInfo = $this->userInfo;
        $data = $contractModel->getDataById($param['id']);
        //判断权限
        $auth_user_ids = $userModel->getUserByPer('crm', 'contract', 'read');
        //读权限
        $roPre = $userModel->rwPre($userInfo['id'], $data['ro_user_id'], $data['rw_user_id'], 'read');
        $rwPre = $userModel->rwPre($userInfo['id'], $data['ro_user_id'], $data['rw_user_id'], 'update');                
        if (!in_array($data['owner_user_id'],$auth_user_ids) && !$roPre && !$rwPre) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }         
        if (!$data) {
            return resultArray(['error' => $contractModel->getError()]);
        }
        //回款信息用来作废时二次确认
        $auth_user_ids = $userModel->getUserByPer('crm', 'receivables', 'index');
        
        $where['contract_id'] = $param['id'];
        $where['pageType'] = 'all';
        $where['user_id'] = $userInfo['id']; 
        $receivablesData = $receivablesModel->getDataList($where);
        $receivablesData = $receivablesModel
            ->where([
                'contract_id' => $param['id'],
                'owner_user_id' => ['IN', $auth_user_ids]
            ])
            ->count(); 
        $data['receivablesDataCount'] = $receivablesData;
        return resultArray(['data' => $data]);
    }

    /**
     * 编辑合同
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function update()
    {    
        $contractModel = model('Contract');
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $dataInfo = $contractModel->getDataById($param['id']);
        if (!$dataInfo) {
            return resultArray(['error' => '数据不存在或已删除']); 
        }
        //判断权限
        $auth_user_ids = $userModel->getUserByPer('crm', 'contract', 'update');
        //读写权限
        $rwPre = $userModel->rwPre($userInfo['id'], $dataInfo['ro_user_id'], $dataInfo['rw_user_id'], 'update');        
        if (!in_array($dataInfo['owner_user_id'],$auth_user_ids) && !$rwPre) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }        
        
        //已进行审批，不能编辑
        if (!in_array($dataInfo['check_status'],['3','4','5'])) {
            return resultArray(['error' => '当前状态为审批中或已审批通过，不可编辑']);
        }
        //将合同审批状态至为待审核，提交后重新进行审批
        //审核判断（是否有符合条件的审批流）
        $examineFlowModel = new \app\admin\model\ExamineFlow();
        $examineStepModel = new \app\admin\model\ExamineStep();
        if (!$examineFlowModel->checkExamine($dataInfo['owner_user_id'], 'crm_contract')) {
            return resultArray(['error' => '暂无审批人，无法创建']); 
        }
        //添加审批相关信息
        $examineFlowData = $examineFlowModel->getFlowByTypes($dataInfo['owner_user_id'], 'crm_contract');
        if (!$examineFlowData) {
            return resultArray(['error' => '无可用审批流，请联系管理员']);
        }
        $param['flow_id'] = $examineFlowData['flow_id'];
        //获取审批人信息
        if ($examineFlowData['config'] == 1) {
            //固定审批流
            $nextStepData = $examineStepModel->nextStepUser($dataInfo['owner_user_id'], $examineFlowData['flow_id'], 'crm_contract', 0, 0, 0);
            $next_user_ids = arrayToString($nextStepData['next_user_ids']) ? : '';
            $check_user_id = $next_user_ids ? : [];
            $param['order_id'] = 1;
        } else {
            $check_user_id = $param['check_user_id'] ? ','.$param['check_user_id'].',' : '';
        }
        if ($param['is_draft']) {
            //保存为草稿
            $param['check_status'] = 5;
            $param['check_user_id'] = $param['check_user_id'] ? ','.$param['check_user_id'].',' : '';           
        } else {
            if (!$check_user_id) {
                return resultArray(['error' => '无可用审批人，请联系管理员']);
            }
            $param['check_user_id'] = is_array($check_user_id) ? ','.implode(',',$check_user_id).',' : $check_user_id;
            $param['check_status'] = 0;
        }
        $param['flow_user_id'] = '';   

        if ($contractModel->updateDataById($param, $param['id'])) {
            //将审批记录至为无效
            $examineRecordModel = new \app\admin\model\ExamineRecord();
            $examineRecordModel->setEnd(['types' => 'crm_contract','types_id' => $param['id']]);            
            return resultArray(['data' => '编辑成功']);
        } else {
            return resultArray(['error' => $contractModel->getError()]);
        }       
    }

    /**
     * 删除合同（逻辑删）
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function delete()
    {
        $param = $this->param; 
        $userInfo = $this->userInfo;  
        $contractModel = model('Contract');     
        $recordModel = new \app\admin\model\Record(); 
        $fileModel = new \app\admin\model\File();
        $actionRecordModel = new \app\admin\model\ActionRecord();
        if (!is_array($param['id'])) {
            $contract_id = [$param['id']];
        } else {
            $contract_id = $param['id'];
        }
        $delIds = [];
        $errorMessage = [];

        //数据权限判断
        $userModel = new \app\admin\model\User();
        $auth_user_ids = $userModel->getUserByPer('crm', 'contract', 'delete');
        $adminTypes = adminGroupTypes($userInfo['id']);
        foreach ($contract_id as $k=>$v) {
            $isDel = true;
            //数据详情
            $data = $contractModel->getDataById($v);
            if (!$data) {
                $isDel = false;
                $errorMessage[] = 'id为'.$v.'的合同删除失败,错误原因：'.$contractModel->getError();
                continue;
            }
            if (!in_array($data['owner_user_id'],$auth_user_ids)) {
                $isDel = false;
                $errorMessage[] = '名称为'.$data['name'].'的合同删除失败,错误原因：无权操作';
                continue;
            }
            if ($data['check_status'] == 6 && !in_array(1,$adminTypes)) {
                $isDel = false;
                $errorMessage[] = '名称为'.$data['name'].'的合同删除失败,错误原因：当前合同已作废，非超级管理员，不可删除';
                continue;
            }
            if (!in_array($data['check_status'],['0','4','5','6']) && !in_array(1,$adminTypes)) {
                $isDel = false;
                $errorMessage[] = '名称为'.$data['name'].'的合同删除失败,错误原因：当前状态为审批中或已审批通过，不可删除';
                continue;
            }            
            $delIds[] = $v;            
        }
        if ($delIds) {
            $data = $contractModel->delDatas($delIds);
            if (!$data) {
                return resultArray(['error' => $contractModel->getError()]);
            }
            //删除跟进记录
            $recordModel->delDataByTypes('crm_contract',$delIds);
            //删除关联附件
            $fileModel->delRFileByModule('crm_contract',$delIds);
            //删除关联操作记录
            $actionRecordModel->delDataById(['types'=>'crm_contract','action_id'=>$delIds]);              
            actionLog($delIds,'','','');                    
        }
        if ($errorMessage) {
            return resultArray(['error' => $errorMessage]);
        } else {
            return resultArray(['data' => '删除成功']);
        }        
    }

    /**
     * 合同转移
     * @author Michael_xu
     * @param owner_user_id 变更负责人
     * @param is_remove 1移出，2转为团队成员
     * @param type 权限 1只读2读写
     * @return
     */ 
    public function transfer()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $contractModel = model('Contract');
        $settingModel = model('Setting');
        $userModel = new \app\admin\model\User();
        $authIds = $userModel->getUserByPer(); //权限范围的user_id

        if (!$param['owner_user_id']) {
            return resultArray(['error' => '变更负责人不能为空']);
        }
        if (!$param['contract_id'] || !is_array($param['contract_id'])) {
            return resultArray(['error' => '请选择需要转移的合同']); 
        }
        
        $is_remove = $param['is_remove'] == 2 ? : 1;
        $type = $param['type'] == 2 ? : 1;
        
        $data = [];
        $data['owner_user_id'] = $param['owner_user_id'];
        $data['update_time'] = time();

        $ownerUserName = $userModel->getUserNameById($param['owner_user_id']);
        $errorMessage = [];
        foreach ($param['contract_id'] as $contract_id) {
            $contractInfo = $contractModel->getDataById($contract_id);
            if (!$contractInfo) {
                $errorMessage[] = '名称:为《'.$contractInfo['name'].'》的合同转移失败，错误原因：数据不存在；';
                continue;
            }
            //权限判断
            if (!in_array($contractInfo['owner_user_id'],$authIds)) {
                $errorMessage[] = $contractInfo['name'].'"转移失败，错误原因：无权限；';
                continue;
            }
            if (!in_array($contractInfo['check_status'],['0','1'])) {
                $errorMessage[] = $contractInfo['name'].'"转移失败，错误原因：审批中或已有审核结果，无法转移；';
                continue;
            }            
            $resContract = db('crm_contract')->where(['contract_id' => $contract_id])->update($data);
            if (!$resContract) {
                $errorMessage[] = $contractInfo['name'].'"转移失败，错误原因：数据出错；';
                continue;
            }
            //修改记录
            updateActionLog($userInfo['id'], 'crm_contract', $contract_id, '', '', '将合同转移给：'.$ownerUserName);        
        }
        if (!$errorMessage) {
            return resultArray(['data' => '转移成功']);
        } else {
            return resultArray(['error' => $errorMessage]);
        }
    }   

    /**
     * 合同审核
     * @author Michael_xu
     * @param 
     * @return
     */  
    public function check()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $user_id = $userInfo['id'];
        $contractModel = model('Contract');
        $examineStepModel = new \app\admin\model\ExamineStep();
        $examineRecordModel = new \app\admin\model\ExamineRecord();
        $examineFlowModel = new \app\admin\model\ExamineFlow();
        $customerModel = model('Customer');

        $contractData = [];
        $contractData['update_time'] = time();
        $contractData['check_status'] = 1; //0待审核，1审核通中，2审核通过，3审核未通过
        //权限判断
        if (!$examineStepModel->checkExamine($user_id, 'crm_contract', $param['id'])) {
           return resultArray(['error' => $examineStepModel->getError()]); 
        };
        //审批主体详情
        $dataInfo = $contractModel->getDataById($param['id']);
        $flowInfo = $examineFlowModel->getDataById($dataInfo['flow_id']);
        $is_end = 0; // 1审批结束

        $status = $param['status'] ? 1 : 0; //1通过，0驳回
        $checkData = [];
        $checkData['check_user_id'] = $user_id;
        $checkData['types'] = 'crm_contract';
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
                $nextStepData = $examineStepModel->nextStepUser($dataInfo['owner_user_id'], $dataInfo['flow_id'], 'crm_contract', $param['id'], $dataInfo['order_id'], $user_id);
                $next_user_ids = $nextStepData['next_user_ids'] ? : [];
                $contractData['order_id'] = $nextStepData['order_id'] ? : '';
                if (!$next_user_ids) {
                    $is_end = 1;
                    //审批结束
                    $checkData['check_status'] = !empty($status) ? 2 : 3;
                    $contractData['check_user_id'] = '';
                } else {
                    //修改主体相关审批信息
                    $contractData['check_user_id'] = arrayToString($next_user_ids);
                }                 
            } else {
                //自选流程
                $is_end = $param['is_end'] ? 1 : '';
                $check_user_id = $param['check_user_id'] ? : '';
                if ($is_end !== 1 && empty($check_user_id)) {
                    return resultArray(['error' => '请选择下一审批人']); 
                }
                $contractData['check_user_id'] = arrayToString($param['check_user_id']);
            } 
            if ($is_end == 1) {
                $checkData['check_status'] = !empty($status) ? 2 : 3;
                $contractData['check_user_id'] = '';
                $contractData['check_status'] = 2;
            }                     
        } else {
            //审批驳回
            $is_end = 1;
            $contractData['check_status'] = 3;
            //将审批记录至为无效
            // $examineRecordModel->setEnd(['types' => 'crm_contract','types_id' => $param['id']]);
        }
        //已审批人ID
        $contractData['flow_user_id'] = stringToArray($dataInfo['flow_user_id']) ? arrayToString(array_merge(stringToArray($dataInfo['flow_user_id']),[$user_id])) : arrayToString([$user_id]);        
        $resContract = db('crm_contract')->where(['contract_id' => $param['id']])->update($contractData);
        if ($resContract) {
            //审批记录
            $resRecord = $examineRecordModel->createData($checkData);
            //审核通过，相关客户状态改为已成交
            if ($is_end == 1 && !empty($status)) {
                // 审批通过消息告知负责人
                (new Message())->send(
					Message::CONTRACT_PASS,
					[
						'title' => $dataInfo['name'],
						'action_id' => $param['id']
					],
					$dataInfo['owner_user_id']
				);

                $customerData = [];
                $customerData['deal_status'] = '已成交';
                $customerData['deal_time'] = time();
                $customerData['is_lock'] = 0;
                db('crm_customer')->where(['customer_id' => $dataInfo['customer_id']])->update($customerData);
            } else {
                if ($status) {
                    //发送站内信
                    // 通过未完成，发送消息给
                    (new Message())->send(
                        Message::CONTRACT_TO_DO,
                        [
                            'from_user' => User::where(['id' => $dataInfo['owner_user_id']])->value('realname'),
                            'title' => $dataInfo['name'],
                            'action_id' => $param['id']
                        ],
                        stringToArray($contractData['check_user_id'])
                    );
                } else {
                    (new Message())->send(
                        Message::CONTRACT_REJECT,
                        [
                            'title' => $dataInfo['name'],
                            'action_id' => $param['id']
                        ],
                        $dataInfo['owner_user_id']
                    );
                }          
            }
            return resultArray(['data' => '审批成功']);            
        } else {
            return resultArray(['error' => '审批失败，请重试！']); 
        }
    }

    /**
     * 合同撤销审核
     * @author Michael_xu
     * @param 
     * @return
     */  
    public function revokeCheck()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $user_id = $userInfo['id'];
        $contractModel = model('Contract');
        $examineRecordModel = new \app\admin\model\ExamineRecord();
        $customerModel = model('Customer');
        $userModel = new \app\admin\model\User();

        $contractData = [];
        $contractData['update_time'] = time();
        $contractData['check_status'] = 0; //0待审核，1审核通中，2审核通过，3审核未通过
        //审批主体详情
        $dataInfo = $contractModel->getDataById($param['id']);        
        //权限判断(负责人或管理员)        
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
        $checkData['types'] = 'crm_contract';
        $checkData['types_id'] = $param['id'];
        $checkData['check_time'] = time();
        $checkData['content'] = $param['content'];
        $checkData['flow_id'] = $dataInfo['flow_id'];
        $checkData['order_id'] = $dataInfo['order_id'];
        $checkData['status'] = $status;
        
        $contractData['check_status'] = 4;
        $contractData['check_user_id'] = '';
        $examineData['flow_user_id'] = '';
        $resContract = db('crm_contract')->where(['contract_id' => $param['id']])->update($contractData);
        if ($resContract) {
            //将审批记录至为无效
            // $examineRecordModel->setEnd(['types' => 'crm_contract','types_id' => $param['id']]);
            //审批记录
            $resRecord = $examineRecordModel->createData($checkData);
            return resultArray(['data' => '撤销成功']);            
        } else {
            return resultArray(['error' => '撤销失败，请重试！']); 
        }
    } 

    /**
     * 相关产品
     * @author Michael_xu
     * @param 
     * @return
     */ 
    public function product()
    {
        $productModel = model('Product');
        $contractModel = model('Contract');
        $userModel = new \app\admin\model\User();
        $param = $this->param;
        $userInfo = $this->userInfo;
        if (!$param['contract_id']) {
            return resultArray(['error' => '参数错误']);
        }
        $contractInfo = db('crm_contract')->where(['contract_id' => $param['contract_id']])->find();      
        //判断权限
        $auth_user_ids = $userModel->getUserByPer('crm', 'contract', 'read');
        //读写权限
        $roPre = $userModel->rwPre($userInfo['id'], $contractInfo['ro_user_id'], $contractInfo['rw_user_id'], 'read');
        $rwPre = $userModel->rwPre($userInfo['id'], $contractInfo['ro_user_id'], $contractInfo['rw_user_id'], 'update');
        if (!in_array($contractInfo['owner_user_id'],$auth_user_ids) && !$roPre && !$rwPre) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }        
        $dataList = db('crm_contract_product')->where(['contract_id' => $param['contract_id']])->select();
        foreach ($dataList as $k=>$v) {
            $where = [];
            $where['product_id'] = $v['product_id'];
            $productInfo = db('crm_product')->where($where)->field('name,category_id')->find();
            $category_name = db('crm_product_category')->where(['category_id' => $productInfo['category_id']])->value('name');
            $dataList[$k]['name'] = $productInfo['name'] ? : '';
            $dataList[$k]['category_id_info'] = $category_name ? : '';
        }
        $list['list'] = $dataList ? : [];
        $list['total_price'] = $contractInfo['total_price'] ? : '0.00';
        $list['discount_rate'] = $contractInfo['discount_rate'] ? : '0.00';
        return resultArray(['data' => $list]);
    }       

    /**
     * 导出
     * @author Michael_xu
     * @param 
     * @return
     */
    public function excelExport()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        if ($param['contract_id']) {
           $param['contract_id'] = ['condition' => 'in','value' => $param['contract_id'],'form_type' => 'text','name' => ''];
           $param['is_excel'] = 1;
        }
        $excelModel = new \app\admin\model\Excel();
        // 导出的字段列表
        $fieldModel = new \app\admin\model\Field();
        $field_list = $fieldModel->getIndexFieldConfig('crm_contract', $userInfo['id']);
        // 文件名
        $file_name = '5kcrm_contract_'.date('Ymd');

        $model = model('Contract');
        $temp_file = $param['temp_file'];
        unset($param['temp_file']);
        $page = $param['page'] ?: 1;
        unset($param['page']);
        unset($param['export_queue_index']);
        return $excelModel->batchExportCsv($file_name, $temp_file, $field_list, $page, function($page, $limit) use ($model, $param, $field_list) {
            $param['page'] = $page;
            $param['limit'] = $limit;
            $data = $model->getDataList($param);
            $data['list'] = $model->exportHandle($data['list'], $field_list, 'contract');
            return $data;
        });
    }  

     /**
     * 修改已审核过的合同为作废状态
     * @author ZFH
     * @param 
     * @return
     */ 
    public function cancel()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $userModel = new \app\admin\model\User();
        $adminTypes = adminGroupTypes($userInfo['id']);

        if (!$param['contract_id']) {
            return resultArray(['error' => '参数错误']);
        }
         //判断权限
        $auth_user_ids = $userModel->getUserByPer('crm', 'contract', 'cancel');
        $contractInfo = db('crm_contract')->where(['contract_id' => $param['contract_id']])->find();
        if(!$contractInfo){
            return resultArray(['error' => '数据不存在']);
        }
        if (!in_array($contractInfo['owner_user_id'],$auth_user_ids)) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }
        if($contractInfo['check_status'] != 2){
            return resultArray(['error' => '未审核通过的合同不可作废,请选择撤销或删除']);
        }
        $data['check_status'] = 6;       //变更合同状态为作废
        if(db('crm_contract')->where(['contract_id'=>$param['contract_id']])->update($data)){
            return resultArray(['data' => '作废成功']);
        }else{
            return resultArray(['error' => '失败，请重试']);
        }
    }
}
