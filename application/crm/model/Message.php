<?php
// +----------------------------------------------------------------------
// | Description: 站内信
// +----------------------------------------------------------------------
// | Author:  ymob
// +----------------------------------------------------------------------
namespace app\crm\model;

use app\admin\model\Common;

class Message extends Common
{
    protected $name = 'admin_message';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'send_time';
    protected $updateTime = false;

    /**
     * 错误信息
     */
    public $error = '';

    /**
     * 将要发送的消息
     */
    protected $message = '';

    /**
     * 消息类型
     *
     * @var array
     * @author Ymob
     * @datetime 2019-10-17 15:27:43
     */
    protected $type = [
        1 => [
            'template' => '{from_user} 将 {title} 任务分配给您，请及时查看。'
        ],
        2 => [
            'template' => '{from_user} 邀请您参与 {title} 任务，请及时查看。'
        ],
        3 => [
            'template' => '{from_user} 将<span> {title} </span>任务标记结束。'
        ],
        4 => [
            'template' => '{from_user} 回复了您的日志 {title} ，请及时查看。'
        ],
        5 => [
            'template' => '{from_user} 将日志 {title} 发送给您，请及时查看。'
        ],
        6 => [
            'template' => '{from_user} 提交 {title} 审批待您处理，请及时查看。'
        ],
        7 => [
            'template' => '{from_user} 拒绝您的 {title} 审批，请及时查看。'
        ],
        8 => [
            'template' => '您的 {title} 审批已经审核通过，请及时查看。'
        ],
        9 => [
            'template' => '您有一个新公告 {title} ，请及时查看。'
        ],
        10 => [
            'template' => '{from_user} 邀请您参与 {title} 日程，请及时查看。'
        ],
        11 => [
            'template' => '{from_user} 提交 {title} 合同审批待您处理，请及时查看。'
        ],
        12 => [
            'template' => '{from_user} 拒绝您的 {title} 合同审批，请及时处理。'
        ],
        13 => [
            'template' => '您的 {title} 合同已经审批通过，请及时查看。'
        ],
        14 => [
            'template' => '{from_user} 提交 {title} 回款审批待您处理，请及时查看。'
        ],
        15 => [
            'template' => '{from_user} 拒绝您的 {title} 回款审批，请及时处理。'
        ],
        16 => [
            'template' => '您的 {title} 回款已经审核通过，请及时查看。'
        ],
        17 => [
            'template' => '{time}，{from_user} 导入客户数据 {import_total} 条，失败 {import_error} 条。{title}'
        ],
        18 => [
            'template' => '{time}，{from_user} 导入联系人数据 {import_total} 条，失败 {import_error} 条。{title}'
        ],
        19 => [
            'template' => '{time}，{from_user} 导入线索数据 {import_total} 条，失败 {import_error} 条。{title}'
        ],
        20 => [
            'template' => '{time}，{from_user} 导入产品数据 {import_total} 条，失败 {import_error} 条。{title}'
        ],
        21 => [
            'template' => '{from_user} 将您添加为客户 {title} 的成员。'
        ],
        22 => [
            'template' => '{from_user} 将您添加为商机 {title} 的成员。'
        ],
        23 => [
            'template' => '{from_user} 将您添加为合同 {title} 的成员。'
        ],
    ];

    /**
     * 发送系统通知
     *
     * @param array|int $user_id    接收消息员工ID
     * @param int       $type       消息类型
     * @param int       $action_id  关联模块ID
     * @param boolean   $system     是否系统消息
     * @return bool
     * @author Ymob
     * @datetime 2019-10-17 17:23:05
     */
    public function sendMessage($user_id, $type, $action_id, $system = false)
    {
        $user_id_list = (array) $user_id;
        $user_id_list = array_unique(array_filter($user_id_list));
        if (!$user_id || empty($user_id_list)) {
            $this->error = '接收人不能为空';
            return false;
        }

        if (!isset($this->type[$type])) {
            $this->error = '消息类型错误';
            return false;
        }
    }

    /**
     * 处理消息
     *
     * @param [type] $type
     * @param [type] $data
     * @return void
     * @author Ymob
     * @datetime 2019-10-17 18:07:01
     */
    public function setMessage($type, $data)
    {
        if (!isset($this->type[$type])) {
            $this->error = '消息类型错误';
            return false;
        }
        $message = $this->type[$type]['temp'];
        $message = str_replace('{from_user}', );
        // $this->message = 
    }

}
