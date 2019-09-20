<?php
// +----------------------------------------------------------------------
// | Description: 工作台及基础
// +----------------------------------------------------------------------
// | Author:  yykun
// +----------------------------------------------------------------------

namespace app\work\controller;

use think\Request;
use think\Session;
use think\Hook;
use think\Db;
use app\admin\controller\ApiCommon;
use app\admin\model\Comment as Comment;
use app\work\model\WorkModel as WorkModel;

class Index extends ApiCommon
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
            'allow'=>['worklist','fields','index','fieldrecord']  //需要登录才能访问          
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 左侧导航栏项目展示
     * @author Michael_xu
     * @return 
     */
    public function workList()
    {
        $userInfo = $this->userInfo;
        $WorkModel = model('Work');
        $param['user_id'] = $userInfo['id']; 
        //权限
        $map = $WorkModel->getWorkWhere($param);
        $list = Db::name('Work')->where(['status' => 1])->where($map)->select();
        return resultArray(['data' => $list]);
    }
    
    /**
     * 看板试图
     * @author Michael_xu
     * @return 
     */
    public function index()
    {
        $commentModel = new Comment();
        $param['task_id'] =2;
        $list = $commentModel->read($param);
        if ($list) {
             return resultArray(['data' => $list ]);
        } else {
             return resultArray(['error'=> $commentModel->getError()]);
        }
    }
}
 