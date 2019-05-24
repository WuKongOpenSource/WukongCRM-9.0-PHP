### WukongCRM (Version 9.0)
Wukong Software has long provided enterprises with information services such as R&D, implementation, marketing, consulting, training and service of enterprise management software (CRM/HRM/OA/ERP, etc.). We have taken high technology as its starting point, technology as the core, and perfect after-sales service as its backing. With the spirit of stability and development, factualism and innovation, it has provided services for thousands of enterprises at home and abroad.

The development of Wukong benefits from open source and vice versa. In 2019, Wukong CRM will continue to adhere to the concept of “embracing openness, cooperation and win-win, creating value”, move forward on the road of open source, and make positive contributions to open source at home and abroad with more community developers.

Official website: ：[http://www.72crm.org](http://www.72crm.org/)

Demo ：[demo9.5kcrm.net](http://demo9.5kcrm.net/)(account number: 18888888888 password: 123456)

Wukong CRM adopts a new mode separating front-end from back-end. The front-end vue packaged files have been integrated into the warehouse code, eliminating the need for packaging operations.

If you need to adjust the front-end code, please download the front-end code separately. The front-end code is in the ux folder of the root directory.

## Main technology stack

Back end framework: ThinkPHP 5.0.2

Front end MVVM framework: Vue.JS 2.5.x

Routing: Vue-Router 3.x

Data interaction: Axios

UI framework: Element-UI 2.6.3

Wukong crm9.0 operating environment requires PHP5.6 or above.


## One click installation

The front-end vue packaged files has been integrated into the code without the package operation: Take the local (phpstudy integrated environment) setup as an example: download the Wukong CRM9.0 open source version, create the 72crm folder in the server root directory (www directory), and place code; browser access 

`http://localhost/72crm/index.php/admin/install/index.html `

Complete the deployment and installation of Wukong CRM9.0 according to the installation prompt steps.





## Development dependencies (you need  personalized  installation or adjust the front-end code and please follow the tutorial below, one-click installation users can ignore this step.) 

### Data interaction
Data interaction is implemented by axios and RESTful architecture. User verification is put in header by auth-key returning from log-in. It is worth noting that in the case of cross-domain, there will be a pre-request OPTION.

### Server setup
The framework used by the server is thinkphp5.0.2. Make sure to have the lamp/lnmp/wamp environment before building.

The setup mentioned here is actually putting the server framework into the WEB runtime environment and using port 80. Import the server root folder database file public/sql/5kcrm.sql and modify the config/database.php configuration file.

### Configuration requirements
PHP >= 5.6.0 (not support for PHP7 and above) When accessing http://localhost/, "Wukong Software" appears, which represents the successful setup of the backend interface.
### Front-end deployment

Install the front-end part of node.js  based on node.js, so you must first install node.js with  version  6.0 or above.

Use npm to install dependencies, download the Wukong CRM9.0 front-end code; place the code in the backend peer directory frontend, execute the command to install dependencies: npm install

    npm install

Modify the internal configuration and request address or domain name: modify BASE_API (development environment server address, default localhost) in config / dev.env.js, modify the custom port:  modify the dev object port parameter in config / index.js (default 8080, Not recommend to modify)

### Running front end npm run dev

     npm run dev

Note: The front-end service starts, it will occupy port 8080 by default, so before starting the front-end service, please make sure that port 8080 is not occupied. The Server port needs to be set up before the program runs.

---

### 悟空CRM（9.0版本）
悟空软件长期为企业提供企业管理软件(CRM/HRM/OA/ERP等)的研发、实施、营销、咨询、培训、服务于一体的信息化服务。悟空软件以高科技为起点，以技术为核心、以完善的售后服务为后盾，秉承稳固与发展、求实与创新的精神，已为国内外上千家企业提供服务。

悟空的发展受益于开源，也会回馈于开源。2019年，悟空CRM会继续秉承“拥抱开放、合作共赢、创造价值”的理念，在开源的道路上继续砥砺前行，和更多的社区开发者一起为国内外开源做出积极贡献。

官网：[http://www.5kcrm.com](http://www.5kcrm.com/)

官网：[http://www.72crm.com](http://www.72crm.com/)

论坛：[http://bbs.72crm.net](http://bbs.72crm.net/)

演示地址：[demo9.5kcrm.net](http://demo9.5kcrm.net/)(帐号：18888888888   密码：123456)

QQ群交流群⑩群：[486745026](https:////shang.qq.com/wpa/qunwpa?idkey=f4687b809bf63f08f707aa1c56dee8dbcb9526237c429c4532222021d65bf83c)

JAVA版下载地址：[https://github.com/72crm/72crm-java](https://note.youdao.com/)


悟空CRM采用全新的前后端分离模式，本仓库代码中已集成前端vue打包后文件，可免去打包操作

如需调整前端代码，请单独下载前端代码，前端代码在根目录的ux文件夹中

## 主要技术栈

后端框架：ThinkPHP 5.0.2

前端MVVM框架：Vue.JS 2.5.x 

路由：Vue-Router 3.x 

数据交互：Axios 

UI框架：Element-UI 2.6.3 

悟空crm9.0的运行环境要求PHP5.6以上


## 一键安装

代码中已集成前端vue打包后文件，可免去打包操作：
以本地（phpstudy集成环境）搭建举例：
下载悟空CRM9.0开源版，在服务器根目录（www目录）下创建72crm文件夹，并放置代码； 浏览器访问

`http://localhost/72crm/index.php/admin/install/index.html `

根据安装提示步骤，完成悟空CRM9.0 的部署安装





## 开发依赖（需个性化安装或调整前端代码请按照以下教程，一键安装用户可忽略）

### 数据交互 
数据交互通过axios以及RESTful架构来实现 
用户校验通过登录返回的auth_key放在header 
值得注意的一点是：跨域的情况下，会有预请求OPTION的情况

### Server搭建 
服务端使用的框架为thinkphp5.0.2，搭建前请确保拥有lamp/lnmp/wamp环境。

这里所说的搭建其实就是把server框架放入WEB运行环境，并使用80端口。
导入服务端根文件夹数据库文件public/sql/5kcrm.sql，并修改config/database.php配置文件。

### 配置要求
PHP >= 5.6.0 （暂不支持PHP7及以上版本）
当访问 http://localhost/, 出现“悟空软件”即代表后端接口搭建成功。
### 前端部署
安装node.js 前端部分是基于node.js上运行的，所以必须先安装`node.js`，版本要求为6.0以上

使用npm安装依赖 下载悟空CRM9.0前端代码； 可将代码放置在后端同级目录frontend，执行命令安装依赖：

    npm install

修改内部配置 修改请求地址或域名：config/dev.env.js里修改BASE_API（开发环境服务端地址，默认localhost） 修改自定义端口：config/index.js里面的dev对象的port参数（默认8080，不建议修改）

### 运行前端

     npm run dev

注意：前端服务启动，默认会占用8080端口，所以在启动前端服务之前，请确认8080端口没有被占用。
程序运行之前需搭建好Server端



## 系统介绍

以下为悟空CRM9.0 部分功能系统截图

![](https://github.com/72crm/72crm/blob/master/ux/intro_img/g1.png)
![](https://github.com/72crm/72crm/blob/master/ux/intro_img/g2.png)
![](https://github.com/72crm/72crm/blob/master/ux/intro_img/g3.png)
![](https://github.com/72crm/72crm/blob/master/ux/intro_img/g4.png)
![](https://github.com/72crm/72crm/blob/master/ux/intro_img/g5.png)
![](https://github.com/72crm/72crm/blob/master/ux/intro_img/g6.png)
![](https://github.com/72crm/72crm/blob/master/ux/intro_img/g7.png)
![](https://github.com/72crm/72crm/blob/master/ux/intro_img/g8.png)
![](https://github.com/72crm/72crm/blob/master/ux/intro_img/g9.png)
![](https://github.com/72crm/72crm/blob/master/ux/intro_img/g10.png)




