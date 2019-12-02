<?php
// +----------------------------------------------------------------------
// | Description: 审批
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\oa\controller;

use app\admin\controller\ApiCommon;
use app\admin\model\Message;
use app\admin\model\User;
use think\Hook;
use think\Request;
use think\Db;

class Examine extends ApiCommon
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
            'allow'=>['index','save','read','update','delete','categorylist','check','revokecheck','category','categorysave','categoryupdate','categorydelete','categoryenables']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
        //权限判断
        $unAction = ['index','save','read','update','delete','categorylist','check','revokecheck'];
        if (!in_array($a, $unAction) && !checkPerByAction('admin', 'oa', 'examine')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }               
    }

    /**
     * 审批列表
     * @author Michael_xu
     * @return 
     */
    public function index()
    {
        $examineModel = model('Examine');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        
        $data = $examineModel->getDataList($param);       
        return resultArray(['data' => $data]);
    }

    /**
     * 添加审批
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function save()
    {
        $examineModel = model('Examine');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $category_id = $param['category_id'];

        //审核判断（是否有符合条件的审批流）
        $examineFlowModel = new \app\admin\model\ExamineFlow();
        $examineStepModel = new \app\admin\model\ExamineStep();
        if (!$examineFlowModel->checkExamine($param['create_user_id'], 'oa_examine', $category_id)) {
            return resultArray(['error' => '暂无审批人，无法创建']); 
        }
        //获取审批相关信息
        $examineFlowData = $examineFlowModel->getFlowByTypes($param['create_user_id'], 'oa_examine', $category_id);
        if (!$examineFlowData) {
            return resultArray(['error' => '无可用审批流，请联系管理员']);
        }
        $param['flow_id'] = $examineFlowData['flow_id'];
        //获取审批人信息
        if ($examineFlowData['config'] == 1) {
            //固定审批流
            $nextStepData = $examineStepModel->nextStepUser($userInfo['id'], $examineFlowData['flow_id'], 'oa_examine', 0, 0, 0);
            $check_user_id = $nextStepData['next_user_ids'] ? : '';
            $param['order_id'] = 1;
        } else {
            $check_user_id = $param['check_user_id'] ? ','.$param['check_user_id'].',' : '';
        }
        if (!$check_user_id) {
            return resultArray(['error' => '无可用审批人，请联系管理员']);
        }
        $param['check_user_id'] = is_array($check_user_id) ? ','.implode(',',$check_user_id).',' : $check_user_id; 
        //流程审批人
        // $flow_user_id = $examineFlowModel->getUserByFlow($examineFlowData['flow_id'], $userInfo['id']); 
        // $param['flow_user_id'] = $flow_user_id ? arrayToString($flow_user_id) : ''; 
        $res = $examineModel->createData($param);
        if ($res) {
            $categoryModel = new \app\oa\model\ExamineCategory();
            $categoryInfo = $categoryModel->getDataById($category_id);
            actionLog($res['examine_id'], '', '', '创建了审批');
            return resultArray(['data' => '添加成功']);
        } else {
        	return resultArray(['error' => $examineModel->getError()]);
        }
    }

    /**
     * 审批详情
     * @author Michael_xu
     * @param  
     * @return
     */
    public function read()
    {
        $examineModel = model('Examine');
        $userModel = new \app\admin\model\User();
        $examineFlowModel = new \app\admin\model\ExamineFlow();
        $param = $this->param;
        $userInfo = $this->userInfo;
        $data = $examineModel->getDataById($param['id']);
        //权限判断(创建人、审批人、管理员)
        $adminIds = $userModel->getAdminId(); //管理员
        $checkUserIds = $examineFlowModel->getUserByFlow($data['flow_id'], $data['create_user_id'], $data['check_user_id']);
        if (($userInfo['id'] != $data['create_user_id']) && !in_array($userInfo['id'],$adminIds) && !in_array($userInfo['id'],$checkUserIds)) {
            return resultArray(['error' => '没有权限']);
        }
        if (!$data) {
            return resultArray(['error' => $examineModel->getError()]);
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 编辑审批
     * @author Michael_xu
     * @param 
     * @return
     */
    public function update()
    {
        $examineModel = model('Examine');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $dataInfo = db('oa_examine')->where(['examine_id' => $param['id']])->find();
        if (!$dataInfo) {
            return resultArray(['error' => '数据不存在或已删除']); 
        }
        //权限判断
        if ($userInfo['id'] != $dataInfo['create_user_id']) {
            return resultArray(['error' => '没有权限']);
        }
        if (!in_array($dataInfo['check_status'],['3','4'])) {
            return resultArray(['error' => '当前状态为审批中或已审批通过，不可编辑']);
        }
        //审核判断（是否有符合条件的审批流）
        $examineFlowModel = new \app\admin\model\ExamineFlow();
        $examineStepModel = new \app\admin\model\ExamineStep();
        if (!$examineFlowModel->checkExamine($dataInfo['create_user_id'], 'oa_examine', $dataInfo['category_id'])) {
            return resultArray(['error' => '暂无审批人，无法创建']); 
        }
        //获取审批相关信息
        $examineFlowData = $examineFlowModel->getFlowByTypes($dataInfo['create_user_id'], 'oa_examine', $dataInfo['category_id']);
        if (!$examineFlowData) {
            return resultArray(['error' => '无可用审批流，请联系管理员']);
        }
        $param['flow_id'] = $examineFlowData['flow_id'];
        //获取审批人信息
        if ($examineFlowData['config'] == 1) {
            //固定审批流
            $nextStepData = $examineStepModel->nextStepUser($dataInfo['create_user_id'], $examineFlowData['flow_id'], 'oa_examine', 0, 0, 0);
            $next_user_ids = arrayToString($nextStepData['next_user_ids']) ? : '';
            $check_user_id = $next_user_ids ? : '';
            $param['order_id'] = 1;
        } else {
            $check_user_id = $param['check_user_id'] ? ','.$param['check_user_id'].',' : '';
        }
        if (!$check_user_id) {
            return resultArray(['error' => '无可用审批人，请联系管理员']);
        }
        $param['check_user_id'] = is_array($check_user_id) ? ','.implode(',',$check_user_id).',' : $check_user_id; 
        $param['check_status'] = 0;
        //流程审批人
        // $flow_user_id = $examineFlowModel->getUserByFlow($examineFlowData['flow_id'], $dataInfo['create_user_id']); 
        // $param['flow_user_id'] = $flow_user_id ? arrayToString($flow_user_id) : ''; 
        $param['flow_user_id'] = '';                   

        $res = $examineModel->updateDataById($param, $param['id']);
        if ($res) {
            //将审批记录至为无效
            $examineRecordModel = new \app\admin\model\ExamineRecord();
            $examineRecordModel->setEnd(['types' => 'oa_examine','types_id' => $param['id']]);            
            $categoryModel = new \app\oa\model\ExamineCategory();
            $categoryInfo = $categoryModel->getDataById($dataInfo['category_id']);            
            actionLog($param['id'], '', '', '新建了审批');
            return resultArray(['data' => '编辑成功']);
        } else {
        	return resultArray(['error' => $examineModel->getError()]);
        } 
    }

    /**
     * 删除审批（逻辑删）
     * @author Michael_xu
     * @param 
     * @return
     */
    public function delete()
    {
        $examineModel = model('Examine');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $dataInfo = db('oa_examine')->where(['examine_id' => $param['id']])->find();
        if (!$dataInfo) {
            return resultArray(['error' => '数据不存在或已删除']); 
        }
        $adminTypes = adminGroupTypes($userInfo['id']);
        if (in_array($dataInfo['check_status'], [2,3])) {
            return resultArray(['error' => '已审批，不可删除']);  
        }
        if (!in_array($dataInfo['check_status'],['4']) && !in_array(1,$adminTypes)) {
            return resultArray(['error' => '不可删除，请先撤销审核']);  
        }         
        //权限判断
        if ($userInfo['id'] != $dataInfo['create_user_id']) {
            return resultArray(['error' => '无权操作']);
        }
        $data = $examineModel->delDataById($param['id']);
        if (!$data) {
            return resultArray(['error' => $examineModel->getError()]);
        } 
        $fileModel = new \app\admin\model\File();
        //删除关联附件
        $fileModel->delRFileByModule('oa_examine',$param['id']);    
        actionLog($param['id'], '', '', '删除了审批');
        return resultArray(['data' => '删除成功']);
    }

    /**
     * 审批类型(列表)
     * @author Michael_xu
     * @param 
     * @return
     */ 
    public function category()
    {
        $categoryModel = model('ExamineCategory');
        $param = $this->param;
        $data = $categoryModel->getDataList($param);       
        return resultArray(['data' => $data]);
    }

    /**
     * 审批类型(创建)
     * @author Michael_xu
     * @param 
     * @return
     */ 
    public function categorySave()
    {
        $categoryModel = model('ExamineCategory');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        
        $res = $categoryModel->createData($param);
        if ($res) {
            return resultArray(['data' => $res]);
        } else {
            return resultArray(['error' => $categoryModel->getError()]);
        }
    }

    /**
     * 审批类型(编辑)
     * @author Michael_xu
     * @param 
     * @return
     */
    public function categoryUpdate()
    {
        $categoryModel = model('ExamineCategory');
        $examineFlowModel = new \app\admin\model\ExamineFlow();
        $examineStepModel = new \app\admin\model\ExamineStep();     
        $param = $this->param;
        $userInfo = $this->userInfo;

        $category_id = $param['id'];
        $dataInfo = $categoryModel->getDataById($category_id);
        if (!$dataInfo) {
            return resultArray(['error' => '数据不存在或已删除']);
        } 
        //将当前审批流标记为已删除，重新创建审批流(目的：保留审批流程记录)
        // $newData = db('admin_examine_flow')->where(['flow_id' => $dataInfo['flow_id']])->find();
        
        $param['name'] = $param['title'].'流程';
        $param['types'] = 'oa_examine';        
        $param['types_id'] = $category_id;        
        $param['user_ids'] = arrayToString($param['user_ids']);
        $param['structure_ids'] = arrayToString($param['structure_ids']);        
        $param['update_user_id'] = $userInfo['id'];
        $param['create_time'] = time();
        $param['update_time'] = time();
        $param['status'] = 1;
        $resUpdate = $examineFlowModel->createData($param);

        if ($resUpdate) {
            if ($param['config'] == 1) {
                $resStep = $examineStepModel->createStepData($param['step'], $resUpdate['flow_id']);
                if (!$resStep) {
                    return resultArray(['error' => $examineStepModel->getError()]);
                }  
            }
			if ($dataInfo['flow_id']) {
				$upData = [];
	            $upData['is_deleted'] = 1;      
	            $upData['delete_time'] = time();      
	            $upData['delete_user_id'] = $userInfo['id'];      
	            $upData['status'] = 0;
	            $resFlow = db('admin_examine_flow')->where(['flow_id' => $dataInfo['flow_id']])->update($upData);
	            if (!$resFlow) {
	                return resultArray(['error' => '编辑失败']);
	            }
	        }            
            
            $param['flow_id'] = $resUpdate['flow_id'];
            $res = $categoryModel->updateDataById($param, $param['id']);
            if (!$res) {
                return resultArray(['error' => $categoryModel->getError()]);
            }
            return resultArray(['data' => '编辑成功']);
        } else {
            return resultArray(['error' => $examineFlowModel->getError()]);
        } 
    }

    /**
     * 审批类型（逻辑删）
     * @author Michael_xu
     * @param 
     * @return
     */
    public function categoryDelete()
    {
        $categoryModel = model('ExamineCategory');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $data = $categoryModel->signDelById($param['id'], $userInfo['id']);
        if (!$data) {
            return resultArray(['error' => $categoryModel->getError()]);
        }
        return resultArray(['data' => '删除成功']);
    }

    /**
     * 审批类型状态（启用、停用）
     * @author Michael_xu
     * @param ids array
     * @param status 1启用，0禁用
     * @return
     */    
    public function categoryEnables()
    {
        $categoryModel = model('ExamineCategory');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $id = [$param['id']];
        $data = $categoryModel->enableDatas($id, $param['status']);  
        if (!$data) {
            return resultArray(['error' => $categoryModel->getError()]);
        } 
        return resultArray(['data' => '操作成功']);         
    }      

    /**
     * 审批类型列表(创建时)
     * @author Michael_xu
     * @return 
     */
    public function categoryList()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $where = [];
        $where['is_deleted'] = ['neq',1];
        $where['status'] = ['eq',1];
        $list = db('oa_examine_category')
                ->where($where)
                ->where(function ($query) use ($userInfo){
                    $query->where('`user_ids` = "" AND `structure_ids` = ""')
                    ->whereOr(function($query) use ($userInfo){
                        $query->where('structure_ids','like','%,'.$userInfo['structure_id'].',%')
                              ->whereOr('user_ids','like','%,'.$userInfo['id'].',%');
                    });
                })->select();
        return resultArray(['data' => $list]);        
    } 

    /**
     * 审批审核
     * @author Michael_xu
     * @param 
     * @return
     */  
    public function check()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $user_id = $userInfo['id'];
        $examineModel = model('Examine');
        $examineStepModel = new \app\admin\model\ExamineStep();
        $examineRecordModel = new \app\admin\model\ExamineRecord();
        $examineFlowModel = new \app\admin\model\ExamineFlow();

        $examineData = [];
        $examineData['update_time'] = time();
        $examineData['check_status'] = 1; //0待审核，1审核通中，2审核通过，3审核未通过
        //权限判断
        if (!$examineStepModel->checkExamine($user_id, 'oa_examine', $param['id'])) {
           return resultArray(['error' => $examineStepModel->getError()]); 
        };
        //审批主体详情
        $dataInfo = $examineModel->getDataById($param['id']);
        $flowInfo = $examineFlowModel->getDataById($dataInfo['flow_id']);
        $is_end = 0; // 1审批结束

        $status = $param['status'] ? 1 : 0; //1通过，0驳回
        $checkData = [];
        $checkData['check_user_id'] = $user_id;
        $checkData['types'] = 'oa_examine';
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
                $nextStepData = $examineStepModel->nextStepUser($dataInfo['create_user_id'], $dataInfo['flow_id'], 'oa_examine', $param['id'], $dataInfo['order_id'], $user_id);
                $next_user_ids = $nextStepData['next_user_ids'] ? : [];
                $examineData['order_id'] = $nextStepData['order_id'] ? : '';
                if (!$next_user_ids) {
                    $is_end = 1;
                    //审批结束
                    $checkData['check_status'] = !empty($status) ? 2 : 3;
                    $examineData['check_user_id'] = '';
                } else {
                    //修改主体相关审批信息
                    $examineData['check_user_id'] = arrayToString($next_user_ids);
                }                 
            } else {
                //自选流程
                $is_end = $param['is_end'] ? 1 : '';
                $check_user_id = $param['check_user_id'] ? : '';
                if ($is_end !== 1 && empty($check_user_id)) {
                    return resultArray(['error' => '请选择下一审批人']); 
                }
                $examineData['check_user_id'] = arrayToString($param['check_user_id']);
            } 
            if ($is_end == 1) {
                $checkData['check_status'] = !empty($status) ? 2 : 3;
                $examineData['check_user_id'] = '';
                $examineData['check_status'] = 2;
            }                     
        } else {
            //审批驳回
            $is_end = 1;
            $examineData['check_status'] = 3;
            //将审批记录至为无效
            // $examineRecordModel->setEnd(['types' => 'oa_examine','types_id' => $param['id']]);                           
        }
        //已审批人ID
        $examineData['flow_user_id'] = stringToArray($dataInfo['flow_user_id']) ? arrayToString(array_merge(stringToArray($dataInfo['flow_user_id']),[$user_id])) : arrayToString([$user_id]);
        $resExamine = db('oa_examine')->where(['examine_id' => $param['id']])->update($examineData);
        if ($resExamine) {
            //审批记录
            $resRecord = $examineRecordModel->createData($checkData);
            //审核通过
            if ($is_end == 1 && !empty($status)) {
                // 审批通过消息告知审批提交人
                (new Message())->send(
					Message::EXAMINE_PASS,
					[
						'title' => $dataInfo['category_name'],
						'action_id' => $param['id']
					],
					$dataInfo['create_user_id']
				);
            } else {
                if ($status) {
                    // 通过后发送消息给下一审批人
                    (new Message())->send(
                        Message::EXAMINE_TO_DO,
                        [
                            'from_user' => User::where(['id' => $dataInfo['create_user_id']])->value('realname'),
                            'title' => $dataInfo['category_name'],
                            'action_id' => $param['id']
                        ],
                        stringToArray($examineData['check_user_id'])
                    );
                } else {
                    // 审批驳回消息告知审批提交人
                    (new Message())->send(
                        Message::EXAMINE_REJECT,
                        [
                            'title' => $dataInfo['category_name'],
                            'action_id' => $param['id']
                        ],
                        $dataInfo['create_user_id']
                    );
                }                
            }
            return resultArray(['data' => '审批成功']);            
        } else {
            return resultArray(['error' => '审批失败，请重试！']); 
        }
    }

    /**
     * 审批撤销审核
     * @author Michael_xu
     * @param 
     * @return
     */  
    public function revokeCheck()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $user_id = $userInfo['id'];
        $examine_id = $param['id'];
        $examineModel = model('Examine');
        $examineRecordModel = new \app\admin\model\ExamineRecord();
        $userModel = new \app\admin\model\User();

        $examineData = [];
        $examineData['update_time'] = time();
        $examineData['check_status'] = 0; //0待审核，1审核通中，2审核通过，3审核未通过
        //审批主体详情
        $dataInfo = db('oa_examine')->where(['examine_id' => $examine_id])->find();      
        //权限判断(创建人或管理员)
        if ($dataInfo['check_status'] == 2) {
            return resultArray(['error' => '已审批结束,不能撤销']);   
        } 
        if ($dataInfo['check_status'] == 4) {
            return resultArray(['error' => '无需撤销']);   
        }  
        $admin_user_ids = $userModel->getAdminId(); 
        if ($dataInfo['create_user_id'] !== $user_id && !in_array($user_id, $admin_user_ids)) {
            return resultArray(['error' => '没有权限']);
        }    
        
        $is_end = 0; // 1审批结束
        $status = 2; //1通过，0驳回, 2撤销
        $checkData = [];
        $checkData['check_user_id'] = $user_id;
        $checkData['types'] = 'oa_examine';
        $checkData['types_id'] = $param['id'];
        $checkData['check_time'] = time();
        $checkData['content'] = $param['content'];
        $checkData['flow_id'] = $dataInfo['flow_id'];
        $checkData['order_id'] = $dataInfo['order_id'];
        $checkData['status'] = $status;
        
        $examineData['check_status'] = 4;
        $examineData['check_user_id'] = '';
        $examineData['flow_user_id'] = '';
        $resExamine = db('oa_examine')->where(['examine_id' => $examine_id])->update($examineData);
        if ($resExamine) {
            //将审批记录至为无效
            $examineRecordModel->setEnd(['types' => 'oa_examine','types_id' => $examine_id]);
            //审批记录
            $resRecord = $examineRecordModel->createData($checkData);
            return resultArray(['data' => '撤销成功']);            
        } else {
            return resultArray(['error' => '撤销失败，请重试！']); 
        }
    }     
}
