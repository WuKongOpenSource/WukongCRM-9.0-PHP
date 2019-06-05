<?php
// +----------------------------------------------------------------------
// | Description: 通讯录
// +----------------------------------------------------------------------
// | Author: yyk
// +----------------------------------------------------------------------

namespace app\oa\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;
use think\Db;

class Addresslist extends ApiCommon
{
    /**
     * 用于判断权限
     * @permission 无限制
     * @allow 登录用户可访问
     * @other 其他根据系统设置
    **/    
    public function _initialize()
    {
        $action = [
            'permission'=>[''],
            'allow'=>['index']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    } 
	
    //通讯录列表
    public function index()
    {
		$param = $this->param;
		$where = array();
		$where['user.status'] = 1;
		if ($param['search']) {
			$where['user.realname']  = array('like', '%'.$param['search'].'%');
		} 
		if ($param['type'] == 1) {
			$datalist =  Db::name('AdminUser')
				->where($where)
				->alias('user')
				->join('AdminStructure structure', 'structure.id = user.structure_id', 'LEFT')
				->join('HrmUserDet hud','hud.user_id = user.id','LEFT')
				->field('user.id,user.realname,user.thumb_img,user.post,user.structure_id,structure.name as structure_name,user.username,user.mobile,user.sex,user.email,user.status')
				->select();
				foreach( $datalist as $k=>$v){
					$datalist[$k]['thumb_img'] = $v['thumb_img'] ? getFullPath( $v['thumb_img'] ) : '';
				}
				$newarray = $this->groupByInitials($datalist,'realname');
				return resultArray(['data' => $newarray]);
		} else {
			$structureList = Db::name('AdminStructure')->select();
			foreach($structureList as $key=>$value){
				$where['user.structure_id'] = $value['id'];
				$temp = Db::name('AdminUser')
					->where($where)
					->alias('user')
					->join('HrmUserDet hud','hud.user_id = user.id','LEFT')
					->field('user.id,user.realname,user.username,user.thumb_img,user.post,user.structure_id,user.mobile,user.sex,user.email')
					->order('realname asc')
					->select();
				foreach( $temp as $k=>$v){
					$temp[$k]['thumb_img'] = $v['thumb_img'] ? getFullPath( $v['thumb_img'] ) : '';
				}
				$structureList[$key]['userList'] = $temp;
				$structureList[$key]['structure_name'] = $value['name'];
			}
			return resultArray(['data' => $structureList]);
		}
    }  

	/**
     * 二维数组根据首字母分组排序
     * @param  array  $data      二维数组
     * @param  string $targetKey 首字母的键名
     * @return array             根据首字母关联的二维数组
     */
    public function groupByInitials(array $data, $targetKey = 'name')
    {
        $data = array_map(function ($item) use ($targetKey) {
            return array_merge($item, [
                'initials' => $this->getInitials($item[$targetKey]),
            ]);
        }, $data);
        $data = $this->sortInitials($data);
        return $data;
    }
 
    /**
     * 按字母排序
     * @param  array  $data
     * @return array
     */
    public function sortInitials(array $data)
    {
        $sortData = [];
        foreach ($data as $key => $value) {
            $sortData[$value['initials']][] = $value;
        }
        ksort($sortData);
        return $sortData;
    }
    
    /**
     * 获取首字母
     * @param  string $str 汉字字符串
     * @return string 首字母
     */
    public function getInitials($str)
    {
        if (empty($str)) {return '';}
        $fchar = ord($str{0});
        if ($fchar >= ord('A') && $fchar <= ord('z')) {
            return strtoupper($str{0});
        }
 
        $s1  = iconv('UTF-8', 'gb2312', $str);
        $s2  = iconv('gb2312', 'UTF-8', $s1);
        $s   = $s2 == $str ? $s1 : $str;
        $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
        if($asc == -9300){
            return 'G';
        }
        if ($asc >= -20319 && $asc <= -20284) {
            return 'A';
        }
 
        if ($asc >= -20283 && $asc <= -19776) {
            return 'B';
        }
 
        if ($asc >= -19775 && $asc <= -19219) {
            return 'C';
        }
 
        if ($asc >= -19218 && $asc <= -18711) {
            return 'D';
        }
 
        if ($asc >= -18710 && $asc <= -18527) {
            return 'E';
        }
 
        if ($asc >= -18526 && $asc <= -18240) {
            return 'F';
        }
 
        if ($asc >= -18239 && $asc <= -17923) {
            return 'G';
        }
 
        if ($asc >= -17922 && $asc <= -17418) {
            return 'H';
        }
 
        if ($asc >= -17417 && $asc <= -16475) {
            return 'J';
        }
 
        if ($asc >= -16474 && $asc <= -16213) {
            return 'K';
        }
 
        if ($asc >= -16212 && $asc <= -15641) {
            return 'L';
        }
 
        if ($asc >= -15640 && $asc <= -15166) {
            return 'M';
        }
 
        if ($asc >= -15165 && $asc <= -14923) {
            return 'N';
        }
 
        if ($asc >= -14922 && $asc <= -14915) {
            return 'O';
        }
 
        if ($asc >= -14914 && $asc <= -14631) {
            return 'P';
        }
 
        if ($asc >= -14630 && $asc <= -14150) {
            return 'Q';
        }
 
        if ($asc >= -14149 && $asc <= -14091) {
            return 'R';
        }
 
        if ($asc >= -14090 && $asc <= -13319) {
            return 'S';
        }
 
        if ($asc >= -13318 && $asc <= -12839) {
            return 'T';
        }
 
        if ($asc >= -12838 && $asc <= -12557) {
            return 'W';
        }
 
        if ($asc >= -12556 && $asc <= -11848) {
            return 'X';
        }
 
        if ($asc >= -11847 && $asc <= -11056) {
            return 'Y';
        }
 
        if ($asc >= -11055 && $asc <= -10247) {
            return 'Z';
        }
        return null;
    }
}
