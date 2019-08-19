<?php
// +----------------------------------------------------------------------
// | Description: 任务评论及基础
// +----------------------------------------------------------------------
// | Author:  yykun
// +----------------------------------------------------------------------

namespace app\work\controller;

use think\Request;
use think\Hook;
use app\admin\controller\ApiCommon;

class Taskcomment extends ApiCommon
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
            'allow'=>['index','save','delete']         
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 添加评论
     * @author yykun
     * @return
     */
    public function save()
    {
        $param = $this->param;
        $commentModel = new \app\admin\model\Comment();
        if (!$param['task_id']) {
            return resultArray(['error'=>'参数错误']);
        }
        $userInfo = $this->userInfo;
		$param['user_id'] = $userInfo['id'];
        $param['type'] = 'task';
        $param['type_id'] = $param['task_id'];
        $flag = $commentModel->createData($param);
        if ($flag) {
            return resultArray(['data'=>$flag]);
        } else {
            return resultArray(['error'=>$commentModel->getError()]);
        }
    }

    /**
     * 删除评论
     * @author yykun
     * @return
     */ 
    public function delete()
    {
        $param = $this->param;
        $commentModel = new \app\admin\model\Comment();
        if (!$param['comment_id']) {
            return resultArray(['error'=>'参数错误']);
        }
        $userInfo = $this->userInfo;
		$param['create_user_id'] = $userInfo['id']; 
        $flag = $commentModel->delDataById($param);
        if ( $flag ) {
            return resultArray(['data'=>'删除成功']);
        } else {
            return resultArray(['error'=>$commentModel->getError()]);
        }
    }
}
 