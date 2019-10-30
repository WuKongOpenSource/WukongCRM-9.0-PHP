ALTER TABLE `5kcrm_admin_user_threeparty` 
CHANGE COLUMN `ding_id` `key` varchar(100) NOT NULL COMMENT '关联模块' AFTER `user_id`,
ADD COLUMN `value` varchar(512) NOT NULL DEFAULT '' COMMENT '关联内容' AFTER `key`;

ALTER TABLE `5kcrm_crm_contract` CHANGE `check_status` `check_status` TINYINT(4) NOT NULL DEFAULT '0' COMMENT '0待审核、1审核中、2审核通过、3审核未通过、4撤销、5草稿(未提交)';

ALTER TABLE `5kcrm_crm_receivables` CHANGE `check_status` `check_status` TINYINT(4) NOT NULL DEFAULT '0' COMMENT '0待审核、1审核中、2审核通过、3审核未通过、4撤销、5草稿(未提交)';

INSERT INTO `5kcrm_admin_scene` (`scene_id`, `types`, `name`, `user_id`, `order_id`, `data`, `is_hide`, `type`, `bydata`, `create_time`, `update_time`) VALUES (NULL, 'crm_customer_pool', '今日进入公海的客户', '0', '0', '', '0', '1', 'pool', '1566748800', '1566748800');

ALTER TABLE `5kcrm_crm_customer` CHANGE `deal_time` `deal_time` INT(11) NOT NULL COMMENT '领取，分配，创建时间';

CREATE TABLE IF NOT EXISTS `5kcrm_crm_customer_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_ids` varchar(255) NOT NULL COMMENT '员工',
  `structure_ids` varchar(255) NOT NULL COMMENT '部门',
  `types` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1拥有客户上限2锁定客户上限',
  `value` int(10) NOT NULL COMMENT '数值',
  `is_deal` tinyint(4) NOT NULL COMMENT '1成交客户',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='客户配置表（锁定、拥有）';

ALTER TABLE `5kcrm_task` CHANGE `status` `status` TINYINT(2) NOT NULL DEFAULT '1' COMMENT '完成状态 1正在进行,2延期,5结束';

INSERT INTO `5kcrm_admin_rule`(`id`, `types`, `title`, `name`, `level`, `pid`, `status`) VALUES
(104, 2, '成交状态', 'deal_status', 3, 10, 1);

INSERT INTO `5kcrm_admin_rule`(`id`, `types`, `title`, `name`, `level`, `pid`, `status`) VALUES
(105, 0, '全部', 'admin', 1, 0, 1),
(106, 0, '企业首页', 'system', 2, 105, 1),
(107, 0, '查看', 'index', 3, 106, 1),
(108, 0, '编辑', 'save', 3, 106, 1),
(109, 0, '应用管理', 'configset', 2, 105, 1),
(110, 0, '查看', 'index', 3, 109, 1),
(111, 0, '停用/启用', 'update', 3, 109, 1),
(112, 0, '员工与部门管理', 'users', 2, 105, 1),
(113, 0, '部门/员工查看', 'index', 3, 112, 1),
(114, 0, '员工新建', 'save', 3, 112, 1),
(115, 0, '员工禁用/激活', 'enables', 3, 112, 1),
(116, 0, '员工操作', 'update', 3, 112, 1),
(117, 0, '部门新建', 'structures_save', 3, 112, 1),
(118, 0, '部门编辑', 'structures_update', 3, 112, 1),
(119, 0, '部门删除', 'structures_delete', 3, 112, 1),
(120, 0, '角色权限管理', 'groups', 2, 105, 1),
(121, 0, '角色权限设置', 'update', 3, 120, 1),
(122, 0, '工作台设置', 'oa', 2, 105, 1),
(123, 0, '办公审批管理', 'examine', 3, 122, 1),
(124, 0, '审批流程管理', 'examine_flow', 2, 105, 1),
(125, 0, '审批流程管理', 'index', 3, 124, 1),
(126, 0, '客户管理设置', 'crm', 2, 105, 1),
(127, 0, '自定义字段设置', 'field', 3, 126, 1),
(128, 0, '客户公海规则', 'pool', 3, 126, 1),
(129, 0, '业务参数设置', 'setting', 3, 126, 1),
(130, 0, '业绩目标设置', 'achievement', 3, 126, 1);

ALTER TABLE `5kcrm_admin_config` CHANGE `type` `type` TINYINT(2) NOT NULL COMMENT '类型：1已发布，2未发布，3增值';

ALTER TABLE `5kcrm_admin_config` CHANGE `typestatus` `pid` TINYINT(4) NOT NULL COMMENT '父级ID';

