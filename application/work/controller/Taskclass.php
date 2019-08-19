<?php
// +----------------------------------------------------------------------
// | Description: 任务列表
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\work\controller;

use think\Request;
use think\Session;
use think\Hook;
use app\admin\controller\ApiCommon;

class taskclass extends ApiCommon
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
            'allow'=>['index','save','rename','delete']         
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 添加任务列表
     * @author yykun
     * @return
     */ 
    public function save()
    {
        $param = $this->param;
        $workClassModel = model('WorkClass');
        $workModel = model('Work');
        $userInfo = $this->userInfo;
        if (!$param['name']) return resultArray(['error'=>'参数错误']);
        //权限判断
        if (!$workModel->isCheck('work','taskClass','save',$param['work_id'],$userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));            
        }        
        $param['create_user_id'] = $userInfo['id'];
        $res = $workClassModel->createData($param);
        if ($res) {
            return resultArray(['data'=>'添加成功']);
        } else {
            return resultArray(['error'=>$workClassModel->getError()]);
        }
    }

    /**
     * 重命名任务列表
     * @author yykun
     * @return
     */
    public function rename()
    {
        $param = $this->param;
        $workClassModel = model('WorkClass');
        $workModel = model('Work');
        if (!$param['name'] || !$param['class_id']) return resultArray(['error'=>'参数错误']);
        $classInfo = db('work_task_class')->where(['class_id' => $param['class_id']])->find();
        $userInfo = $this->userInfo;
		$param['create_user_id'] = $userInfo['id']; 
        //权限判断
        if (!$workModel->isCheck('work','taskClass','update',$classInfo['work_id'],$userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));            
        }        
        $res = $workClassModel->rename($param);
        if ($res) {
            return resultArray(['data'=>'编辑成功']);
        } else {
            return resultArray(['error'=>$workClassModel->getError()]);
        }
    }

    /**
     * 删除任务列表(该分类下任务标记删除)
     * @author yykun
     * @return
     */
    public function delete()
    {
        $param = $this->param;
        $workClassModel = model('WorkClass');
        $workModel = model('Work');
        if (!$param['class_id']) return resultArray(['error'=>'参数错误']);
        $classInfo = db('work_task_class')->where(['class_id' => $param['class_id']])->find();
        $userInfo = $this->userInfo;
		$param['create_user_id'] = $userInfo['id']; 
        //权限判断
        if (!$workModel->isCheck('work','taskClass','delete',$classInfo['work_id'],$userInfo['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));            
        }          
        $res = $workClassModel->deleteById($param);
        if ($res) {
            return resultArray(['data'=>'删除成功']);
        } else {
            return resultArray(['error'=>$workClassModel->getError()]);
        }
    }      
}