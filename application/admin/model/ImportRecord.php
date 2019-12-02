<?php
// +----------------------------------------------------------------------
// | Description: 导入记录
// +----------------------------------------------------------------------
// | Author:  ymob
// +----------------------------------------------------------------------
namespace app\admin\model;

class ImportRecord extends Common
{
    protected $name = 'admin_import_record';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;

    /**
     * 保存记录
     *
     * @param array     $data
     * @return void
     * @author Ymob
     * @datetime 2019-10-22 11:46:41
     */
    public function createData($data)
    {
        $res = $this->save($data);

        $message_type_list = [
            'crm_customer' => Message::IMPORT_CUSTOMER,
            'crm_contacts' => Message::IMPORT_CONTACTS,
            'crm_leads' => Message::IMPORT_LEADS,
            'crm_product' => Message::IMPORT_PRODUCT,
        ];

        if ($res) {
            (new Message())->send(
                $message_type_list[$data['type']],
                [
                    'total' => $data['total'],
                    'error' => $data['error'],
                    'done' => $data['done'],
                    'success' => $data['done'] - $data['error'],
                    'cover' => $data['cover'],
                    'title' => $data['error'] > 0 ? '点击下载错误数据' : '',
                    'action_id' => $this->id,
                ],
                User::userInfo('id'),
                true
            );
        }
    }

}
