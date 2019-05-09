<?php
// +----------------------------------------------------------------------
// | Description: 场景
// +----------------------------------------------------------------------
// | Author:   Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Hook;
use think\Session;
use think\Request;
use think\Db;

class Scene extends ApiCommon
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
            'allow'=>['index','save','read','update','delete','sort','defaults']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 场景列表
     * @return
     */
    public function index()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $sceneModel = model('Scene');
        $data = $sceneModel->getDataList($param['types'], $userInfo['id']);
        if (!$data) {
            return resultArray(['error' => $sceneModel->getError()]);
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 场景创建
     * @param
     * @return
     */
    public function save()
    {
        $sceneModel = model('Scene');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $data = $sceneModel->createData($param, $param['types']);
        if (!$data) {
            return resultArray(['error' => $sceneModel->getError()]);
        }
        return resultArray(['data' => '添加成功']);
    }

    /**
     * 场景详情
     * @param  int  $id
     * @return 
     */
    public function read()
    {
        $sceneModel = model('Scene');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $data = $sceneModel->getDataById($param['id'], $userInfo['id']);
        if (!$data) {
            return resultArray(['error' => $sceneModel->getError()]);
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 编辑场景
     * @param  int  $id
     * @return 
     */
    public function update()
    {
        $sceneModel = model('Scene');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $data = $sceneModel->updateDataById($param, $param['id']);
        if (!$data) {
            return resultArray(['error' => $sceneModel->getError()]);
        } 
        return resultArray(['data' => '编辑成功']);        
    }

    /**
     * 删除场景
     * @param  int  $id
     * @return 
     */
    public function delete($id)
    {
        $sceneModel = model('Scene');
        $param = $this->param;
        $userInfo = $this->userInfo;
        //权限判断
        if (!$sceneModel->getDataById($param['id'], $userInfo['id'])) {
            return resultArray(['error' => '数据不存在或已删除']);
        }
        $dataInfo = db('admin_scene')->where(['scene_id' => $param['id']])->find();
        $resData = $sceneModel->delDataById($param['id']);
        if ($resData) {
            //重新设置默认
            $default = db('admin_scene')->where(['types' => $dataInfo['types'],'bydata' => 'all'])->find();
            $sceneModel->defaultDataById(['types' => $dataInfo['types'],'user_id' => $userInfo['id']], $default['scene_id']);
            return resultArray(['data' => '删除成功']);
        } else {
            return resultArray(['error' => $sceneModel->getError()]);
        }
    } 

    /**
     * 场景排序
     * @param 
     * @return 
     */
    public function sort()
    {
        $sceneModel = model('Scene');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['ids'] = $param['ids'] ? : [];
        $param['hide_ids'] = $param['hide_ids'] ? : [];
        $resData = $sceneModel->listOrder($param, $userInfo['id']);
        if (!$resData) {
            return resultArray(['error' => $sceneModel->getError()]);
        }
        return resultArray(['data' => '设置成功']);
    } 

    /**
     * 场景默认
     * @param scene_id 场景ID
     * @return 
     */
    public function defaults() 
    {
        $sceneModel = model('Scene');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $scene_id = $param['id'];
        $param['user_id'] = $userInfo['id'];
        $resData = $sceneModel->defaultDataById($param, $scene_id);
        if (!$resData) {
            return resultArray(['error' => $sceneModel->getError()]);
        }
        return resultArray(['data' => '设置成功']);        
    }     
}
