ALTER TABLE `5kcrm_admin_field` ADD `relevant` VARCHAR(50) NULL DEFAULT NULL COMMENT '相关字段名' AFTER `type`;
INSERT INTO `5kcrm_admin_rule` VALUES ('95', '6', '办公分析', 'oa', '2', '62', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('96', '6', '查看', 'read', '3', '95', '1');

ALTER TABLE `5kcrm_crm_customer` ADD INDEX( `update_time`);
ALTER TABLE `5kcrm_crm_leads` ADD INDEX( `update_time`);
ALTER TABLE `5kcrm_crm_business` ADD INDEX( `update_time`);
ALTER TABLE `5kcrm_crm_contacts` ADD INDEX( `update_time`);
ALTER TABLE `5kcrm_crm_contract` ADD INDEX( `update_time`);
ALTER TABLE `5kcrm_crm_receivables` ADD INDEX( `update_time`);