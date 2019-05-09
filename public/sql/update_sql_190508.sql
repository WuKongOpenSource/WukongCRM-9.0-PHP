ALTER TABLE `5kcrm_crm_leads` ADD `follow` VARCHAR(20) NULL DEFAULT NULL COMMENT '跟进' AFTER `next_time`;
ALTER TABLE `5kcrm_crm_customer` ADD `follow` VARCHAR(20) NULL DEFAULT NULL COMMENT '跟进' AFTER `next_time`;

INSERT INTO `5kcrm_crm_config` (`id`, `name`, `value`, `description`) VALUES (NULL, 'contract_day', '30', '合同到期提醒天数');

UPDATE `5kcrm_admin_rule` SET `status` = '0' WHERE `5kcrm_admin_rule`.`id` = 67;
UPDATE `5kcrm_admin_rule` SET `status` = '0' WHERE `5kcrm_admin_rule`.`id` = 68;
UPDATE `5kcrm_admin_rule` SET `title` = '产品分析' WHERE `5kcrm_admin_rule`.`id` = 69;
INSERT INTO `5kcrm_admin_rule` VALUES ('75', '6', '员工业绩分析', 'contract', '2', '62', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('76', '6', '查看', 'read', '3', '75', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('77', '6', '客户画像分析', 'portrait', '2', '62', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('78', '6', '查看', 'read', '3', '77', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('79', '6', '排行榜', 'ranking', '2', '62', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('80', '6', '查看', 'read', '3', '79', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('81', '2', '导入', 'excelImport', '3', '22', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('82', '2', '导出', 'excelExport', '3', '22', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('83', '2', '导入', 'excelImport', '3', '56', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('84', '2', '导出', 'excelExport', '3', '56', '1');