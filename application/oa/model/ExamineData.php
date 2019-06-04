<?php
// +----------------------------------------------------------------------
// | Description: 公告
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\oa\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;
use think\helper\Time;

class ExamineData extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'oa_examine_data';

	/**
	 * 存储自定义字段数据
	 * @author Michael_xu
	 * @param
	 * @return
	 */
	public function createData($param)
	{
        $examine_id = $param['examine_id'];
		if (!$examine_id) {
			$this->error = '参数错误';
			return false;
		}
		$fieldList = db('admin_field')->where(['types' => 'oa_examine','types_id' => $param['category_id']])->select();
		//过滤掉固定字段
		$unField = ['content','remark','start_time','end_time','duration','cause','money','category_id','check_user_id','check_status','flow_id','order_id','create_user_id'];
		$data = [];
		foreach ($fieldList as $k=>$v) {
			$field_arr = [];
			if (is_array($param[$v['field']])) {
				foreach ($param[$v['field']] as $key=>$val) {
					$field_arr[] = str_replace(',', '，', $val);
				}
				$param[$v['field']] = $field_arr ? ','.implode(',',$field_arr).',' : '';
			}
			if (!in_array($v['field'], $unField)) {
				$data[$k]['examine_id'] = $examine_id;
				$data[$k]['field'] = $v['field'];
				$data[$k]['value'] = $param[$v['field']];
			}
		}
		if ($data) {
			$resData = db('oa_examine_data')->insertAll($data);
			if (!$resData) {
				$this->error = '添加失败';
				return false;
			}
		}
		return true;
	}

	/**
	 * 读取自定义字段数据
	 * @author Michael_xu
	 * @param
	 * @return
	 */
	public function getDataById($examine_id = '', $param = [])
	{
		if (!$examine_id) {
			$this->error = '参数错误';
			return false;
		}
		$dataList = db('oa_examine_data')->where(['examine_id' => $examine_id])->select();
		$newData = [];
		foreach ($dataList as $k=>$v) {
			$newData[$v['field']] = $v['value'];
		}
		return $newData ? : [];
	}
}
