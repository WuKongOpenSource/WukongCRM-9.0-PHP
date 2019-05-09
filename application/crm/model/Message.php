<?php
// +----------------------------------------------------------------------
// | Description: 站内信
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\crm\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class Message extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
    
    /**
     * [getDataList 消息list,系统通知]
     * @author Michael_xu
     * @param     [string]                   $map [查询条件]
     * @param     [number]                   $page     [当前页数]
     * @param     [number]                   $limit    [每页数量]
     * @return    [array]                    [description]
     */     
    public function getDataList($request)
    {
        $userModel = new \app\admin\model\User();
        $search = $request['search'];
        $user_id = $request['user_id'];
        unset($request['search']);
        unset($request['user_id']);         

        $request = $this->fmtRequest( $request );
        $map = $request['map'] ? : [];
        if ($search) {
            //普通筛选
            $map['content'] = ['like', '%'.$search.'%'];
        } else {
            //高级筛选
            $map = where_arr($map, 'crm', 'message', 'index');
        }
        //权限
        $map['from_user_id'] = 0;
        $map['to_user_id'] = $user_id;
        $order = 'update_time desc'; //排序

        $list = $this
                ->where($map)
                ->page($request['page'], $request['limit'])
                ->order($order)
                ->select(); 
        foreach ($list as $k=>$v) {
            $list[$k]['']
        }
        $dataCount = $this->where($map)->count('message_id');
        $data = [];
        $data['list'] = $list;
        $data['dataCount'] = $dataCount ? : 0;
        return $data;
    }	
} 		