<?php
// +----------------------------------------------------------------------
// | Description: 系统配置
// +----------------------------------------------------------------------
// | Author:  yykun
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Hook;
use think\Request;

class System extends ApiCommon
{
    //用于判断权限
    public function _initialize()
    {
        $action = [
            'permission'=>['index'],
            'allow'=>['']
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }            
    }        

    //信息列表
    public function index()
    {   
        $systemModel = model('System');
        $data = $systemModel->getDataList();
        return resultArray(['data' => $data]);
    }
	
    //编辑保存
	public function save()
	{
        $param = $this->param;
		$systemModel = model('System');
        $fileModel = model('File');
        $syncModel = model('Sync');
        //处理图片
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept"); 
        $imgfile = request()->file('file');
        if ($imgfile) {
			$resImg = $fileModel->updateByField($imgfile, 'admin_system', 2, 'value','', '', '');
		}
        unset($param['file']);
		$ret = $systemModel->createData($param);
		if ($ret) {
            $syncModel->syncData($param);
			return resultArray(['data'=>'保存成功']);
		} else {
			return resultArray(['error'=>'保存失败']);
		}
	}
}
 