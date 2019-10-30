--
-- 登录记录数据表 2019-09-20
--
CREATE TABLE `5kcrm_admin_login_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '登录成功与否：0成功；1密码错误；2账号禁用',
  `create_user_id` int(10) NOT NULL DEFAULT '0' COMMENT '员工ID',
  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '登录时间',
  `ip` varchar(64) NOT NULL DEFAULT '' COMMENT '登录IP，IPv6是46 凑整64位',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '登录地址',
  `browser` varchar(64) NOT NULL DEFAULT '' COMMENT '浏览器',
  `os` varchar(64) NOT NULL DEFAULT '' COMMENT '操作系统',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '说明 - 暂时记录user-agent',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- 
-- 商机、合同导出权限 2019-10-11
-- 
INSERT INTO `5kcrm_admin_rule`(`id`, `types`, `title`, `name`, `level`, `pid`, `status`) VALUES
(146, 2, '导出', 'excelExport', 3, 34, 1),
(147, 2, '导出', 'excelExport', 3, 42, 1),
(148, 2, '合同作废', 'cancel', 3, 42, 1);
