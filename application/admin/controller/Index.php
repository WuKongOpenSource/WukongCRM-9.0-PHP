<?php
// +----------------------------------------------------------------------
// | Description: 工作台及基础
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Hook;
use think\Request;

class Index extends ApiCommon
{
    /**
     * 用于判断权限
     * @permission 无限制
     * @allow 登录用户可访问
     * @other 其他根据系统设置
     */
    public function _initialize()
    {
        parent::_initialize();
        $action = [
            'permission' => [],
            'allow' => ['fields', 'fieldrecord', 'authlist'],
        ];
        Hook::listen('check_auth', $action);
        $request = Request::instance();
        $a = strtolower($request->action());
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }

    /**
     * 获取字段属性，用于筛选或其他操作
     * @param
     * @return
     */
    public function fields()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $fieldModel = model('Field');
        $field_arr = $fieldModel->getField($param);
        return resultArray(['data' => $field_arr]);
    }

    /**
     * 获取字段修改记录
     * @param
     * @return
     */
    public function fieldRecord()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $param['user_id'] = $userInfo['id'];
        $actionRecordModel = model('ActionRecord');
        $data = $actionRecordModel->getDataList($param);
        if (!$data) {
            return resultArray(['data' => '暂无数据']);
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 权限数据返回
     * @param
     * @return
     */
    public function authList()
    {
        $userInfo = $this->userInfo;
        $userModel = model('User');
        $dataList = $userModel->getMenuAndRule($userInfo['id']);
        return resultArray(['data' => $dataList['authList']]);
    }

}
