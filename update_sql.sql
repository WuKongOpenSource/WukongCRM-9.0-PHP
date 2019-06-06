INSERT INTO `5kcrm_crm_config` (`id`, `name`, `value`, `description`) VALUES
(NULL, 'record_type', '[\"\\u6253\\u7535\\u8bdd\",\"\\u53d1\\u90ae\\u4ef6\",\"\\u53d1\\u77ed\\u4fe1\",\"\\u89c1\\u9762\\u62dc\\u8bbf\",\"\\u6d3b\\u52a8\"]', '跟进记录类型');

INSERT INTO `5kcrm_crm_config` (`id`, `name`, `value`, `description`) VALUES (NULL, 'contract_config', '1', '1开启');

INSERT INTO `5kcrm_admin_rule` VALUES ('85', '2', '导出', 'poolExcelExport', '3', '29', '1');

CREATE TABLE `5kcrm_crm_contacts_business` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `contacts_id` int(10) NOT NULL,
  `business_id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;