<?php
// +----------------------------------------------------------------------
// | Description: 用户组
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Hook;
use think\Request;

class Groups extends ApiCommon
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
            'allow'=>['index','read','save','update','delete','enables','copy']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
        $userInfo = $this->userInfo;
        //权限判断
        $adminTypes = adminGroupTypes($userInfo['id']);
        if (!in_array(1,$adminTypes) && !in_array(2,$adminTypes) && !in_array(3,$adminTypes)) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }                
    }        

    /**
     * 角色列表
     * @author Michael_xu
     * @param 
     * @return                            
     */     
    public function index()
    {   
        $groupModel = model('Group');
        $param = $this->param;
        $data = $groupModel->getDataList($param);
        return resultArray(['data' => $data]);
    }

    /**
     * 角色详情
     * @author Michael_xu
     * @param 
     * @return                            
     */    
    public function read()
    {   
        $groupModel = model('Group');
        $param = $this->param;
        $data = $groupModel->getDataById($param['id']);
        if (!$data) {
            return resultArray(['error' => $groupModel->getError()]);
        } 
        return resultArray(['data' => $data]);
    }

    /**
     * 角色添加
     * @author Michael_xu
     * @param 
     * @return                            
     */    
    public function save()
    {
        $groupModel = model('Group');
        $param = $this->param;
		$param['rules'] = arrayToString($param['rules']);
        $data = $groupModel->createData($param);
        if (!$data) {
            return resultArray(['error' => $groupModel->getError()]);
        } 
        return resultArray(['data' => $data]);
    }

    /**
     * 角色编辑
     * @author Michael_xu
     * @param 
     * @return                            
     */     
    public function update()
    {
        $groupModel = model('Group');
        $param = $this->param;
        $dataInfo = $groupModel->getDataById($param['id']);
        if (!$dataInfo) {
            return resultArray(['error' => '参数错误']);
        }
        if ($dataInfo['types']) {
            return resultArray(['error' => '系统角色，不能编辑']);
        }
        $param['rules'] = arrayToString($param['rules']);
        $data = $groupModel->updateDataById($param, $param['id']);
        return resultArray(['data' => '编辑成功']);
    }

    /**
     * 角色删除
     * @author Michael_xu
     * @param 
     * @return                            
     */     
    public function delete()
    {
        $groupModel = model('Group');
        $param = $this->param;
        $dataInfo = $groupModel->getDataById($param['id']);
        if (!$dataInfo) {
            return resultArray(['error' => '参数错误']);
        }
        if ($dataInfo['types']) {
            return resultArray(['error' => '系统角色，不能删除']);
        }        
        $data = $groupModel->delGroupById($param['id']);      
        if (!$data) {
            return resultArray(['error' => $groupModel->getError()]);
        } 
        return resultArray(['data' => '删除成功']);    
    }

    /**
     * 角色启用、禁用
     * @author Michael_xu
     * @param 
     * @return                            
     */   
    public function enables()
    {
        $groupModel = model('Group');
        $param = $this->param;
        $dataInfo = $groupModel->getDataById($param['id']);
        if (!$dataInfo) {
            return resultArray(['error' => '参数错误']);
        }
        if ($dataInfo['types']) {
            return resultArray(['error' => '系统角色，不能删除']);
        }         
        $data = $groupModel->enableDatas($param['id'], $param['status'], true);  
        if (!$data) {
            return resultArray(['error' => $groupModel->getError()]);
        } 
        return resultArray(['data' => '操作成功']);         
    }

    /**
     * 角色复制
     * @author Michael_xu
     * @param 
     * @return                            
     */   
    public function copy()
    {
        $groupModel = model('Group');
        $param = $this->param;
        $dataInfo = $groupModel->getDataById($param['id']);
        if (!$dataInfo) {
            return resultArray(['error' => '参数错误']);
        }
        $dataInfo = json_decode($dataInfo, true);
        unset($dataInfo['id']);
        $titleCount = db('admin_group')->where(['title' => $dataInfo['title']])->count();
        $dataInfo['title'] = $dataInfo['title'].'('.$titleCount.')';
        $data = $groupModel->createData($dataInfo);
        if (!$data) {
            return resultArray(['error' => $groupModel->getError()]);
        }
        return resultArray(['data' => '操作成功']);         
    }    
}
 