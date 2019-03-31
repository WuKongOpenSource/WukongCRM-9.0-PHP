ThinkPHP 5.0 SAE扩展
===============

添加下面的配置参数即可

~~~
'log'=>[
	'type'=> '\think\sae\Log',
]

'template' => [
	'type'	=>	'Think',
	'compile_type'	=> '\think\sae\Template',

]
'cache'=>[
	'type'  =>  '\think\sae\Cache',
]
~~~

数据库配置文件database.php中修改为：
~~~
// 数据库类型
'type'        => 'mysql',
// 服务器地址
'hostname'    => SAE_MYSQL_HOST_M . ',' . SAE_MYSQL_HOST_S,
// 数据库名
'database'    => SAE_MYSQL_DB,
// 用户名
'username'    => SAE_MYSQL_USER,
// 密码
'password'    => SAE_MYSQL_PASS,
// 端口
'hostport'    => SAE_MYSQL_PORT,
~~~