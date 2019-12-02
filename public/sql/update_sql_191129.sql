
--
-- 产品删除权限
--
INSERT INTO `5kcrm_admin_rule`(`id`, `types`, `title`, `name`, `level`, `pid`, `status`) VALUES
(149, 2, '删除', 'delete', 3, 56, 1);

--
-- 产品软删除字段
--
ALTER TABLE `5kcrm_crm_product` 
ADD COLUMN `delete_user_id` int(10) NOT NULL DEFAULT 0 COMMENT '删除人',
ADD COLUMN `delete_time` int(10) COMMENT '删除时间';

--
-- 商业智能优化
--
-- 客户表 
ALTER TABLE `5kcrm_crm_customer` 
ADD INDEX `bi_analysis` (`create_time`, `owner_user_id`) USING BTREE;

-- 合同表
ALTER TABLE `5kcrm_crm_contract` 
ADD INDEX `bi_analysis` (`check_status`, `customer_id`, `order_date`) USING BTREE;

-- 商机表
ALTER TABLE `5kcrm_crm_business` 
ADD INDEX `bi_analysis` (`create_time`, `is_end`, `owner_user_id`) USING BTREE;

-- 回款表
ALTER TABLE `5kcrm_crm_receivables` 
ADD INDEX `bi_analysis` (`check_status`, `return_time`, `owner_user_id`) USING BTREE;

-- 
-- 消息提醒
-- 
ALTER TABLE `5kcrm_admin_message` 
ADD COLUMN `type` tinyint(2) NOT NULL DEFAULT 0 COMMENT '消息类型，用于前端拼接消息' AFTER `message_id`;

--
-- 导入记录
--
CREATE TABLE `5kcrm_admin_import_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(32) NOT NULL DEFAULT '' COMMENT '导入模块',
  `total` int(10) NOT NULL DEFAULT '0' COMMENT '总数',
  `done` int(10) NOT NULL DEFAULT '0' COMMENT '已导入数',
  `cover` int(10) NOT NULL DEFAULT '0' COMMENT '覆盖数',
  `error` int(10) NOT NULL DEFAULT '0' COMMENT '错误数',
  `error_data_file_path` varchar(255) NOT NULL DEFAULT '' COMMENT '错误数据文件路径',
  `create_time` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='导入数据记录表';

