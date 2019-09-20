<?php
// +----------------------------------------------------------------------
// | Description: 产品类别
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;

class ProductCategory extends ApiCommon
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
            'allow'=>['index','save','update','delete']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        } 

        $userInfo = $this->userInfo;
        //权限判断
        $unAction = ['index'];
        if (!in_array($a, $unAction) && !checkPerByAction('admin', 'crm', 'setting')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }                
    } 

    /**
     * 产品分类列表
     * @author Michael_xu
     * @return 
     */
    public function index()
    {
        $categoryModel = model('ProductCategory');
        $param = $this->param;
        $data = $categoryModel->getDataList($param['type']);       
        return resultArray(['data' => $data]);
    }

    /**
     * 添加产品分类
     * @author Michael_xu
     * @param  
     * @return 
     */
    public function save()
    {
        $categoryModel = model('ProductCategory');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];

        $res = $categoryModel->createData($param);
        if ($res) {
            return resultArray(['data' => '添加成功']);
        } else {
            return resultArray(['error' => $categoryModel->getError()]);
        }
    }

    /**
     * 编辑产品分类
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function update()
    {    
        $categoryModel = model('ProductCategory');
        $param = $this->param;
        $userInfo = $this->userInfo;

        $res = $categoryModel->updateDataById($param, $param['id']); 
        if ($res) {
            return resultArray(['data' => '编辑成功']);
        } else {
            return resultArray(['error' => $productDataModel->getError()]);
        }       
    }

    /**
     * 删除产品分类
     * @author Michael_xu
     * @param 
     * @return 
     */
    public function delete()
    {
        $categoryModel = model('ProductCategory');
        $param = $this->param;
        $data = $categoryModel->delDataById($param['id'], true);       
        if (!$data) {
            return resultArray(['error' => $categoryModel->getError()]);
        } 
        return resultArray(['data' => '删除成功']);    
    }   
}
