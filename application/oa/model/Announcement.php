<?php
// +----------------------------------------------------------------------
// | Description: 公告
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\oa\model;

use think\Db;
use app\admin\model\Common;
use app\admin\model\Message;
use think\Request;
use think\Validate;
use think\helper\Time;

class Announcement extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'oa_announcement';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
	protected $autoWriteTimestamp = true;

	//类型转换
	protected $dateFormat = 'Y-m-d H:i:s';
	protected $type = [
        'create_time'  =>  'timestamp',
    ];

	/**
     * [getDataList 公告list]
     * @author Michael_xu
     * @param     [by]                       $by [查询时间段类型]
     * @return
     */		
	public function getDataList($request)
    {
    	$userModel = new \app\admin\model\User();
		$structureModel = new \app\admin\model\Structure();

		$search = $request['search'];
    	$user_id = $request['user_id'];
		unset($request['search']);
		unset($request['user_id']);	

        $request = $this->fmtRequest( $request );
        $requestMap = $request['map'] ? : [];		

		$time = strtotime(date('Y-m-d',time()));
		$map = array();
		if ($requestMap['type'] && $requestMap['type'] == 1) {
			$time = 'end_time >= '.$time.' AND start_time <= '.time().' AND ';
		} elseif ($requestMap['type'] && $requestMap['type'] == 2) {
			$time = 'end_time < '.$time.' AND ';
		} else {
			$time = '';
		}
		$userDet = $userModel->getUserById($user_id);
			
		$list = Db::name('OaAnnouncement')
				->alias('announcement')
				->where($time.' ( owner_user_ids LIKE "%,'.$userDet['id'].',%" OR structure_ids LIKE "%,'.$userDet['structure_id'].',%" OR create_user_id = '.$user_id.' OR (owner_user_ids = "" AND structure_ids = ""))')
				->where($map)
				->join('__ADMIN_USER__ user', 'user.id = announcement.create_user_id', 'LEFT')
        		->page($request['page'], $request['limit'])
        		->field('announcement.*,user.realname,user.thumb_img')
				->order('announcement.create_time desc')
        		->select();
        $adminTypes = adminGroupTypes($user_id);
		foreach ($list as $k=>$v) {
			$list[$k]['thumb_img'] = $v['thumb_img'] ? getFullPath($v['thumb_img']) : '';
			$list[$k]['structureList'] = $structureModel->getDataByStr($v['structure_ids']) ? : array();
			$list[$k]['ownerList'] = $userModel->getDataByStr($v['owner_user_ids']) ? : array();
		}
		
        $dataCount = Db::name('OaAnnouncement')->alias('announcement')
			->where(''.$time.' ( owner_user_ids LIKE "%,'.$userDet['id'].',%" OR structure_ids LIKE "%,'.$userDet['structure_id'].',%" OR create_user_id = '.$user_id.')')
			->count();  
        $data = [];
        $data['list'] = $list;
        $data['dataCount'] = $dataCount ? : 0;
        return $data;
    }

	/**
	 * 创建公告信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function createData($param)
	{	
		//不选择通知人，则默认为全部
		$param['structure_ids'] = arrayToString($param['owner_structure_ids']) ? : '';
		unset($param['owner_structure_ids']);
		$param['owner_user_ids'] = arrayToString($param['owner_user_ids']) ? : '';
		
		if ($this->allowField(true)->save($param)) {
			$data = $param;
			$data['announcement_id'] = $this->announcement_id;
			$userModel = new \app\admin\model\User();
			$structureModel = new \app\admin\model\Structure();
			$data['ownerList'] = $param['owner_user_ids'] ? $userModel->getDataByStr($param['owner_user_ids']) : array();
			$data['structureList'] = $param['structure_ids'] ? $structureModel->getDataByStr($param['structure_ids']) : array();
			//操作记录
			actionLog($this->announcement_id,$param['owner_user_ids'],$param['structure_ids'],'创建了公告');
			//发送站内信
			$send_user_id = [];
			$send_structure_ids = $param['structure_ids'] ? $userModel->getSubUserByStr(stringToArray($param['structure_ids'])) : [];
			$send_user_ids = stringToArray($param['owner_user_ids']) ? : [];
			if ($send_structure_ids && $send_user_ids) {
				$send_user_id = array_merge($send_structure_ids, $send_user_ids);
			} elseif ($send_structure_ids) {
				$send_user_id = $send_structure_ids;
			} elseif ($send_user_ids) {
				$send_user_id = $send_user_ids;
			} else {
				$send_user_id = getSubUserId(true, 1); 
			}
            if ($send_user_id) {
				// 发送消息
				(new Message())->send(
					Message::NOTICE_MESSAGE,
					[
						'title' => $param['title'],
						'action_id' => $this->announcement_id
					],
					$send_user_id
				);
            }		
			return $data;
		} else {
			$this->error = '添加失败';
			return false;
		}			
	}

	/**
	 * 编辑公告信息
	 * @author Michael_xu
	 * @param  
	 * @return                            
	 */	
	public function updateDataById($param, $announcement_id = '')
	{
		$dataInfo = $this->getDataById($announcement_id);
		if (!$dataInfo) {
			$this->error = '数据不存在或已删除';
			return false;
		}
		//不选择通知人，则默认为全部
		$param['structure_ids'] = arrayToString($param['owner_structure_ids']) ? : '';
		unset($param['owner_structure_ids']);
		$param['owner_user_ids'] = arrayToString($param['owner_user_ids']) ? : '';		
		$param['update_time'] = time();
		if ($this->allowField(true)->save($param, ['announcement_id' => $announcement_id])) {
			$userModel = new \app\admin\model\User();
			$structureModel = new \app\admin\model\Structure();			
			actionLog($announcement_id, $param['owner_user_ids'], $param['structure_ids'], '编辑了公告');
			//发送站内信
			$send_user_id = [];
			$send_structure_ids = $param['structure_ids'] ? $userModel->getSubUserByStr(stringToArray($param['structure_ids'])) : [];
			$send_user_ids = stringToArray($param['owner_user_ids']) ? : [];
			if ($send_structure_ids && $send_user_ids) {
				$send_user_id = array_merge($send_structure_ids, $send_user_ids);
			} elseif ($send_structure_ids) {
				$send_user_id = $send_structure_ids;
			} elseif ($send_user_ids) {
				$send_user_id = $send_user_ids;
			} else {
				$send_user_id = getSubUserId(true, 1); 
			}
            $createUserInfo = $userModel->getUserById($param['create_user_id']);
            $sendContent = $createUserInfo['realname'].'修改了公告《'.$param['title'].'》,请及时查看';
            if ($send_user_id) {
            	sendMessage($send_user_id, $sendContent, $announcement_id, 1);
            }			
			$data = [];
			$data['announcement_id'] = $announcement_id;
			return $data;
		} else {
			$this->error = '编辑失败';
			return false;
		}					
	}

	/**
     * 公告数据
     * @param  $id 公告ID
     * @return 
     */	
   	public function getDataById($announcement_id = '')
   	{   		
   		$map['announcement_id'] = $announcement_id;
		$dataInfo = Db::name('OaAnnouncement')->where($map)->find();

		if (!$dataInfo) {
			$this->error = '暂无此数据';
			return false;
		}
		$userModel = new \app\admin\model\User();
		$dataInfo['create_user_info'] = $userModel->getUserById($dataInfo['create_user_id']);
		$structureModel = new \app\admin\model\Structure();
		$dataInfo['structureList'] = $structureModel->getDataByStr($dataInfo['structure_ids'])?:array();
		$dataInfo['announcement_id'] = $announcement_id;
		return $dataInfo;
   	}
	
	//删除公告
	public function delDataById($param)
	{
		$dataInfo = $this->getDataById($param['announcement_id']);
		if (!$dataInfo) {
			$this->error = '数据不存在或已删除';
			return false;
		}
		$map['announcement_id'] = $param['announcement_id'];
		$flag = $this->where($map)->delete();
		if ($flag) {
			actionLog($param['announcement_id'],$dataInfo['owner_user_ids'],$dataInfo['structure_ids'],'删除了公告'); 
			return true;
		} else {
			$this->error = '删除失败';
			return false;
		}
	}
}