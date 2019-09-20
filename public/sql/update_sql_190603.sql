INSERT INTO `5kcrm_admin_rule` VALUES ('85', '2', '推广', 'marketing', '2', '1', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('86', '2', '新建', 'save', '3', '85', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('87', '2', '查看列表', 'index', '3', '85', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('88', '2', '导出', 'excelExport', '3', '85', '1');

INSERT INTO `5kcrm_crm_config` (`id`, `name`, `value`, `description`) VALUES
(NULL, 'record_type', '[\"\\u6253\\u7535\\u8bdd\",\"\\u53d1\\u90ae\\u4ef6\",\"\\u53d1\\u77ed\\u4fe1\",\"\\u89c1\\u9762\\u62dc\\u8bbf\",\"\\u6d3b\\u52a8\"]', '跟进记录类型');

CREATE TABLE `5kcrm_crm_marketing` (
  `marketing_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '营销id',
  `name` varchar(30) NOT NULL COMMENT '营销名称',
  `object` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1客户  2线索',
  `end_time` int(10) NOT NULL COMMENT '截止时间',
  `relation_user_id` varchar(200) NOT NULL COMMENT '关联ID',
  `owner_user_id` varchar(200) NOT NULL COMMENT '管理ID',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `state` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1启用  2禁用',
  `second` tinyint(1) NOT NULL DEFAULT '1' COMMENT '每个客户只能填写次数',
  `field_data` text NOT NULL COMMENT '营销内容填写字段',
  `remark` text NOT NULL COMMENT '备注',
  `path` varchar(500) NOT NULL COMMENT '二维码路径',
  `browse` int(10) NOT NULL COMMENT '浏览数',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`marketing_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='营销表';

CREATE TABLE `5kcrm_crm_marketing_info` (
  `r_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `marketing_id` int(10) NOT NULL COMMENT '关联ID',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0未同步  1同步成功  2同步失败',
  `field_info` text NOT NULL COMMENT '营销内容填写字段内容',
  `device` varchar(50) NOT NULL COMMENT '设备号',
  `owner_user_id` int(10) NOT NULL COMMENT '关联ID',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`r_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='营销数据表';

INSERT INTO `5kcrm_crm_config` (`id`, `name`, `value`, `description`) VALUES (NULL, 'contract_config', '1', '1开启');

INSERT INTO `5kcrm_admin_rule` VALUES ('89', '2', '导出', 'poolExcelExport', '3', '29', '1');

CREATE TABLE `5kcrm_crm_contacts_business` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `contacts_id` int(10) NOT NULL,
  `business_id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;