<?php
// +----------------------------------------------------------------------
// | Description: 项目回收站
// +----------------------------------------------------------------------
// | Author:  yykun
// +----------------------------------------------------------------------

namespace app\work\controller;

use think\Request;
use think\Session;
use think\Hook;
use app\admin\controller\ApiCommon;
use think\Db;

class Trash extends ApiCommon
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
            'allow'=>['index','delete','recover']          
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 回收站列表 
     * @author yykun
     * @return
     */
    public function index()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $where = [];
        $where['ishidden'] = 1;
        $taskModel = new \app\work\model\Task();
        $adminTypes = adminGroupTypes($userInfo['id']);
        if (!in_array(1,$adminTypes) && !in_array(7,$adminTypes)) {
            $str = ','.$userInfo['id'].',';
            $where['whereStr'] = ' ( task.create_user_id ='.$userInfo['id'].' or ( task.owner_user_id like "%'.$str.'%") or ( task.main_user_id = '.$userInfo['id'].' ) )';
        }       
        $data = $taskModel->getTaskList($where);
        return resultArray(['data' =>$data]);        
    }

    /**
     * 回收站删除
     * @author yykun
     * @return
     */
    public function delete()
    {
        $param = $this->param;
        if (!$param['task_id']) return resultArray(['error'=>'参数错误']);
        $flag = Db::name('Task')->where(['ishidden' => 1,'task_id' => $param['task_id']])->delete();
        if ($flag) {
            //删除附件
            
            return resultArray(['data'=>'删除成功']);
        } else {
            return resultArray(['error'=>'删除失败']);
        }
    }

    /**
     * 恢复任务
     * @author yykun
     * @return
     */
    public function recover()
    {
        $param = $this->param;
        if (!$param['task_id']) return resultArray(['error'=>'参数错误']);
        $updateData = [];
        $updateData['ishidden'] = 0;
        $updateData['class_id'] = 0;
        $ret = Db::name('Task')->where(['ishidden' => 1,'task_id' => $param['task_id']])->update($updateData);
        if ($ret) {
            return resultArray(['data'=>'操作成功']);
        } else {
            return resultArray(['error'=>$model->getError()]);
        }
    }    
}