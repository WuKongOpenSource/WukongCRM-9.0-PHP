<?php
// +----------------------------------------------------------------------
// | Description: 组织架构
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Hook;
use think\Request;
use think\Db;

class Structures extends ApiCommon
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
            'allow'=>['index','read','save','update','delete','deletes','enables','listdialog','subindex','getsubuserbystructrue']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
    }    
    
    //获取权限范围内的部门
    public function subIndex()
    {
        $param = $this->param;
        $userInfo = $this->userInfo;
        $userModel = model('User');
        $m = $param['m'] ? : '';
        $c = $param['c'] ? : '';
        $a = $param['a'] ? : '';
        $ret = $userModel->getUserByPer($m, $c, $a);
        $where['au.id'] = ['in',$ret];
        $structure_ids = Db::name('AdminUser')
            ->alias('au')
            ->join('AdminStructure ast','ast.id = au.structure_id','LEFT')
            ->where($where)
            ->group('structure_id')
            ->order('structure_id asc')
            ->column('ast.id');
        $list = Db::name('AdminStructure')
            ->group('id')
            ->order('id asc')
            ->select();
        $result = getSubObj(0, $list, '', 1);
        $adminTypes = adminGroupTypes($userInfo['id']);
        if(!in_array(1,$adminTypes)){
            foreach ($result as $key => $value) {
                if(!in_array($value['id'],$structure_ids)){
                    unset($result[$key]);
                }
            }
        }
        return resultArray(['data'=>$result]);
    }

    //获取部门下权限范围内员工
    public function getSubUserByStructrue()
    {
        $param = $this->param;
        $userModel = model('User');
        $structureList = $userModel->getSubUserByStr($param['structure_id'],2);
        if($param['structure_id']){
            $where['id'] = ['in',$structureList];
        }
        $list =Db::name('AdminUser')->field('id,realname')->where($where)->select();
        $list = $list?:array();
        return resultArray(['data'=>$list]);
    }

    /**
     * 部门列表
     * @author Michael_xu
     * @param 
     * @return                            
     */    
    public function index()
    {   
        //权限判断
        // if (!checkPerByAction('admin', 'users', 'index')) {
        //     header('Content-Type:application/json; charset=utf-8');
        //     exit(json_encode(['code'=>102,'error'=>'无权操作']));
        // }        
        $structureModel = model('Structure');
		$param = $this->param;
        $type = $param['type'] ? 'tree' : '';
		$data = $structureModel->getDataList($type);
        return resultArray(['data' => $data]);
    }

    /**
     * 部门详情
     * @author Michael_xu
     * @param 
     * @return                            
     */    
    public function read()
    {           
        $structureModel = model('Structure');
        $param = $this->param;
        $data = $structureModel->getDataById($param['id']);
        if (!$data) {
            return resultArray(['error' => $structureModel->getError()]);
        } 
        return resultArray(['data' => $data]);
    }

    /**
     * 部门添加
     * @author Michael_xu
     * @param 
     * @return                            
     */    
    public function save()
    {
        //权限判断
        if (!checkPerByAction('admin', 'users', 'structures_save')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }        
        $structureModel = model('Structure');
        $param = $this->param;
		if(!$param['pid']){
			resultArray(['error' => '请选择上级部门']);
		}
        $data = $structureModel->createData($param);
        if (!$data) {
            return resultArray(['error' => $structureModel->getError()]);
        } 
        return resultArray(['data' => '添加成功']);
    }

    /**
     * 部门编辑
     * @author Michael_xu
     * @param 
     * @return                            
     */
    public function update()
    {
        //权限判断
        if (!checkPerByAction('admin', 'users', 'structures_update')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }        
        $structureModel = model('Structure');
        $param = $this->param;
        $dataInfo = $structureModel->getDataByID($param['id']);
        if (empty($dataInfo['pid']) || $param['id'] == '1') {
            unset($param['pid']);
        }
        $data = $structureModel->updateDataById($param, $param['id']);
        if (!$data) {
            return resultArray(['error' => $structureModel->getError()]);
        } 
        return resultArray(['data' => '编辑成功']);
    }

    /**
     * 部门删除
     * @author Michael_xu
     * @param 
     * @return                            
     */  
    public function delete()
    {
        //权限判断
        if (!checkPerByAction('admin', 'users', 'structures_delete')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }        
        $structureModel = model('Structure');
        $param = $this->param;
        $data = $structureModel->delStrById($param['id']);       
        if (!$data) {
            return resultArray(['error' => $structureModel->getError()]);
        } 
        return resultArray(['data' => '删除成功']);    
    }

    /**
     * 部门启用、停用
     * @author Michael_xu
     * @param 
     * @return                            
     */    
    public function enables()
    {
        //权限判断
        if (!checkPerByAction('admin', 'users', 'structures_update')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }        
        $structureModel = model('Structure');
        $param = $this->param;
        $data = $structureModel->enableDatas($param['ids'], $param['status'], true);  
        if (!$data) {
            return resultArray(['error' => $structureModel->getError()]);
        } 
        return resultArray(['data' => '操作成功']);         
    }

    /**
     * 部门list
     * @author Michael_xu
     * @param 
     * @return                            
     */  
    public function listDialog()
    {   
        $param = $this->param;
        $structure_id = $param['id'];
        $type = $param['type'];
        if (!$structure_id) {
            return resultArray(['error' => '参数错误']);
        }
        $structureList = db('admin_structure')->select();
        if ($type == 'update') {
            //去除自身及下属部门
            foreach ($structureList as $k => $v) {
                if (($v['id'] == $structure_id) || ($v['pid'] == $structure_id)) {
                    unset($structureList[$k]);
                }
            }
        }
        $structureList = getSubObj(0, $structureList, '', 1);
        return resultArray(['data' => $structureList]);   
    }  
}
 