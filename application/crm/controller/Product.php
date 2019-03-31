<?php
// +----------------------------------------------------------------------
// | Description: 产品
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\crm\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;

class Product extends ApiCommon
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
            'allow'=>['']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    } 

    /**
     * 产品列表
     * @author Michael_xu
     * @return
     */
    public function index()
    {
        $productModel = model('Product');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];        
        $data = $productModel->getDataList($param);       
        return resultArray(['data' => $data]);
    }

    /**
     * 添加产品
     * @author Michael_xu
     * @param  
     * @return
     */
    public function save()
    {
        $productModel = model('Product');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $param['owner_user_id'] = $userInfo['id'];

        if ($productModel->createData($param)) {
            return resultArray(['data' => '添加成功']);
        } else {
            return resultArray(['error' => $productModel->getError()]);
        }
    }

    /**
     * 产品详情
     * @author Michael_xu
     * @param  
     * @return
     */
    public function read()
    {
        $productModel = model('Product');
        $param = $this->param;
        $data = $productModel->getDataById($param['id']);
        if (!$data) {
            return resultArray(['error' => $productModel->getError()]);
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 编辑产品
     * @author Michael_xu
     * @param 
     * @return
     */
    public function update()
    {    
        $productModel = model('Product');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];

        if ($productModel->updateDataById($param, $param['id'])) {
            return resultArray(['data' => '编辑成功']);
        } else {
            return resultArray(['error' => $productModel->getError()]);
        }      
    } 

    /**
     * 产品上架、下架
     * @author Michael_xu
     * @param 
     * @return
     */     
    public function status()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $data = [];
        $data['status'] = ($param['status'] == '上架') ? '上架' : '下架'; 
        $data['update_time'] = time();
        if (!is_array($param['id'])) {
            $productIds[] = $param['id'];
        } else {
            $productIds = $param['id'] ? : [];
        }
        if (!$productIds) {
            return resultArray(['error' => '参数错误']);
        }
        $res = db('crm_product')->where(['product_id' => ['in',$productIds]])->update($data);
        if (!$res) {
            return resultArray(['error' => '操作失败']);
        }
        return resultArray(['data' => $data['status'].'成功']);
    }
}
