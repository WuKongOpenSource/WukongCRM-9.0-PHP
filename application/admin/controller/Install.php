<?php
// +----------------------------------------------------------------------
// | Description: 安装
// +----------------------------------------------------------------------
// | Author:  Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Db;
use Env;

class Install extends Controller
{
    // private $count = 100;
    // private $now = 0; 

    public function _initialize()
    {
        /*防止跨域*/      
        header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, authKey, sessionId");
        $param = Request::instance()->param();          
        $this->param = $param;

        $request = request();
        $m = strtolower($request->module());
        $c = strtolower($request->controller());
        $a = strtolower($request->action());     
	
        if (!in_array($a, array('upgrade','upgradeprocess','checkversion')) && file_exists(CONF_PATH . "install.lock")) {
            echo "<meta http-equiv='content-type' content='text/html; charset=UTF-8'> <script>alert('请勿重复安装!');location.href='".$_SERVER["HTTP_HOST"]."';</script>";
            die();       
        }    
    }

    private $upgrade_site = "http://message.72crm.com/";

    /**
     * [index 安装步骤]
     * @author Michael_xu 
     * @param  
     */    
    public function index()
    {
        if (file_exists(CONF_PATH . "install.lock")) {
            echo "<meta http-equiv='content-type' content='text/html; charset=UTF-8'> <script>alert('请勿重复安装!');location.href='".$_SERVER["HTTP_HOST"]."';</script>";
            die();     
        }
        if (!file_exists(getcwd() . "/public/sql/5kcrm.sql")) {
            echo "<meta http-equiv='content-type' content='text/html; charset=UTF-8'> <script>alert('缺少必要的数据库文件!');location.href='".$_SERVER["HTTP_HOST"]."';</script>";
            die();     
        }         
        return $this->fetch('index');      
    }

    public function step1()
    {
		session('install_error',null);
        $data           = [];
        $data['env']    = self::checkNnv();
        $data['dir']    = self::checkDir();
        $data['version'] = $this->version();
        $this->assign('data',$data);
        return $this->fetch('step1');
    }

    //版本
    public function version()
    {
        $res = include(CONF_PATH.'version.php'); 
        return $res ? : array('VERSION' => '9.0.0','RELEASE' => '20190330'); 
    }    

    public function step2(){
		if (session('install_error')){
            echo "<meta http-equiv='content-type' content='text/html; charset=UTF-8'> <script>alert('环境检测未通过，不能进行下一步操作!');location.href='".$_SERVER["HTTP_REFERER"]."';</script>";
            die(); 
        }
        $data['os'] = PHP_OS;
        $data['php'] = phpversion();
        $data['version'] = $this->version();
        $this->assign('envir_data',$data);
        return $this->fetch();
    }
	
    public function step3(){
        return $this->fetch();
    }

