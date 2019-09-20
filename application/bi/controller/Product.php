<?php
// +----------------------------------------------------------------------
// | Description: 商业智能-产品分析
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\bi\controller;

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
            'allow'=>['statistics','productcategory']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
        if (!checkPerByAction('bi', 'product' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }         
    } 
  
    /**
     * 产品销量统计
     * @author Michael_xu
     * @param 
     * @return
     */
    public function statistics()
    {
        $productModel = new \app\crm\model\Product();
        $param = $this->param;
        $list = $productModel->getStatistics($param);
        return resultArray(['data' => $list]);
    } 
     
    /**
     * 产品分类销量分析
     * @return [type] [description]
     */
    public function productCategory()
    {       
        $productModel = new \app\bi\model\Product();
        $param = $this->param;
        $list = $productModel->getStatistics($param);
        return resultArray(['data' => $list]);
    } 
}