INSERT INTO `5kcrm_admin_rule`(`id`, `types`, `title`, `name`, `level`, `pid`, `status`) VALUES
(131, 1, '全部', 'oa', 1, 0, 1),
(132, 1, '通讯录', 'addresslist', 2, 131, 1),
(133, 1, '查看列表', 'index', 3, 132, 1),
(134, 1, '公告', 'announcement', 2, 131, 1),
(135, 1, '新建', 'save', 3, 134, 1),
(136, 1, '编辑', 'update', 3, 134, 1),
(137, 1, '删除', 'delete', 3, 134, 1);

ALTER TABLE `5kcrm_admin_group` CHANGE `pid` `pid` TINYINT(4) NOT NULL COMMENT '分类：0客户自定义角色,1系统默认管理角色,2客户管理角色,3人力资源管理角色,4财务管理角色,5项目管理角色,6办公管理角色';

ALTER TABLE `5kcrm_admin_rule` CHANGE `types` `types` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '0系统设置1工作台2客户管理3项目管理4人力资源5财务管理6商业智能(客戶)7商业智能(办公)';

INSERT INTO `5kcrm_crm_config` (`id`, `name`, `value`, `description`) VALUES (NULL, 'remind_day', '7', '公海提前提醒天数');

INSERT INTO `5kcrm_crm_config` (`id`, `name`, `value`, `description`) VALUES (NULL, 'remind_config', '0', '1开启(公海提前提醒天数)');

INSERT INTO `5kcrm_admin_rule`(`id`, `types`, `title`, `name`, `level`, `pid`, `status`) VALUES
(138, 0, '项目管理设置', 'work', 2, 105, 1),
(139, 0, '项目管理', 'work', 3, 138, 1);

UPDATE `5kcrm_admin_group` SET `rules` = ',105,106,107,108,109,110,111,112,113,114,115,116,117,118,119,120,121,122,123,124,125,126,127,128,129,130,138,139,' WHERE `pid` = 1 AND `types` = 2;

UPDATE `5kcrm_admin_group` SET `rules` = ',112,113,114,115,116,117,118,119,105,' WHERE `pid` = 1 AND `types` = 3;
UPDATE `5kcrm_admin_group` SET `rules` = ',124,125,105,' WHERE `pid` = 1 AND `types` = 4;
UPDATE `5kcrm_admin_group` SET `rules` = ',122,123,105,' WHERE `pid` = 1 AND `types` = 5;
UPDATE `5kcrm_admin_group` SET `rules` = ',126,127,128,129,130,105,' WHERE `pid` = 1 AND `types` = 6;
UPDATE `5kcrm_admin_group` SET `rules` = ',141,142,143,' WHERE `pid` = 1 AND `types` = 7;

INSERT INTO `5kcrm_admin_rule` (`id`, `types`, `title`, `name`, `level`, `pid`, `status`) VALUES (140, '7', '商业智能', 'bi', '1', '0', '1');

UPDATE `5kcrm_admin_rule` SET `pid` = 140 WHERE `name` = 'oa' AND `level` = 2 AND `pid` = 62;
UPDATE `5kcrm_admin_rule` SET `types` = '7' WHERE `types` = 6 AND `id` > 85;

INSERT INTO `5kcrm_admin_rule`(`id`, `types`, `title`, `name`, `level`, `pid`, `status`) VALUES
(141, 9, '全部', 'work', 1, 0, 1),
(142, 9, '项目管理', 'work', 2, 141, 1),
(143, 9, '项目创建', 'save', 3, 142, 1);

ALTER TABLE `5kcrm_task` CHANGE `name` `name` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '任务名称';

INSERT INTO `5kcrm_admin_rule` (`id`, `types`, `title`, `name`, `level`, `pid`, `status`) VALUES (144, '2', '跟进记录管理', 'record', '2', '1', '1');
INSERT INTO `5kcrm_admin_rule` (`id`, `types`, `title`, `name`, `level`, `pid`, `status`) VALUES (145, '2', '查看列表', 'index', '3', '144', '1');

INSERT INTO `5kcrm_admin_config` (`id`, `name`, `status`, `module`, `controller`, `type`, `pid`) VALUES
(1, '办公管理', 1, 'oa', '', 1, 0),
(2, '客户关系管理', 1, 'crm', '', 1, 0),
(3, '项目管理', 1, 'work', '', 1, 0),
(4, '人力资源管理', 0, 'hrm', '', 2, 0),
(5, '进销存管理', 0, 'jxc', '', 2, 0),
(6, '呼叫中心功能', 0, 'call', '', 3, 0);

UPDATE `5kcrm_admin_group` SET `rules` = ',92,98,90,', `remark` = '成员初始加入时默认享有的权限：默认只有新建任务，查看任务权限' WHERE `pid` = 5 AND `system` = 1;