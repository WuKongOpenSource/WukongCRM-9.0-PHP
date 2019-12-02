<?php
// +----------------------------------------------------------------------
// | Description: 站内信
// +----------------------------------------------------------------------
// | Author:  ymob
// +----------------------------------------------------------------------
namespace app\admin\model;

use app\admin\model\ImportRecord as ImportRecordModel;
use app\admin\model\User as UserModel;
use app\crm\model\Contract as ContractModel;
use app\crm\model\Receivables as ReceivablesModel;
use app\oa\model\Announcement as AnnouncementModel;
use app\oa\model\Event as EventModel;
use app\oa\model\Examine as ExamineModel;
use app\oa\model\ExamineCategory;
use app\oa\model\Log as LogModel;
use app\work\model\Task as TaskModel;

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
     * 将要发送的消息内容
     */
    protected $content = '';

    /**
     * 消息类型
     */
    protected $type = 0;

    /**
     * 任务分配
     */
    const TASK_ALLOCATION = 1;
    /**
     * 任务邀请
     */
    const TASK_INVITE = 2;
    /**
     * 任务结束
     */
    const TASK_OVER = 3;
    /**
     * 回复日志
     */
    const LOG_REPLAY = 4;
    /**
     * 发送日志
     */
    const LOG_SEND = 5;
    /**
     * 审批待处理
     */
    const EXAMINE_TO_DO = 6;
    /**
     * 审批驳回
     */
    const EXAMINE_REJECT = 7;
    /**
     * 审批通过
     */
    const EXAMINE_PASS = 8;
    /**
     * 公告
     */
    const NOTICE_MESSAGE = 9;
    /**
     * 日程
     */
    const EVENT_MESSAGE = 10;
    /**
     * 合同待审批
     */
    const CONTRACT_TO_DO = 11;
    /**
     * 合同审批驳回
     */
    const CONTRACT_REJECT = 12;
    /**
     * 合同审批通过
     */
    const CONTRACT_PASS = 13;
    /**
     * 回款待审批
     */
    const RECEIVABLES_TO_DO = 14;
    /**
     * 回款审批驳回
     */
    const RECEIVABLES_REJECT = 15;
    /**
     * 回款审批通过
     */
    const RECEIVABLES_PASS = 16;
    /**
     * 客户导入
     */
    const IMPORT_CUSTOMER = 17;
    /**
     * 联系人
     */
    const IMPORT_CONTACTS = 18;
    /**
     * 线索
     */
    const IMPORT_LEADS = 19;
    /**
     * 产品
     */
    const IMPORT_PRODUCT = 20;
    /**
     * 团队成员-客户
     */
    const TEAM_CUSTOMER = 21;
    /**
     * 团队成员-商机
     */
    const TEAM_BUSINESS = 22;
    /**
     * 团队成员-合同
     */
    const TEAM_CONTRACT = 23;

    /**
     * 消息类型
     *
     * @var array
     * @author Ymob
     * @datetime 2019-10-17 15:27:43
     */
    protected $typeList = [
        1 => [
            'template' => '{from_user} 将 {title} 任务分配给您，请及时查看。',
        ],
        2 => [
            'template' => '{from_user} 邀请您参与 {title} 任务，请及时查看。',
        ],
        3 => [
            'template' => '{from_user} 将 {title} 任务标记结束。',
        ],
        4 => [
            'template' => '{from_user} 回复了您的日志 {title} ，请及时查看。',
        ],
        5 => [
            'template' => '{from_user} 将日志 {title} 发送给您，请及时查看。',
        ],
        6 => [
            'template' => '{from_user} 提交 {title} 审批待您处理，请及时查看。',
        ],
        7 => [
            'template' => '{from_user} 拒绝您的 {title} 审批，请及时查看。',
        ],
        8 => [
            'template' => '您的 {title} 审批已经审核通过，请及时查看。',
        ],
        9 => [
            'template' => '您有一个新公告 {title} ，请及时查看。',
        ],
        10 => [
            'template' => '{from_user} 邀请您参与 {title} 日程，请及时查看。',
        ],
        11 => [
            'template' => '{from_user} 提交 {title} 合同审批待您处理，请及时查看。',
        ],
        12 => [
            'template' => '{from_user} 拒绝您的 {title} 合同审批，请及时处理。',
        ],
        13 => [
            'template' => '您的 {title} 合同已经审批通过，请及时查看。',
        ],
        14 => [
            'template' => '{from_user} 提交 {title} 回款审批待您处理，请及时查看。',
        ],
        15 => [
            'template' => '{from_user} 拒绝您的 {title} 回款审批，请及时处理。',
        ],
        16 => [
            'template' => '您的 {title} 回款已经审核通过，请及时查看。',
        ],
        17 => [
            'template' => '{date}，{from_user} 导入客户数据，共 {total} 条，已导入 {done} 条 ， 成功 {success} 条， 覆盖 {cover} 条, 失败 {error} 条。{title}',
        ],
        18 => [
            'template' => '{date}，{from_user} 导入联系人数据，共 {total} 条，已导入 {done} 条 ， 成功 {success} 条， 覆盖 {cover} 条, 失败 {error} 条。{title}',
        ],
        19 => [
            'template' => '{date}，{from_user} 导入线索数据，共 {total} 条，已导入 {done} 条 ， 成功 {success} 条， 覆盖 {cover} 条, 失败 {error} 条。{title}',
        ],
        20 => [
            'template' => '{date}，{from_user} 导入产品数据，共 {total} 条，已导入 {done} 条 ， 成功 {success} 条， 覆盖 {cover} 条, 失败 {error} 条。{title}',
        ],
        21 => [
            'template' => '{from_user} 将您添加为客户 {title} 的成员。',
        ],
        22 => [
            'template' => '{from_user} 将您添加为商机 {title} 的成员。',
        ],
        23 => [
            'template' => '{from_user} 将您添加为合同 {title} 的成员。',
        ],
    ];

    /**
     * 消息类型分组
     */
    public static $typeGroup = [
        'announcement' => [
            self::NOTICE_MESSAGE,
        ],
        'examine' => [
            self::EXAMINE_TO_DO,
            self::EXAMINE_REJECT,
            self::EXAMINE_PASS,
        ],
        'task' => [
            self::TASK_ALLOCATION,
            self::TASK_INVITE,
            self::TASK_OVER,
        ],
        'log' => [
            self::LOG_REPLAY,
            self::LOG_SEND,
        ],
        'event' => [
            self::EVENT_MESSAGE,
        ],
        'crm' => [
            self::CONTRACT_TO_DO,
            self::CONTRACT_REJECT,
            self::CONTRACT_PASS,
            self::RECEIVABLES_TO_DO,
            self::RECEIVABLES_REJECT,
            self::RECEIVABLES_PASS,
            self::IMPORT_CUSTOMER,
            self::IMPORT_CONTACTS,
            self::IMPORT_LEADS,
            self::IMPORT_PRODUCT,
            self::TEAM_CUSTOMER,
            self::TEAM_BUSINESS,
            self::TEAM_CONTRACT,
        ]
    ];

    /**
     * 发送系统通知
     *
     * @param int       $type       消息类型
     * @param array     $data       关联信息
     * @param array|int $user_id    接收消息员工ID
     * @param boolean   $system     是否系统消息
     * @return bool
     * @author Ymob
     * @datetime 2019-10-17 17:23:05
     */
    public function send($type, $data, $user_id_list, $system = false)
    {
        if (!isset($this->typeList[$type])) {
            $this->error = '消息类型错误';
            return false;
        }

        $user_id_list = (array) $user_id_list;
        $user_id_list = array_unique(array_filter($user_id_list));
        if (empty($user_id_list)) {
            $this->error = '接收人不能为空';
            return false;
        }

        $action_id = $data['action_id'];

        $content = $this->typeList[$type]['template'];

        foreach ($data as $key => $val) {
            $content = str_replace('{' . $key . '}', $val, $content);
        }

        $content = str_replace('{from_user}', User::userInfo('realname'), $content);
        $content = str_replace('{date}', date('Y-m-d'), $content);

        $data = [];
        $data['type'] = $type;
        $data['content'] = $content;
        $data['action_id'] = $action_id;
        $data['read_time'] = 0;
        $data['from_user_id'] = $system ? 0 : User::userInfo('id');

        $request = request();
        $data['controller_name'] = strtolower($request->controller());
        $data['module_name'] = strtolower($request->module());
        $data['action_name'] = strtolower($request->action());


        $from_user_id = $data['from_user_id'];
        $user_id_list = array_filter($user_id_list, function ($val) use ($from_user_id) {
            return $val !== $from_user_id;
        });
        
        $all_data = [];
        foreach ($user_id_list as $user_id) {
            $temp = $data;
            $temp['to_user_id'] = $user_id;
            $all_data[] = $temp;
        }

        if (!empty($from_user_id) && class_exists('DingTalk')) {
            (new Dingtalk())->message($user_id_list, $content);
        }

        $this->saveAll($all_data);
    }

    /**
     * 获取关联模块数据
     *
     * @author Ymob
     * @datetime 2019-10-22 15:34:35
     */
    public function getRelationTitleAttr($val, $data)
    {
        switch ($data['type']) {
            // 任务
            case self::TASK_ALLOCATION:
            case self::TASK_INVITE:
            case self::TASK_OVER:
                return TaskModel::where(['task_id' => $data['action_id']])->value('name') ?: '';

            // 日志
            case self::LOG_REPLAY:
            case self::LOG_SEND:
                return LogModel::where(['log_id' => $data['action_id']])->value('title') ?: '';

            // 审批
            case self::EXAMINE_TO_DO:
            case self::EXAMINE_REJECT:
            case self::EXAMINE_PASS:
                $category_id = ExamineModel::where(['examine_id' => $data['action_id']])->value('category_id') ?: 0;
                $categoryInfo = (new ExamineCategory())->getDataById($category_id);
                return $categoryInfo['title'];

            // 公告
            case self::NOTICE_MESSAGE:
                return AnnouncementModel::where(['announcement_id' => $data['action_id']])->value('title') ?: '';

            // 日程
            case self::EVENT_MESSAGE:
                return EventModel::where(['event_id' => $data['action_id']])->value('title') ?: '';

            // 合同
            case self::CONTRACT_TO_DO:
            case self::CONTRACT_REJECT:
            case self::CONTRACT_PASS:
            case self::TEAM_CONTRACT:
                return ContractModel::where(['contract_id' => $data['action_id']])->value('name') ?: '';

            // 回款
            case self::RECEIVABLES_TO_DO:
            case self::RECEIVABLES_REJECT:
            case self::RECEIVABLES_PASS:
                return ReceivablesModel::where(['receivables_id' => $data['action_id']])->value('number') ?: '';

            // 导入数据
            case self::IMPORT_CUSTOMER:
            case self::IMPORT_CONTACTS:
            case self::IMPORT_LEADS:
            case self::IMPORT_PRODUCT:
                $error = ImportRecordModel::where(['id' => $data['action_id']])->value('error');
                return $error ? '点击下载错误数据' : '';

            // 客户
            case self::TEAM_CUSTOMER:
                return CustomerModel::where(['customer_id' => $data['action_id']])->value('name') ?: '';

            // 商机
            case self::TEAM_BUSINESS:
                return BusinessModel::where(['business_id' => $data['action_id']])->value('name') ?: '';
        }

        return '';
    }

    /**
     * 发送人
     */
    public function getFromUserIdInfoAttr($val, $data)
    {
        return $data['from_user_id'] ? UserModel::getUserById($data['from_user_id']) : [];
    }
}
