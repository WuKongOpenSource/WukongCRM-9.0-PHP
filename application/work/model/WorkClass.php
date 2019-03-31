<?php
// +----------------------------------------------------------------------
// | Description: 项目下任务分类模型
// +----------------------------------------------------------------------
// | Author:  yykun
// +----------------------------------------------------------------------

namespace app\work\model;

use think\Db;
use app\admin\model\Common;
use com\verify\HonrayVerify;
use think\Cache;

class WorkClass extends Common
{

    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如微信模块用weixin作为数据表前缀
     */
	protected $name = 'work_task_class';
    protected $createTime = 'create_time';
    protected $updateTime = false;
	protected $autoWriteTimestamp = true;
	protected $insert = [
		'status' => 1,
	];

	/**
     * [getDataList 列表]
     * @AuthorHTL
     * @param     [string]                   $map [查询条件]
     * @param     [number]                   $page     [当前页数]
     * @param     [number]                   $limit    [每页数量]
     * @return    [array]                    [description]
     */	    
	public function getDataList($work_id='')
	{
		$map['status'] = 1;
		if($work_id){
			$map['work_id'] = $work_id;
		}
		
		$dataCount = Db::name('WorkTaskClass')->where($map)->count();
		$list = Db::name('WorkTaskClass')->where($map)->order('order_id asc')->select();
		$data = [];
		$data['list'] = $list;
		$data['dataCount'] = $dataCount;
		return $data;
	}
	
	/**
	 * 创建
	 * @param  array   $param  [description]
	 */
	public function createData($param)
	{
		$this->startTrans();
		try {
			$data['create_time'] = time();
			$data['create_user_id'] = $param['create_user_id'];
			$data['name'] = $param['name'];
			$data['work_id'] = $param['work_id'];
			$data['status'] = 1; 
			$this->insert($data);
			$this->commit();
			return true;
		} catch(\Exception $e) {
			$this->rollback();
			$this->error = '添加失败';
			return false;
		}
	}

	/**
	 * 重命名
	 * @param  array   $param  [description]
	 */
	public function rename($param)
	{
		$map['class_id'] = $param['class_id'];
		$flag = $this->where($map)->setField('name',$param['name']);

		if($flag){
			return true;
		}else{
			$this->error = '重命名失败';
			return false;
		}
	}

	/*
	*删除分类 该分类下所有任务删除
	*/
	public function deleteById($param)
	{
		$map['class_id'] = $param['class_id'];
		$this->startTrans();
		try{
			Db::name('Task')->where($map)->setField('status',0);
			$flag = 1;
			if ($flag) {
				$ret = $this->where($map)->setField('status',0);
				if ($ret) {
					$this->commit();
					return true;
				} else {
					$this->rollback();
					$this->error = '删除失败';
					return false;
				}
			} else {
				$this->rollback();
				$this->error = '删除失败';
				return false;
			}
		} catch (\Exception $e) {
			$this->rollback();
			$this->error = '删除失败';
			return false;
		}
	}
}