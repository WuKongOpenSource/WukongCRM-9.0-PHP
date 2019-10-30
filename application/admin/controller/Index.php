<?php
// +----------------------------------------------------------------------
// | Description: 工作台及基础
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Request;
use think\Session;
use think\Hook;
use think\Db;

class Index extends ApiCommon

{    /**
     * 用于判断权限
     * @permission 无限制
     * @allow 登录用户可访问
     * @other 其他根据系统设置
    **/    
    public function _initialize()
    {
        parent::_initialize();
        $action = [
            'permission'=>[],
            'allow'=>['fields','fieldrecord','authlist']            
        ];
        Hook::listen('check_auth',$action);
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

    /**
     * 消息通知类型
     */
    public function messageTypeList()
    {
        return \resultArray([
            'data' => [
                ['controller_name' => 'customer', 'name' => '客户'],
                ['controller_name' => 'business', 'name' => '商机'],
                ['controller_name' => 'contract', 'name' => '合同'],
                ['controller_name' => 'receivables', 'name' => '回款'],
                ['controller_name' => 'task', 'name' => '任务'],
                ['controller_name' => 'log', 'name' => '日志'],
                ['controller_name' => 'examine', 'name' => '审批'],
                ['controller_name' => 'announcement', 'name' => '公告'],
                ['controller_name' => 'event', 'name' => '日程'],
                ['controller_name' => 'import', 'name' => '导入错误数据']
            ]
        ]);
    }
    
    /**
     * 系统通知
     */
    public function message()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;

        $where = ['to_user_id' => $userInfo['id']];
        $order = [];

        // 仅查询未读消息
        if ($param['unread']) {
            $where['read_time'] = 0;
            $page = 1;
            $limit = 10;
        } else {
            $order['read_time'] = 'ASC';
            $page = $param['page'] ?: 1;
            $limit = $param['limit'] ?: 15;

            // 消息类型
            if ($param['controller_name']) {
                $where['controller_name'] = $param['controller_name'];
            }

            // 处理时间条件
            getWhereTimeByParam($where, 'send_time');
        }

        $order['send_time'] = 'DESC';
        $data = db('AdminMessage')
            ->where($where)
            ->order($order)
            ->page($page, $limit)
            ->select();

        foreach ($data as &$val) {
            $val['content'] = \str_replace('《', '<span>《', $val['content']);
            $val['content'] = \str_replace('》', '》</span>', $val['content']);
            $val['send_time'] = date('Y-m-d H:i:s', $val['send_time']);
        }
        return resultArray(['data' => $data]);
    }

    /**
     * 阅读系统通知，修改状态为已读
     */
    public function readMessage()
    {
        $userInfo = $this->userInfo;
        $param = $this->param;

        $where['to_user_id'] = $userInfo['id'];
        $where['message_id'] = ['IN', (array) $param['message_id']];

        $res = db('AdminMessage')->where($where)->update(['read_time' => time()]);
        return \resultArray(['data' => $res]);
    }
}
 