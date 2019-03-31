<?php
// +----------------------------------------------------------------------
// | Description: 岗位
// +----------------------------------------------------------------------
// | Author:  
// +----------------------------------------------------------------------

namespace app\admin\model;

use app\admin\model\Common;

class Post extends Common 
{

    /**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'admin_post';
    protected $createTime = 'create_time';
    protected $updateTime = false;
	protected $autoWriteTimestamp = true;
	protected $insert = [
		'status' => 1,
	];  

	/**
	 * [getDataList 获取列表]
	 * @return    [array]                         
	 */
	public function getDataList($request)
	{
		$request = $this->fmtRequest( $request );
		$map = $request['map'];
		if (isset($map['search'])) {
			$map['name'] = ['like', '%'.$map['search'].'%'];
			unset($map['search']);
		} else {
			$map = where_arr($map, 'member'); //高级筛选
		}	
		$data = $this->where($map)->page($request['page'], $request['limit'])->select();
		return $data;
	}
}