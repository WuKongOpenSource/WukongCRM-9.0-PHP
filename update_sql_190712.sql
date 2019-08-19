INSERT INTO `5kcrm_admin_rule` VALUES ('86', '3', '项目管理', 'work', '1', '0', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('87', '3', '项目', 'work', '2', '86', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('88', '3', '任务', 'task', '2', '86', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('89', '3', '项目设置', 'update', '3', '87', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('90', '3', '任务列表', 'taskClass', '2', '86', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('91', '3', '新建任务列表', 'save', '3', '90', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('92', '3', '编辑任务列表', 'update', '3', '90', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('93', '3', '删除任务列表', 'delete', '3', '90', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('94', '3', '创建', 'save', '3', '88', '1');

CREATE TABLE IF NOT EXISTS `5kcrm_work_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `work_id` int(11) NOT NULL COMMENT '项目ID',
  `user_id` int(11) NOT NULL COMMENT '成员ID',
  `types` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1管理员，0初始权限',
  `group_id` int(11) NOT NULL COMMENT '角色ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='项目成员表';
COMMIT;

INSERT INTO `5kcrm_admin_group` (`id`, `pid`, `title`, `rules`, `remark`, `status`, `type`, `types`) VALUES (NULL, '1', '项目管理员', '', '项目管理员', '1', '1', '7');

ALTER TABLE `5kcrm_admin_group` ADD `system` TINYINT(4) NOT NULL DEFAULT '0' COMMENT '系统角色' AFTER `types`;

INSERT INTO `5kcrm_admin_group` (`id`, `pid`, `title`, `rules`, `remark`, `status`, `type`, `types`, `system`) VALUES (NULL, '5', '编辑', ',88,94,91,92,86,90,', '成员初始加入时默认享有的权限,可修改权限范围', '1', '0', '7', '1');

INSERT INTO `5kcrm_admin_group` (`id`, `pid`, `title`, `rules`, `remark`, `status`, `type`, `types`, `system`) VALUES (NULL, '5', '只读', '', '项目只读角色', '1', '0', '0', '0');

CREATE TABLE `5kcrm_admin_user_threeparty` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` int(10) NOT NULL COMMENT '用户ID',
  `ding_id` varchar(100) NOT NULL COMMENT '钉钉userID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='关联第三方';

ALTER TABLE `5kcrm_task` ADD `is_archive` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '1归档' AFTER `top_order_id`;