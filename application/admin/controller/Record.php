<?php
// +----------------------------------------------------------------------
// | Description: 跟进记录
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Hook;
use think\Request;
use think\Db;

class Record extends ApiCommon
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
    }

    /**
     * 跟进记录列表
     * @return
     */
    public function index()
    {
        $param = $this->param;
        $by = $param['by'] ? : '';
        unset($param['by']);
        $recordModel = model('Record');
        $data = $recordModel->getDataList($param, $by);
        if (!$data) {
            return resultArray(['error' => $recordModel->getError()]);
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 跟进记录创建
     * @param
     * @return
     */
    public function save()
    {
        $recordModel = model('Record');
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['create_user_id'] = $userInfo['id'];
        $resData = $recordModel->createData($param);
        if (!$resData) {
            return resultArray(['error' => $recordModel->getError()]);
        }
        //同时创建日程
        if ($param['is_event']) {
            $eventModel = new \app\oa\model\Event();
            $data['title'] = trim($param['content']);
            $data['content'] = trim($param['content']);
            $data['start_time'] = $param['next_time'] ? : time();
            $data['end_time'] = $param['next_time']+86399;
            $data['create_user_id'] = $userInfo['id'];
            if ($param['types'] == 'crm_customer') $data['customer_ids'] = $param['types_id'];
            $data['business_ids'] = $param['business_ids'];
            $data['contacts_ids'] = $param['contacts_ids'];            
            $resEvent = $eventModel->createData($data);          
        }
        return resultArray(['data' => '添加成功']);
    }

    /**
     * 跟进记录编辑
     * @param 
     * @return
     */
    public function update()
    {
        $recordModel = model('Record');
        $param = $this->param;
        $data = $recordModel->updateDataById($param, $param['id']);
        if (!$data) {
            return resultArray(['error' => $recordModel->getError()]);
        } 
        return resultArray(['data' => '编辑成功']);        
    }

    /**
     * 跟进记录删除
     * @param
     * @return
     */
    public function delete()
    {
        $recordModel = model('Record');
        $param = $this->param;
        $userInfo = $this->userInfo;
        //权限判断
        $dataInfo = $recordModel->getDataById($param['id']);
        if (!$dataInfo) {
            return resultArray(['error' => '数据不存在或已删除']);
        }
        //自己(24小时)或者管理员
        $adminTypes = adminGroupTypes($userInfo['id']);
        if(!in_array(1,$adminTypes)){
            if((time()-$dataInfo['create_time']) > 86400){
                return resultArray(['error' => '超过24小时，不能删除']);
            }
            if ($dataInfo['create_user_id'] !== $userInfo['id']){
                return resultArray(['error' => '无权操作']);
            }
        }
        $resData = $recordModel->delDataById($param['id']);
        if (!$resData) {
            return resultArray(['error' => $recordModel->getError()]);
        }
        return resultArray(['data' => '删除成功']);
    }   
}
