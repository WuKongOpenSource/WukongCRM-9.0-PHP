<?php
// +----------------------------------------------------------------------
// | Description: 评论
// +----------------------------------------------------------------------
// | Author:  yykun
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Request;
use think\Session;
use think\Hook;
use app\common\controller\Common;

class Comment extends Common
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
            'permission'=>[''],  //不登录可访问
            'allow'=>['save','delete']  //需要登录才能访问
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());
		$c = strtolower($request->controller());
		$m = strtolower($request->module());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    //添加评论
    public function save()
    {
        $param = $this->param;
        $model = model('Comment');
        if ($param['task_id']) {
            $userInfo = $this->userInfo;
			$param['create_user_id'] = $userInfo['id'];
            $flag = $model->createData($param);
            if ($flag) {
                return resultArray(['data'=>$flag]);
            } else {
                return resultArray(['error'=>$model->getError()]);
            }
        } else {
            return resultArray(['error'=>'参数错误']);
        }

    }

    //删除评论
    public function delete()
    {
        $param = $this->param;
        $commentModel = model('Comment');
        if ($param['comment_id']) {
            $userInfo   			 = $this->userInfo;
			$param['create_user_id'] = $userInfo['id'];
            $flag = $commentModel->delDataById($param);
            if ($flag) {
                return resultArray(['data'=>'删除成功']);
            } else {
                return resultArray(['error'=>$commentModel->getError()]);
            }
        } else {
            return resultArray(['error'=>'参数错误']);
        }
    }
}
