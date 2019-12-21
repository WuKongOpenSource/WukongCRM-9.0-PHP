<?php
// +----------------------------------------------------------------------
// | Description: 审批统计
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\bi\model;

use think\Db;
use app\admin\model\Common;
use app\admin\model\User as UserModel;
use app\bi\model\Examine as OaExamineModel;
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
        $count = OaExamineModel::where($whereArr)
            ->group('create_user_id')
            ->field('create_user_id,count(*) as count')
            ->order('count desc')
            ->select();
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
        $perUserIds = []; //权限范围内userIds
        $whereData = $adminModel->getWhere($param, '', $perUserIds); //统计条件

        $userIds = $whereData['userIds'];        

        //时间
        $time_array = getTimeArray();

        $category_list = db('oa_examine_category')
            ->where(['status' => 1,'is_deleted' => ['neq',1]])
            ->field('category_id,title')
            ->select();

        $fields = ['create_user_id'];
        foreach ($category_list as $key=>$val) {
            // 拼接表头标题
            $category_list[$key]['title'] = strstr($val['title'],'审批') ? str_replace('审批','次数',$val['title']) : $val['title'].'次数';
            $fields['SUM(CASE WHEN category_id = ' . $val['category_id'] . ' THEN 1 ELSE 0 END)'] = 'count_' . $val['category_id'];
        }
        
        $sql = OaExamineModel::field($fields)
            ->where([
                'create_time' => ['BETWEEN', $time_array['between']],
                'create_user_id' => ['IN', $userIds]
            ])
            ->group('create_user_id')
            ->fetchSql()
            ->select();
        $list = queryCache($sql);
        // $list = array_column($list, null, 'create_user_id');
        foreach ($list as $key => $val) {
            $val['realname'] = $userModel->getUserById($val['create_user_id'])['realname'];
            $val['id'] = $val['create_user_id'];
            $list[$key] = $val;
        }

        $data = [
            'category_list' => $category_list,
            'userList' => $list
        ];
        return $data ? : [];
    }   
}