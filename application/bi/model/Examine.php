<?php
// +----------------------------------------------------------------------
// | Description: 审批统计
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\bi\model;

use think\Db;
use app\admin\model\Common;
use think\Request;

class Examine extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'oa_examine';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;
	private $statusArr = ['0'=>'待审核','1'=>'审核中','2'=>'审核通过','3'=>'已拒绝','4'=>'已撤回'];

	/**
     * [getSortByExamine 排序]
     * @author zhi
     * @param 
     * @return 
     */		
	public function getSortByExamine($whereArr)
	{
		$count = db('oa_examine')->group('create_user_id')->field('create_user_id,count(examine_id) as count')->order('count desc')->where($whereArr)->select();
        return $count;
	}

    /**
     * [getStatistics 审批统计]
     * @author Michael_xu
     * @param
     * @return 
     */
    public function getStatistics($param)
    {
        $userModel = new \app\admin\model\User();
        $adminModel = new \app\admin\model\Admin(); 
        // $perUserIds = $userModel->getUserByPer('bi', 'oa', 'read'); //权限范围内userIds
        // $whereData = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        // $userIds = $whereData['userIds'];        
        $where = [];       

        //时间
        $start_time = $param['start_time'] ? : strtotime(date('Y-m-d',time()));
        $end_time = $param['end_time'] ? : strtotime(date('Y-m-d',time()))+86399;
        $create_time = array('between',array($start_time,$end_time));

        // $where['id'] = array('in',$userIds);
        $where['type'] = 1;
        $userList = db('admin_user')->where($where)->field('id,username,thumb_img,realname')->select();

        $category_list = db('oa_examine_category')->where(['status' => 1,'is_deleted' => ['neq',1]])->field('category_id,title')->select();
        foreach ($category_list as $key=>$val) {
            $category_list[$key]['title'] = strstr($val['title'],'审批') ? str_replace('审批','次数',$val['title']) : $val['title'].'次数';
        }
        foreach ($userList as $k=>$v) {
            $userList[$k]['thumb_img'] = $v['thumb_img'] ? getFullPath($v['thumb_img']) : '';
            $examineList = [];
            $whereArr = [];
            $whereArr['create_user_id'] = $v['id'];
            $whereArr['create_time'] = $create_time;
            $examineList = $this->where($whereArr)->field('examine_id,category_id,check_status')->order('category_id asc')->select();
            foreach ($examineList as $key=>$val) {
                if (isset($userList[$k]['count_'.$val['category_id']])) {
                    $userList[$k]['count_'.$val['category_id']] += 1;
                } else {
                    $userList[$k]['count_'.$val['category_id']] = 1;
                }
            }
        }
        $data = [];
        $data['category_list'] = $category_list;
        $data['userList'] = $userList;
        return $data ? : [];
    }   
}