    public function step4(){
        if (session('install_error')){
            return resultArray(['error' => '环境检测未通过，不能进行下一步操作!']);    
        } 
        if (file_exists(CONF_PATH . "install.lock")) {
            return resultArray(['error' => '请勿重复安装!']);       
        } 
        if (!file_exists(getcwd() . "/public/sql/5kcrm.sql")) {
            return resultArray(['error' => '缺少必要的数据库文件!']);      
        } 
        $temp = $this->param;
        $param = $temp['form'];
        $db_config['type'] = 'mysql';
        $db_config['hostname'] = $param['databaseUrl'];
        $db_config['hostport'] = $param['databasePort'];
        $db_config['database'] = $param['databaseName'];
        $db_config['username'] = $param['databaseUser'];
        $db_config['password'] = $param['databasePwd'];        
        $db_config['prefix'] = $param['databaseTable'];
        
        $username = $param['root'];
        $password = $param['pwd'];
        if (empty($db_config['hostname'])) {
            return resultArray(['error' => '请填写数据库主机!']);
        }           
        if (empty($db_config['hostport'])) {
            return resultArray(['error' => '请填写数据库端口!']);
        }
        if (preg_match('/[^0-9]/', $db_config['hostport'])) {
            return resultArray(['error' => '数据库端口只能是数字!']);
        }
        if (empty($db_config['database'])) {
            return resultArray(['error' => '请填写数据库名!']);
        }
        if (empty($db_config['username'])) {
            return resultArray(['error' => '请填写数据库用户名!']);
        }
        if (empty($db_config['password'])) {
            return resultArray(['error' => '请填写数据库密码!']);
        }        
        if (empty($db_config['prefix'])) {
            return resultArray(['error' => '请填写表前缀!']);
        }
        if (preg_match('/[^a-z0-9_]/i', $db_config['prefix'])) {
            return resultArray(['error' => '表前缀只能包含数字、字母和下划线!']);
        }
        if (empty($username)) {
            return resultArray(['error' => '请填写管理员用户名!']);
        }
        if (empty($password)) {
            return resultArray(['error' => '请填写管理员密码!']);
        }
        session('install_count','');
        session('install_now','');
        $database = $db_config['database'];
		unset($db_config['database']);
        $connect = Db::connect($db_config);
        // 检测数据库连接
        try{
            $ret = $connect->execute('select version()');
        }catch(\Exception $e){
            return resultArray(['error' => '数据库连接失败，请检查数据库配置！']);
        }
        $check = $connect->execute("SELECT * FROM information_schema.schemata WHERE schema_name='".$database."'");
        if (!$check && !$connect->execute("CREATE DATABASE IF NOT EXISTS `".$database."` default collate utf8_general_ci ")) {
            return resultArray(['error' => '没有找到您填写的数据库名且无法创建！请检查连接账号是否有创建数据库的权限!']);
        }
		$db_config['database'] = $database;
        self::mkDatabase($db_config);
        $C_Patch = substr($_SERVER['SCRIPT_FILENAME'],0,-10);
        $sql = file_get_contents( $C_Patch.'/public/sql/5kcrm.sql');
        $sqlList = parse_sql($sql, 0, ['5kcrm_' => $db_config['prefix']]);
        if ($sqlList) {
            $sqlList = array_filter($sqlList);
            $install_count = count($sqlList);
            session('install_count',$install_count);
            foreach ($sqlList as $k=>$v) {
                $install_now = $k+1;
                session('install_now',$install_now);
                try {
                    $temp_sql = $v.';';
                    Db::connect($db_config)->query($temp_sql);
                } catch(\Exception $e) {
                    // return resultArray(['error' => '请启用InnoDB数据引擎，并检查数据库是否有DROP和CREATE权限']);
                    return resultArray(['error' => '数据库sql安装出错，请操作数据库手动导入sql文件']);
                }
            }
        } 
        $salt = substr(md5(time()),0,4);
        $password = user_md5(trim($password), $salt, $username);
		//插入信息
        Db::connect($db_config)->query("insert into ".$db_config['prefix']."admin_user (username, password, salt, img, thumb_img, realname, create_time, num, email, mobile, sex, status, structure_id, post, parent_id, type, authkey, authkey_time ) values ( '".$username."', '".$password."', '".$salt."', '', '', '管理员', ".time().", '', '', '".$username."', '', 1, 1, 'CEO', 0, 1, '', 0 )");
        Db::connect($db_config)->query("insert into ".$db_config['prefix']."hrm_user_det (user_id, join_time, type, status, userstatus, create_time, update_time, mobile, sex, age, job_num, idtype, idnum, birth_time, nation, internship, done_time, parroll_id, email, political, location, leave_time ) values ( 1, ".time().", 1, 1, 2, ".time().", ".time().", '".$username."', '', 0, '', 0, '', '', 0, 0, 0, 0, '', '', '', 0 )");
        touch(CONF_PATH . "install.lock"); 
        return resultArray(['data'=>'安装成功']);
    }
	
	//ajax 进度条
    public function progress()
    {
        $data['length'] = session('install_count');
        $data['now'] = session('install_now');
        return resultArray(['data'=>$data]);
    }

    //添加database.php文件
    private function mkDatabase(array $data)
    {
        $code = <<<INFO
<?php
return [
    // 数据库类型
    'type'            => 'mysql',
    // 服务器地址
    'hostname'        => '{$data['hostname']}',
    // 数据库名
    'database'        => '{$data['database']}',
    // 用户名
    'username'        => '{$data['username']}',
    // 密码
    'password'        => '{$data['password']}',
    // 端口
    'hostport'        => '{$data['hostport']}',
    // 连接dsn
    'dsn'             => '',
    // 数据库连接参数
    'params'          => [],
    // 数据库编码默认采用utf8
    'charset'         => 'utf8',
    // 数据库表前缀
    'prefix'          => '{$data['prefix']}',
    // 数据库调试模式
    'debug'           => true,
    // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
    'deploy'          => 0,
    // 数据库读写是否分离 主从式有效
    'rw_separate'     => false,
    // 读写分离后 主服务器数量
    'master_num'      => 1,
    // 指定从服务器序号
    'slave_no'        => '',
    // 自动读取主库数据
    'read_master'     => false,
    // 是否严格检查字段是否存在
    'fields_strict'   => true,
    // 数据集返回类型
    'resultset_type'  => 'array',
];

INFO;
        file_put_contents( CONF_PATH.'database.php', $code);
        // 判断写入是否成功
        $config = include CONF_PATH.'database.php';
        if (empty($config['database']) || $config['database'] != $data['database']) {
            return $this->error('[config/database.php]数据库配置写入失败！');
            exit;
        }
        return 1;
    }

