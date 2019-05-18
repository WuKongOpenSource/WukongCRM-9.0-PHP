ALTER TABLE `5kcrm_oa_announcement` ADD `read_user_ids` TEXT NULL DEFAULT NULL COMMENT '阅读人' AFTER `owner_user_ids`;

CREATE TABLE `5kcrm_crm_top` (
  `top_id` int(10) NOT NULL AUTO_INCREMENT,
  `module_id` int(10) NOT NULL COMMENT '相关模块ID',
  `set_top` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1置顶',
  `top_time` int(10) NOT NULL COMMENT '置顶时间',
  `create_role_id` int(10) NOT NULL COMMENT '创建人ID',
  `module` varchar(50) NOT NULL DEFAULT 'business' COMMENT '置顶模块',
  PRIMARY KEY (`top_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='置顶表';