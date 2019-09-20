<?php
// +----------------------------------------------------------------------
// | Description: 评论
// +----------------------------------------------------------------------
// | Author:  yykun
// +----------------------------------------------------------------------

namespace app\admin\model;

use think\Db;
use think\Model;
use app\admin\model\Common;
use com\verify\HonrayVerify;
use think\Cache;

class Comment extends Model
{
    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如微信模块用weixin作为数据表前缀
     */
	protected $name = 'admin_comment';
    protected $createTime = 'create_time';
    protected $autoWriteTimestamp = true;
	protected $insert = [
		'status' => 1,
	];

	/**
     * 根据ID查看评论
     * @author Michael_xu
     * @param
     * @return
     */	
	public function read($param)
	{
		$userModel = new \app\admin\model\User();
		$map['comment.type'] = $param['type'] ? : ''; //默认评论类型
		$map['comment.type_id'] = $param['type_id'];
		$map['comment.isreply'] = 0; 
		$list = Db::name('AdminComment')
				->alias('comment')
				->join('admin_user u','u.id=comment.user_id')
				->field('comment.*,u.username,u.realname,u.thumb_img')
				->where($map)
				->select();
		foreach ($list as $key => $value) {
			$list[$key]['userInfo']['username'] = $value['username'];
			$list[$key]['userInfo']['realname'] = $value['realname'];
			$list[$key]['userInfo']['thumb_img'] = $value['thumb_img'] ? getFullPath($value['thumb_img']) : '';
			$list[$key]['replyuserInfo'] = $userModel->getUserById($value['reply_user_id']);
			$replyList = [];
			$replyList = Db::name('AdminComment')->where(['reply_fid' => $value['comment_id']])->select();
			foreach ($replyList as $k=>$v) {
				$replyList[$k]['userInfo'] = $userModel->getUserById($v['user_id']);
				$replyList[$k]['replyuserInfo'] = $userModel->getUserById($v['reply_user_id']);
			}
			$list[$key]['replyList'] = $replyList ? : array();
		}
		return $list;
	}

	/**
     * 获取回复
     * @author Michael_xu
     * @param
     * @return
     */		
	function commentList($parent_id = 0,&$result = array())
	{   
		$list = $this->where(['status' => 1,'reply_id' => $parent_id])->order("create_time desc")->select();
		if ($list) {
			foreach ($list as $cm) {  
				$thisArr =& $result[];
				$cm["children"] = $this->commentList($cm["comment_id"],$thisArr);    
				$thisArr = $cm;                                    
			}			
		}
		return $result ? : [];
    }
	
	/**
     * 新建评论
     * @author Michael_xu
     * @param
     * @return
     */	 
	public function createData($param)
	{
		if (!$param['content']) {
			$this->error = '评论内容不能为空';
			return false;
		}
		$userModel = new \app\admin\model\User();
		$param['reply_content'] = $param['reply_content'] ? : '';  //内容拼接保存
		$param['isreply'] = $param['reply_comment_id'] ? 1 : 0; //是否是回复评论
		$param['reply_id'] = $param['reply_comment_id'] ? $param['reply_comment_id'] : 0;  //回复消息id
		$param['reply_fid'] = $param['reply_fid'] ? : ''; //回复最上级ID
		$param['reply_user_id'] = $param['reply_user_id'] ? : ''; //回复别人ID
		$param['status'] = 1; 
		if ($this->data($param)->allowField(true)->save()) {
			$userInfo = $userModel->getUserById($param['user_id']);
			//发送站内信
			switch ($param['type']) {
				case 'task' : 
					$taskInfo = db('task')->where(['task_id' => $param['type_id']])->field('name,create_user_id,main_user_id,owner_user_id')->find();
					$user_ids[] = $taskInfo['create_user_id'];
					if ($taskInfo['main_user_id']) {
						$user_ids = array_merge($user_ids,array($taskInfo['main_user_id']));
					}
					if (stringToArray($taskInfo['owner_user_id'])) {
						$user_ids = array_merge($user_ids,stringToArray($taskInfo['owner_user_id']));
					}
					$user_ids = array_filter(array_unique($user_ids));
					$sendContent = $userInfo['realname'].',评论了任务《'.$taskInfo['name'].'》:'.$param['content'];
					break;
			}
			if ($user_ids && $sendContent) {
				$resMessage = sendMessage($user_ids, $sendContent, $param['type_id'], 1);
			}
			return $this->comment_id;
		} else {
			$this->error = '回复失败';
			return false;
		}
	}

	/**
     * 删除评论
     * @author Michael_xu
     * @param
     * @return
     */	
	public function delDataById($param)
	{
		if ($param['comment_id']) {
			$flag = $this->where(['comment_id' => $param['comment_id']])->delete();
		} else {
			$flag = $this->where(['type' => $param['type'],'type_id' => $param['type_id']])->delete();
		}
		if (!$flag){
			$this->error = '不存在或已删除';
			return false;
		}
		return true;
	}

	/**
     * 获取评论数
     * @author Michael_xu
     * @param
     * @return
     */	
	public function getCount($type,$type_id)
	{
		$count = 0;
		if ($type && $type_id) {
			$count = $this->where(['type' => $type,'type_id' => $type_id])->count();
		}
		return $count;
	}	
}