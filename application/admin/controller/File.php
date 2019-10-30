<?php
// +----------------------------------------------------------------------
// | Description: 附件
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\admin\controller;

use think\Hook;
use think\Request;

class File extends ApiCommon
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
            'allow'=>['index','save','delete','update','read', 'download']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }         
    }

    /**
     * 附件列表
     * @author Michael_xu
     * @param 
     * @return                            
     */
    public function index()
    {
        $fileModel = model('File');
        $param = $this->param;
        $data = $fileModel->getDataList($param, $param['by']);
        return resultArray(['data' => $data]);
    }

	/**
     * 附件上传
     * @author Michael_xu
     * @return                            
     */
    public function save()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept"); 
        $files = request()->file('file');
        $i = 0;
        $newFiles = array();
		if($files){
			 foreach ($files as $v) {
				$newFiles[$i]['obj'] = $v;  
				$newFiles[$i]['types'] = 'file';
				$i++;
			}
		}
       
        $imgs = request()->file('img');
        if ($imgs) {
            foreach ($imgs as $v) {
                $newFiles[$i]['obj'] = $v;  
                $newFiles[$i]['types'] = 'img';
                $i++;
            }            
        }      
        $fileModel = model('File');
        $param = $this->param;
        $param['create_user_id'] = $this->userInfo['id'];
        $res = $fileModel->createData($newFiles, $param);
		if($res){
			return resultArray(['data' => $res]);
		} else {
			return resultArray(['error' => $fileModel->getError()]);
		}
        
    }

	/**
     * 附件删除
     * @author Michael_xu
     * @param 通过 save_name 作为条件 来删除附件
     * @return                            
     */ 
    public function delete()
    {
        $fileModel = model('File');
        $param = $this->param;
        $res = $fileModel->delFileBySaveName($param['save_name'], $param);
        if (!$res) {
            return resultArray(['error' => $fileModel->getError()]);
        }
        return resultArray(['data' => '删除成功']);        
    }

    /**
     * 附件编辑
     */
    public function update()
    {
        $fileModel = model('File');
        $param = $this->param;
        if ( $param['save_name'] && $param['name'] ) {
            $ret = $fileModel->updateNameBySaveName($param['save_name'],$param['name']);
            if ($ret) {
                return resultArray(['data'=>'操作成功']);
            } else {
                return resultArray(['error'=>'操作失败']);
            } 
        } else {
            return resultArray(['error'=>'参数错误']);
        }
    }

	/**
     * 附件查看（下载）
     * @author Michael_xu
     * @return                            
     */  
    public function read()
    {
        $fileModel = model('File');
        $param = $this->param;
        $data = $fileModel->getDataBySaveName($param['save_name']);
        if (!$data) {
            return resultArray(['error' => $this->getError()]);
        }
        return resultArray(['data' => $data]);        
    }   
    
    /**
     * 静态资源文件下载
     */
    public function download()
    {
        $path = $this->param['path'];
        $name = $this->param['name'] ?: '';
        return download(realpath('./public/' . $path), $name);
    }
}
