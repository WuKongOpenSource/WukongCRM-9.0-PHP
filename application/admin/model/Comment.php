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
    //protected $createTime = 'create_time';
    protected $insert = [
        'status' => 1,
    ];

    //根据ID查看评论
    public function read($param)
    {
        $map['comment.type'] = $param['type'] ?: ''; //默认评论类型
        $map['comment.type_id'] = $param['type_id'];
        $map['comment.isreply'] = 0;
        $list = Db::name('AdminComment')->alias('comment')
            ->field('comment.*,u.username,u.realname,u.thumb_img')
            ->join(' admin_user u', 'u.id=comment.user_id')
            ->where($map)
            ->select();
        foreach ($list as $key => $value) {
            $userInfo_f = Db::name('AdminUser')->field('id,realname,thumb_img')->where('id =' . $value['user_id'])->find();
            $userInfo_f['thumb_img'] = $userInfo_f['thumb_img'] ? getFullPath($userInfo_f['thumb_img']) : '';
            $list[$key]['userInfo'] = $userInfo_f;
            $replyuserInfo_f = Db::name('AdminUser')->field('id,realname,thumb_img')->where('id =' . $value['reply_user_id'])->find();
            $replyuserInfo_f['thumb_img'] = $replyuserInfo_f['thumb_img'] ? getFullPath($replyuserInfo_f['thumb_img']) : '';
            $list[$key]['replyuserInfo'] = $replyuserInfo_f;
            $replyList = Db::name('AdminComment')->where('reply_fid = ' . $value['comment_id'])->select();
            $list[$key]['create_time'] = $value['create_time'];
            foreach ($replyList as $k => $v) {
                $userInfo = Db::name('AdminUser')->field('id,realname,thumb_img')->where('id =' . $v['user_id'])->find();
                $userInfo['thumb_img'] = $userInfo['thumb_img'] ? getFullPath($userInfo['thumb_img']) : '';
                $replyList[$k]['userInfo'] = $userInfo;
                $replyuserInfo = Db::name('AdminUser')->field('id,realname,thumb_img')->where('id =' . $v['reply_user_id'])->find();
                $replyuserInfo['thumb_img'] = $replyuserInfo['thumb_img'] ? getFullPath($replyuserInfo['thumb_img']) : '';
                $replyList[$k]['replyuserInfo'] = $replyuserInfo;
            }
            $list[$key]['replyList'] = $replyList ?: array(); // $this->commentList($value['comment_id'],$result = array());
        }
        return $list;
    }

    //获取回复
    function commentList($parent_id = 0, &$result = array())
    {

        $arr = $this->where("status =1 and reply_id = '" . $parent_id . "'")->order("create_time desc")->select();
        if (empty($arr)) {
            return array();
        }
        foreach ($arr as $cm) {
            $thisArr =& $result[];
            $cm["children"] = $this->commentList($cm["comment_id"], $thisArr);
            $thisArr = $cm;
        }
        return $result;
    }

    //新建评论
    public function createData($param)
    {
        $data['user_id'] = $param['user_id'];
        $data['content'] = $param['content'];  //内容拼接保存
        $data['reply_content'] = $param['reply_content'] ?: '';  //内容拼接保存
        $data['create_time'] = time(); //
        $data['isreply'] = $param['reply_comment_id'] ? 1 : 0; //是否是回复评论
        $data['reply_id'] = $param['reply_comment_id'] ? $param['reply_comment_id'] : 0;  //回复消息id
        $data['reply_fid'] = $param['reply_fid'] ?: ''; //回复最上级ID
        $data['reply_user_id'] = $param['reply_user_id'] ?: ''; //回复别人ID
        $data['status'] = 1;
        $data['type_id'] = $param['type_id'];  //任务id
        $data['type'] = $param['type'];//任务评论
        $flag = db('admin_comment')->insertGetId($data);
        if ($flag) {
            return $flag;
        } else {
            $this->error = '回复添加失败';
            return false;
        }
    }

    /**
     * 删除评论
     * @param array $param
     * @param bool $delSon
     * @return bool
     */
    public function delDataById($param = [], $delSon = false)
    {
        if ($param['comment_id']) {
            $flag = $this->where('comment_id =' . $param['comment_id'])->delete();
        } else {
            $flag = $this->where('type =' . $param['type'] . ' and type_id =' . $param['type_id'])->delete();
        }
        if ($flag) {
            return true;
        } else {
            $this->error = '不存在或已删除';
            return false;
        }
    }
}
