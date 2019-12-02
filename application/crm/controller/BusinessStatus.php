<?php
// +----------------------------------------------------------------------
// | Description: 商机组设置
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;

class BusinessStatus extends ApiCommon
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
            'allow'=>['type','save','update','read','enables','delete']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        } 

        $userInfo = $this->userInfo;
        //权限判断
        $unAction = ['type'];
        if (!in_array($a, $unAction) && !checkPerByAction('admin', 'crm', 'setting')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }                
    } 

    /**
     * 商机组列表
     * @author Michael_xu
     * @return 
     */
    public function type()
    {	
        $businessStatusModel = model('BusinessStatus');
        $param = $this->param;
        $data = $businessStatusModel->getTypeList($param);       
        return resultArray(['data' => $data]);
    }

    /**
     * 添加商机组
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function save()
    {
        $businessStatusModel = model('BusinessStatus');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        
        $res = $businessStatusModel->createData($param);
        if ($res) {
            $key = 'BI_queryCache_StatusList_Data';
            cache($key, null, true);
            return resultArray(['data' => '添加成功']);
        } else {
            return resultArray(['error' => $businessStatusModel->getError()]);
        }
    }

    /**
     * 商机组详情
     * @author Michael_xu
     * @param  
     * @return 
     */
    public function read()
    {
        $businessStatusModel = model('BusinessStatus');
        $param = $this->param;
        $data = $businessStatusModel->getDataById($param['id']);
        if (!$data) {
            return resultArray(['error' => $businessStatusModel->getError()]);
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 编辑商机组
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function update()
    {    
        $businessStatusModel = model('BusinessStatus');
        $param = $this->param;
        $userInfo = $this->userInfo;

        $res = $businessStatusModel->updateDataById($param, $param['type_id']);
        if ($res) {
            $key = 'BI_queryCache_StatusList_Data';
            cache($key, null, true);
            return resultArray(['data' => '编辑成功']);
        } else {
            return resultArray(['error' => $businessStatusModel->getError()]);
        }       
    }

    /**
     * 商机组（停用）
     * @author Michael_xu
     * @param status 1启用, 0停用
     * @return 
     */
    public function enables()
    {
        $businessStatusModel = model('BusinessStatus');
        $param = $this->param;
        if ($param['id'] == 1) {
           return resultArray(['error' => '系统数据，不能操作']); 
        }
        $status = $param['status'] ? : '0';
        if (db('crm_business_type')->where(['type_id' => $param['id']])->setField('status', $status)) {
            return resultArray(['data' => '操作成功']);
        } else {
            return resultArray(['error' => $businessStatusModel->getError()]);
        }       
    }

    /**
     * 删除商机组
     * @author Michael_xu
     * @param status 1启用, 0停用
     * @return 
     */
    public function delete()
    {
        $businessStatusModel = model('BusinessStatus');
        $param = $this->param;
        $data = $businessStatusModel->delDataById($param['id'], true);       
        if (!$data) {
            return resultArray(['error' => $businessStatusModel->getError()]);
        } 
        return resultArray(['data' => '删除成功']);        
    }   
}