    //检查目录权限
    public function check_dir_iswritable($dir_path){ 
        $dir_path=str_replace( '\\','/',$dir_path); 
        $is_writale=1; 
        if (!is_dir($dir_path)) { 
            $is_writale=0; 
            return $is_writale; 
        } else { 
            $file_hd=@fopen($dir_path.'/test.txt','w'); 
            if (!$file_hd) { 
                @fclose($file_hd); 
                @unlink($dir_path.'/test.txt'); 
                $is_writale=0; 
                return $is_writale; 
            } 
            $dir_hd = opendir($dir_path); 
            while (false !== ($file=readdir($dir_hd))) { 
                if ($file != "." && $file != "..") { 
                    if (is_file($dir_path.'/'.$file)) { 
                        //文件不可写，直接返回 
                        if (!is_writable($dir_path.'/'.$file)) { 
                            return 0; 
                        }  
                    } else { 
                        $file_hd2=@fopen($dir_path.'/'.$file.'/test.txt','w'); 
                        if (!$file_hd2) { 
                            @fclose($file_hd2); 
                            @unlink($dir_path.'/'.$file.'/test.txt'); 
                            $is_writale=0; 
                            return $is_writale; 
                        } 
                        //递归 
                        $is_writale=check_dir_iswritable($dir_path.'/'.$file); 
                    } 
                } 
            } 
        } 
        return $is_writale; 
    } 

    /**
     * [checkVersion 检查升级]
     * @author Michael_xu 
     * @param  
     */     
    public function checkVersion(){
        $version = Config::load('version');
        $info = sendRequest($this->upgrade_site.'index.php?m=version&a=checkVersion', $version['VERSION']);
        if ($info){
            return resultArray(['data' => $info]);
        } else {
            return resultArray(['error' => '检查新版本出错!']);
        }
    }    

    /**
     * 环境检测
     * @return array
     */
    private function checkNnv()
    {
        $items = [
            'os'      => ['操作系统', '不限制', '类Unix', PHP_OS, 'ok'],
            'php'     => ['PHP版本', '5.6', '5.6.x', PHP_VERSION, 'ok'],
        ];
		session('install_error','');
        if (substr($items['php'][3],0,3) < $items['php'][1]) {
            $items['php'][4] = 'no';
            session('install_error', true);
        }
        $tmp = function_exists('gd_info') ? gd_info() : [];
        if (empty($tmp['GD Version'])) {
            $items['gd'][3] = '未安装';
            $items['gd'][4] = 'no';
            session('install_error', true);
        } else {
            $items['gd'][3] = $tmp['GD Version'];
        }
        return $items;
    }
    
    /**
     * 目录权限检查
     * @return array
     */
    private function checkDir()
    {
        $items = [
            ['dir', $this->root_path.'application', 'application', '读写', '读写', 'ok'],
            ['dir', $this->root_path.'extend', 'extend', '读写', '读写', 'ok'],
            ['dir', $this->root_path.'runtime', './temp', '读写', '读写', 'ok'],
            ['dir', $this->root_path.'public', './upload', '读写', '读写', 'ok'],
            ['file', $this->root_path.'config', 'config', '读写', '读写', 'ok'],
        ];
        foreach ($items as &$v) {
            if ($v[0] == 'dir') {// 文件夹
                if (!is_writable($v[1])) {
                    if (is_dir($v[1])) {
                        $v[4] = '不可写';
                        $v[5] = 'no';
                    } else {
                        $v[4] = '不存在';
                        $v[5] = 'no';
                    }
                    session('install_error', true);
                }
            } else {// 文件
                if (!is_writable($v[1])) {
                    $v[4] = '不可写';
                    $v[5] = 'no';
                    session('install_error', true);
                }
            }
        }
        return $items;
    }
}