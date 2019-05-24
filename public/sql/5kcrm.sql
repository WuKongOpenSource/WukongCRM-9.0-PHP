DROP TABLE IF EXISTS `5kcrm_admin_access`;
CREATE TABLE `5kcrm_admin_access` (
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `5kcrm_admin_access` VALUES ('1', '1');

DROP TABLE IF EXISTS `5kcrm_admin_action_log`;
CREATE TABLE `5kcrm_admin_action_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL COMMENT '操作人ID',
  `module_name` varchar(50) NOT NULL COMMENT '模块',
  `controller_name` varchar(50) NOT NULL COMMENT '控制器',
  `action_name` varchar(50) NOT NULL COMMENT '方法',
  `action_id` int(10) NOT NULL COMMENT '操作ID',
  `action_delete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1为删除操作',
  `content` varchar(500) NOT NULL DEFAULT '' COMMENT '操作内容',
  `create_time` int(11) NOT NULL COMMENT '操作时间',
  `join_user_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '抄送人IDs',
  `structure_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '抄送部门IDs',
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='操作记录表';

DROP TABLE IF EXISTS `5kcrm_admin_action_record`;
CREATE TABLE `5kcrm_admin_action_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL COMMENT '用户ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `types` varchar(20) NOT NULL COMMENT '类型',
  `action_id` int(11) NOT NULL COMMENT '操作ID',
  `content` text COMMENT '内容',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='字段操作记录表';

DROP TABLE IF EXISTS `5kcrm_admin_comment`;
CREATE TABLE `5kcrm_admin_comment` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '评论表',
  `user_id` int(11) NOT NULL COMMENT '评论人ID',
  `content` varchar(1000) NOT NULL COMMENT '内容(答案)',
  `reply_content` varchar(1000) NOT NULL DEFAULT '' COMMENT '回复内容（问题）',
  `create_time` int(11) NOT NULL COMMENT '新建时间',
  `isreply` tinyint(2) DEFAULT '0' COMMENT '是否是回复 1 是 0 否',
  `reply_user_id` int(11) NOT NULL DEFAULT '0',
  `reply_id` int(11) DEFAULT '0' COMMENT '回复对象ID',
  `status` tinyint(2) DEFAULT '1' COMMENT '状态 ',
  `type_id` int(11) DEFAULT '0' COMMENT '评论项目任务ID 或评论其他模块ID',
  `favour` int(7) DEFAULT '0' COMMENT '赞',
  `type` varchar(50) NOT NULL DEFAULT '' COMMENT '分类 ',
  `reply_fid` int(11) NOT NULL DEFAULT '0' COMMENT '回复最上级ID',
  PRIMARY KEY (`comment_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='任务评论表';

DROP TABLE IF EXISTS `5kcrm_admin_config`;
CREATE TABLE `5kcrm_admin_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '名字',
  `status` tinyint(2) NOT NULL COMMENT '状态',
  `module` varchar(50) NOT NULL COMMENT '模型',
  `controller` varchar(50) NOT NULL COMMENT '控制器',
  `type` tinyint(2) NOT NULL COMMENT '类型',
  `typestatus` tinyint(2) NOT NULL COMMENT '总类型状态',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `5kcrm_admin_examine_flow`;
CREATE TABLE `5kcrm_admin_examine_flow` (
  `flow_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '审批流名称',
  `config` tinyint(4) NOT NULL COMMENT '1固定审批0授权审批',
  `types` varchar(50) NOT NULL COMMENT '关联对象',
  `types_id` tinyint(4) NOT NULL DEFAULT '0' COMMENT '对象ID（如审批类型ID）',
  `structure_ids` varchar(500) NOT NULL DEFAULT '' COMMENT '部门ID（0为全部）',
  `user_ids` varchar(500) NOT NULL DEFAULT '' COMMENT '员工ID',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '流程说明',
  `update_user_id` int(11) NOT NULL COMMENT '修改人ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态 1启用，0禁用',
  `is_deleted` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态 1删除',
  `delete_time` int(11) NOT NULL DEFAULT '0' COMMENT '删除时间',
  `delete_user_id` int(11) NOT NULL  DEFAULT '0' COMMENT '删除人ID',
  PRIMARY KEY (`flow_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COMMENT='审批流程表';

INSERT INTO `5kcrm_admin_examine_flow` VALUES ('1', '普通审批流程', '0', 'oa_examine', '1', '', '', '', '1', '1548835446', '1548835446', '1', '0', '0', '0');
INSERT INTO `5kcrm_admin_examine_flow` VALUES ('2', '请假审批流程', '0', 'oa_examine', '2', '', '', '', '1', '1548835717', '1548835717', '1', '0', '0', '0');
INSERT INTO `5kcrm_admin_examine_flow` VALUES ('3', '出差审批流程', '0', 'oa_examine', '3', '', '', '', '1', '1549959653', '1549959653', '1', '0', '0', '0');
INSERT INTO `5kcrm_admin_examine_flow` VALUES ('4', '加班审批流程', '0', 'oa_examine', '4', '', '', '', '1', '1549959653', '1549959653', '1', '0', '0', '0');
INSERT INTO `5kcrm_admin_examine_flow` VALUES ('5', '差旅审批流程', '0', 'oa_examine', '5', '', '', '', '1', '1549959653', '1549959653', '1', '0', '0', '0');
INSERT INTO `5kcrm_admin_examine_flow` VALUES ('6', '借款审批流程', '0', 'oa_examine', '6', '', '', '', '1', '1549959653', '1549959653', '1', '0', '0', '0');
INSERT INTO `5kcrm_admin_examine_flow` VALUES ('7', '合同审批流程', '0', 'crm_contract', '0', '', '', '', '1', '1549959653', '1549959653', '1', '0', '0', '0');
INSERT INTO `5kcrm_admin_examine_flow` VALUES ('8', '回款审批流程', '0', 'crm_receivables', '0', '', '', '', '1', '1549959653', '1549959653', '1', '0', '0', '0');

DROP TABLE IF EXISTS `5kcrm_admin_examine_record`;
CREATE TABLE `5kcrm_admin_examine_record` (
  `record_id` int(11) NOT NULL AUTO_INCREMENT,
  `types` varchar(50) NOT NULL DEFAULT '' COMMENT '类型',
  `types_id` int(11) NOT NULL DEFAULT '0' COMMENT '类型ID',
  `flow_id` int(11) NOT NULL DEFAULT '0' COMMENT '审批流程ID',
  `order_id` int(11) NOT NULL DEFAULT '0' COMMENT '审批排序ID',
  `check_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '审批人ID',
  `check_time` int(11) NOT NULL COMMENT '审批时间',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '1审核通过0审核失败2撤销',
  `content` varchar(500) NOT NULL DEFAULT '' COMMENT '审核意见',
  `is_end` tinyint(1) NOT NULL DEFAULT '0' COMMENT '审批失效（1标记为无效）',
  PRIMARY KEY (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='审批记录表';

DROP TABLE IF EXISTS `5kcrm_admin_examine_step`;
CREATE TABLE `5kcrm_admin_examine_step` (
  `step_id` int(11) NOT NULL AUTO_INCREMENT,
  `flow_id` int(11) NOT NULL COMMENT '审批流程ID',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1负责人主管，2指定用户（任意一人），3指定用户（多人会签），4上一级审批人主管',
  `user_id` varchar(500) NOT NULL DEFAULT '' COMMENT '审批人ID (使用逗号隔开) ,1,2,',
  `order_id` tinyint(4) NOT NULL DEFAULT '1' COMMENT '排序ID',
  `relation` tinyint(1) NOT NULL DEFAULT '1' COMMENT '审批流程关系（1并2或）',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`step_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='审批步骤表';

DROP TABLE IF EXISTS `5kcrm_admin_field`;
CREATE TABLE `5kcrm_admin_field` (
  `field_id` int(11) NOT NULL AUTO_INCREMENT,
  `types` varchar(30) NOT NULL DEFAULT '' COMMENT '分类',
  `types_id` int(11) NOT NULL DEFAULT '0' COMMENT '分类ID（审批等）',
  `field` varchar(50) NOT NULL COMMENT '字段名',
  `name` varchar(50) NOT NULL COMMENT '标识名',
  `form_type` varchar(20) NOT NULL COMMENT '字段类型',
  `default_value` varchar(255) NOT NULL DEFAULT '' COMMENT '默认值',
  `max_length` int(4) NOT NULL DEFAULT '0' COMMENT ' 字数上限',
  `is_unique` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否唯一（1是，0否）',
  `is_null` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否必填（1是，0否）',
  `input_tips` varchar(100) NOT NULL DEFAULT '' COMMENT '输入提示',
  `setting` text COMMENT '设置',
  `order_id` int(4) NOT NULL DEFAULT '0' COMMENT '排序ID',
  `operating` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0改删，1改，2删，3无',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `type` int(2) NOT NULL DEFAULT '0' COMMENT '薪资管理 1固定 2增加 3减少',
  PRIMARY KEY (`field_id`)
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8 COMMENT='自定义字段表';

INSERT INTO `5kcrm_admin_field` VALUES ('1', '', '0', 'create_user_id', '创建人', 'user', '', '0', '0', '0', '', '', '99', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('2', '', '0', 'update_time', '更新时间', 'datetime', '', '0', '0', '0', '', '', '100', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('3', '', '0', 'create_time', '创建时间', 'datetime', '', '0', '0', '0', '', '', '101', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('4', '', '0', 'owner_user_id', '负责人', 'user', '', '0', '0', '0', '', '', '102', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('5', 'crm_leads', '0', 'name', '线索名称', 'text', '', '0', '1', '1', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('6', 'crm_leads', '0', 'source', '线索来源', 'select', '', '0', '0', '0', '', '促销活动\n搜索引擎\n广告\n转介绍\n线上注册\n线上询价\n预约上门\n陌拜\n招商资源\n公司资源\n展会资源\n个人资源\n电话咨询\n邮件咨询', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('7', 'crm_leads', '0', 'telephone', '电话', 'text', '', '0', '0', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('8', 'crm_leads', '0', 'mobile', '手机', 'mobile', '', '0', '1', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('9', 'crm_leads', '0', 'industry', '客户行业', 'select', '', '0', '0', '0', '', 'IT/通信/电子/互联网\n金融业\n房地产\n商业服务\n贸易\n生产\n运输/物流\n服务业\n文化传媒\n政府\n其他', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('10', 'crm_leads', '0', 'level', '客户级别', 'select', '', '0', '0', '0', '', 'A（重点客户）\nB（普通客户）\nC（非优先客户）', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('11', 'crm_leads', '0', 'detail_address', '地址', 'text', '', '0', '0', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('12', 'crm_leads', '0', 'next_time', '下次联系时间', 'datetime', '', '0', '0', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('13', 'crm_leads', '0', 'remark', '备注', 'textarea', '', '0', '0', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('14', 'crm_customer', '0', 'name', '客户名称', 'text', '', '0', '1', '1', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('15', 'crm_customer', '0', 'level', '客户级别', 'select', '', '0', '0', '0', '', 'A（重点客户）\nB（普通客户）\nC（非优先客户）', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('16', 'crm_customer', '0', 'industry', '客户行业', 'select', '', '0', '0', '0', '', 'IT/通信/电子/互联网\n金融业\n房地产\n商业服务\n贸易\n生产\n运输/物流\n服务业\n文化传媒\n政府\n其他', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('17', 'crm_customer', '0', 'source', '客户来源', 'select', '', '0', '0', '0', '', '促销活动\n搜索引擎\n广告\n转介绍\n线上注册\n线上询价\n预约上门\n陌拜\n招商资源\n公司资源\n展会资源\n个人资源\n电话咨询\n邮件咨询', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('18', 'crm_customer', '0', 'deal_status', '成交状态', 'select', '未成交', '0', '0', '1', '', '未成交\n已成交', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('19', 'crm_customer', '0', 'telephone', '电话', 'text', '', '0', '0', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('20', 'crm_customer', '0', 'website', '网址', 'text', '', '0', '0', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('21', 'crm_customer', '0', 'next_time', '下次联系时间', 'datetime', '', '0', '0', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('22', 'crm_customer', '0', 'remark', '备注', 'textarea', '', '0', '0', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('23', 'crm_contacts', '0', 'name', '姓名', 'text', '', '0', '0', '1', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('24', 'crm_contacts', '0', 'customer_id', '客户名称', 'customer', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('25', 'crm_contacts', '0', 'mobile', '手机', 'mobile', '', '0', '0', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('26', 'crm_contacts', '0', 'telephone', '电话', 'text', '', '0', '0', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('27', 'crm_contacts', '0', 'email', '电子邮箱', 'email', '', '0', '0', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('28', 'crm_contacts', '0', 'decision', '是否关键决策人', 'select', '', '0', '0', '0', '', '是\n否', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('29', 'crm_contacts', '0', 'post', '职务', 'text', '', '0', '0', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('30', 'crm_contacts', '0', 'sex', '性别', 'select', '', '0', '0', '0', '', '男\n女', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('31', 'crm_contacts', '0', 'detail_address', '地址', 'text', '', '0', '0', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('32', 'crm_contacts', '0', 'next_time', '下次联系时间', 'datetime', '', '0', '0', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('33', 'crm_contacts', '0', 'remark', '备注', 'textarea', '', '0', '0', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('34', 'crm_business', '0', 'name', '商机名称', 'text', '', '0', '0', '1', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('35', 'crm_business', '0', 'customer_id', '客户名称', 'customer', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('36', 'crm_business', '0', 'type_id', '商机状态组', 'business_type', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('37', 'crm_business', '0', 'status_id', '商机阶段', 'business_status', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('38', 'crm_business', '0', 'money', '商机金额', 'floatnumber', '', '0', '0', '0', '元', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('39', 'crm_business', '0', 'deal_date', '预计成交日期', 'date', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('40', 'crm_business', '0', 'remark', '备注', 'textarea', '', '0', '0', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('41', 'crm_contract', '0', 'num', '合同编号', 'text', '', '0', '1', '1', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('42', 'crm_contract', '0', 'name', '合同名称', 'text', '', '0', '0', '1', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('43', 'crm_contract', '0', 'customer_id', '客户名称', 'customer', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('44', 'crm_contract', '0', 'business_id', '商机名称', 'business', '', '0', '0', '0', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('45', 'crm_contract', '0', 'order_date', '下单时间', 'date', '', '0', '0', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('46', 'crm_contract', '0', 'money', '合同金额', 'floatnumber', '', '0', '0', '1', '元', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('47', 'crm_contract', '0', 'start_time', '合同开始时间', 'date', '', '0', '0', '0', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('48', 'crm_contract', '0', 'end_time', '合同到期时间', 'date', '', '0', '0', '0', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('49', 'crm_contract', '0', 'contacts_id', '客户签约人', 'contacts', '', '0', '0', '0', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('50', 'crm_contract', '0', 'order_user_id', '公司签约人', 'user', '', '0', '0', '0', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('51', 'crm_contract', '0', 'remark', '备注', 'textarea', '', '0', '0', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('52', 'crm_receivables', '0', 'number', '回款编号', 'text', '', '0', '1', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('53', 'crm_receivables', '0', 'customer_id', '客户名称', 'customer', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('54', 'crm_receivables', '0', 'contract_id', '合同编号', 'contract', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('55', 'crm_receivables', '0', 'return_time', '回款日期', 'date', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('56', 'crm_receivables', '0', 'return_type', '回款方式', 'select', '', '0', '0', '1', '', '支票\n现金\n邮政汇款\n电汇\n网上转账\n支付宝\n微信支付\n其他', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('57', 'crm_receivables', '0', 'money', '回款金额', 'floatnumber', '', '0', '0', '1', '元', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('58', 'crm_receivables', '0', 'plan_id', '期数', 'receivables_plan', '', '0', '0', '0', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('59', 'crm_receivables', '0', 'remark', '备注', 'textarea', '', '0', '0', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('60', 'crm_product', '0', 'name', '产品名称', 'text', '', '0', '0', '1', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('61', 'crm_product', '0', 'category_id', '产品类别', 'category', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('62', 'crm_product', '0', 'num', '产品编码', 'text', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('63', 'crm_product', '0', 'status', '是否上架', 'select', '上架', '0', '0', '1', '', '上架\n下架', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('64', 'crm_product', '0', 'unit', '单位', 'select', '', '0', '0', '1', '', '个\n块\n只\n把\n枚\n瓶\n盒\n台\n吨\n千克\n米\n箱', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('65', 'crm_product', '0', 'price', '标准价格', 'floatnumber', '', '0', '0', '1', '元', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('66', 'crm_product', '0', 'description', '产品描述', 'text', '', '0', '0', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('67', 'oa_examine', '1', 'content', '审批内容', 'text', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('68', 'oa_examine', '1', 'remark', '备注', 'textarea', '', '0', '0', '0', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('69', 'oa_examine', '2', 'type_id', '请假类型', 'select', '', '0', '0', '1', '', '年假\n事假\n病假\n产假\n调休\n婚假\n丧假\n其他', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('70', 'oa_examine', '2', 'content', '审批内容', 'text', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('71', 'oa_examine', '2', 'start_time', '开始时间', 'datetime', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('72', 'oa_examine', '2', 'end_time', '结束时间', 'datetime', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('73', 'oa_examine', '2', 'duration', '时长(天)', 'floatnumber', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('74', 'oa_examine', '2', 'remark', '备注', 'textarea', '', '0', '0', '0', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('75', 'oa_examine', '3', 'content', '出差事由', 'text', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('76', 'oa_examine', '3', 'remark', '备注', 'textarea', '', '0', '0', '0', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('77', 'oa_examine', '3', 'cause', '行程明细', 'business_cause', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('78', 'oa_examine', '3', 'duration', '出差总天数', 'floatnumber', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('79', 'oa_examine', '4', 'content', '加班原因', 'text', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('80', 'oa_examine', '4', 'start_time', '开始时间', 'datetime', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('81', 'oa_examine', '4', 'end_time', '结束时间', 'datetime', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('82', 'oa_examine', '4', 'duration', '加班总天数', 'floatnumber', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('83', 'oa_examine', '4', 'remark', '备注', 'textarea', '', '0', '0', '0', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('84', 'oa_examine', '5', 'content', '差旅事由', 'text', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('85', 'oa_examine', '5', 'cause', '费用明细', 'examine_cause', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('86', 'oa_examine', '5', 'money', '报销总金额', 'floatnumber', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('87', 'oa_examine', '5', 'remark', '备注', 'textarea', '', '0', '0', '0', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('88', 'oa_examine', '6', 'content', '借款事由', 'text', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('89', 'oa_examine', '6', 'money', '借款金额（元）', 'floatnumber', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('90', 'oa_examine', '6', 'remark', '备注', 'textarea', '', '0', '0', '0', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('91', 'crm_receivables_plan', '0', 'customer_id', '客户名称', 'customer', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('92', 'crm_receivables_plan', '0', 'contract_id', '合同编号', 'contract', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('93', 'crm_receivables_plan', '0', 'money', '计划回款金额', 'floatnumber', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('94', 'crm_receivables_plan', '0', 'return_date', '计划回款日期', 'date', '', '0', '0', '1', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('95', 'crm_receivables_plan', '0', 'return_type', '计划回款方式', 'select', '', '0', '0', '1', '', '支票\n现金\n邮政汇款\n电汇\n网上转账\n支付宝\n微信支付\n其他\n在线支付\n线下支付\n预存款\n返利\n预存款+返利', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('96', 'crm_receivables_plan', '0', 'remind', '提前几日提醒', 'number', '', '0', '0', '0', '', '', '0', '3', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('97', 'crm_receivables_plan', '0', 'remark', '备注', 'textarea', '', '0', '0', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('98', 'crm_receivables_plan', '0', 'file', '附件', 'file', '', '0', '0', '0', '', '', '0', '1', '1553788800', '1553788800', '0');
INSERT INTO `5kcrm_admin_field` VALUES ('99', 'crm_customer', '0', 'mobile', '手机', 'mobile', '', '0', '1', '0', '', '', '7', '1', '1553788800', '1553788800', '0');

DROP TABLE IF EXISTS `5kcrm_admin_file`;
CREATE TABLE `5kcrm_admin_file` (
  `file_id` int(11) NOT NULL AUTO_INCREMENT,
  `types` varchar(20) NOT NULL COMMENT '类型（file、img）',
  `name` varchar(100) NOT NULL COMMENT '附件名称',
  `save_name` varchar(500) NOT NULL COMMENT '保存路径名称',
  `size` int(10) NOT NULL COMMENT '附件大小（字节）',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `file_path` varchar(500) NOT NULL COMMENT '文件路径',
  `file_path_thumb` varchar(500) NOT NULL DEFAULT '' COMMENT '文件路径(图片缩略图)',
  PRIMARY KEY (`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='附件表';

DROP TABLE IF EXISTS `5kcrm_admin_group`;
CREATE TABLE `5kcrm_admin_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` tinyint(4) NOT NULL COMMENT '分类1管理角色2客户管理角色3人事角色4财务角色5项目角色0自定义角色',
  `title` varchar(100) NOT NULL COMMENT '名称',
  `rules` varchar(2000) NOT NULL DEFAULT '' COMMENT '规则',
  `remark` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  `status` tinyint(3) DEFAULT '1' COMMENT '1启用0禁用',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1本人，2本人及下属，3本部门，4本部门及下属部门，5全部 ',
  `types` tinyint(4) NOT NULL DEFAULT '0' COMMENT '1超级管理员2系统设置管理员3部门与员工管理员4审批流管理员5工作台管理员6客户管理员7项目管理员8公告管理员',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COMMENT='角色表';

INSERT INTO `5kcrm_admin_group` VALUES ('1', '1', '超级管理员角色', '', '超级管理员角色', '1', '1', '1');
INSERT INTO `5kcrm_admin_group` VALUES ('2', '1', '系统设置管理员', '', '系统设置管理员', '1', '1', '2');
INSERT INTO `5kcrm_admin_group` VALUES ('3', '1', '部门与员工管理员', '', '部门与员工管理员', '1', '1', '3');
INSERT INTO `5kcrm_admin_group` VALUES ('4', '1', '审批流管理员', '', '审批流管理员', '1', '1', '4');
INSERT INTO `5kcrm_admin_group` VALUES ('5', '1', '工作台管理员', '', '工作台管理员', '1', '1', '5');
INSERT INTO `5kcrm_admin_group` VALUES ('6', '1', '客户管理员', '', '客户管理员', '1', '1', '6');
INSERT INTO `5kcrm_admin_group` VALUES ('7', '1', '公告管理员', '', '公告管理员', '1', '1', '8');
INSERT INTO `5kcrm_admin_group` VALUES ('8', '2', '销售员角色', ',3,4,5,6,7,11,12,13,14,15,18,19,21,23,24,25,26,28,30,31,33,35,36,37,38,40,41,43,44,45,46,48,49,50,51,52,53,54,55,59,60,1,2,10,22,29,34,42,56,', '', 1, 2, 0);
INSERT INTO `5kcrm_admin_group` VALUES ('9', '4', '财务角色', ',43,44,45,46,48,51,52,53,54,1,42,50,67,68,62,', '', 1, 5, 0);
INSERT INTO `5kcrm_admin_group` VALUES ('10', '2', '销售经理角色', ',1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,', '', 1, 2, 0);

DROP TABLE IF EXISTS `5kcrm_admin_menu`;
CREATE TABLE `5kcrm_admin_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '菜单ID',
  `pid` int(11) DEFAULT '0' COMMENT '上级菜单ID',
  `title` varchar(32) NOT NULL DEFAULT '' COMMENT '菜单名称',
  `url` varchar(127) NOT NULL DEFAULT '' COMMENT '链接地址',
  `icon` varchar(64) NOT NULL DEFAULT '' COMMENT '图标',
  `menu_type` tinyint(4) NOT NULL COMMENT '菜单类型',
  `sort` tinyint(4) DEFAULT '0' COMMENT '排序（同级有效）',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态',
  `rule_id` int(11) NOT NULL COMMENT '权限id',
  `module` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COMMENT='后台菜单表';

INSERT INTO `5kcrm_admin_menu` VALUES ('1', '0', 'CRM模块', '', '', '0', '0', '1', '1', 'crm');
INSERT INTO `5kcrm_admin_menu` VALUES ('2', '1', '线索', '', '', '0', '0', '1', '2', 'leads');
INSERT INTO `5kcrm_admin_menu` VALUES ('3', '1', '客户', '', '', '0', '0', '1', '10', 'customer');
INSERT INTO `5kcrm_admin_menu` VALUES ('4', '1', '联系人', '', '', '0', '0', '1', '22', 'contacts');
INSERT INTO `5kcrm_admin_menu` VALUES ('5', '1', '公海', '', '', '0', '0', '1', '29', 'pool');
INSERT INTO `5kcrm_admin_menu` VALUES ('6', '1', '商机', '', '', '0', '0', '1', '34', 'business');
INSERT INTO `5kcrm_admin_menu` VALUES ('7', '1', '合同', '', '', '0', '0', '1', '42', 'contract');
INSERT INTO `5kcrm_admin_menu` VALUES ('8', '1', '回款', '', '', '0', '0', '1', '50', 'receivables');
INSERT INTO `5kcrm_admin_menu` VALUES ('9', '1', '产品', '', '', '0', '0', '1', '56', 'product');

DROP TABLE IF EXISTS `5kcrm_admin_message`;
CREATE TABLE `5kcrm_admin_message` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `to_user_id` int(10) NOT NULL COMMENT '接收人ID',
  `from_user_id` int(10) NOT NULL COMMENT '发送人ID',
  `content` varchar(500) NOT NULL COMMENT '发送内容',
  `send_time` int(11) NOT NULL COMMENT '发送时间',
  `read_time` int(11) NOT NULL COMMENT '阅读时间',
  `module_name` varchar(30) NOT NULL COMMENT '模块',
  `controller_name` varchar(30) NOT NULL COMMENT '控制器',
  `action_name` varchar(30) NOT NULL COMMENT '方法',
  `action_id` int(11) NOT NULL COMMENT '操作ID',
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='站内信';

DROP TABLE IF EXISTS `5kcrm_admin_record`;
CREATE TABLE `5kcrm_admin_record` (
  `record_id` int(11) NOT NULL AUTO_INCREMENT,
  `types` varchar(50) NOT NULL COMMENT '关联类型',
  `types_id` int(11) NOT NULL COMMENT '类型ID',
  `content` varchar(1000) NOT NULL COMMENT '跟进内容',
  `category` varchar(30) NOT NULL DEFAULT '' COMMENT '跟进类型',
  `next_time` int(11) NOT NULL DEFAULT '0' COMMENT '下次联系时间',
  `business_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '商机ID',
  `contacts_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '联系人ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `create_user_id` int(11) NOT NULL COMMENT '创建人ID',
  UNIQUE KEY `record_id` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='跟进记录';

DROP TABLE IF EXISTS `5kcrm_admin_record_file`;
CREATE TABLE `5kcrm_admin_record_file` (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `record_id` int(11) NOT NULL COMMENT '日志ID',
  `file_id` int(11) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='跟进记录附件关系表';

DROP TABLE IF EXISTS `5kcrm_admin_rule`;
CREATE TABLE `5kcrm_admin_rule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `types` tinyint(2) NOT NULL DEFAULT '0' COMMENT '0系统设置1工作台2客户管理3项目管理4人力资源5财务管理6商业智能',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '名称',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '定义',
  `level` tinyint(5) NOT NULL DEFAULT '0' COMMENT '级别。1模块,2控制器,3操作',
  `pid` int(11) DEFAULT '0' COMMENT '父id，默认0',
  `status` tinyint(3) DEFAULT '1' COMMENT '状态，1启用，0禁用',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8 COMMENT='权限规则表';

INSERT INTO `5kcrm_admin_rule` VALUES ('1', '2', '全部', 'crm', '1', '0', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('2', '2', '线索管理', 'leads', '2', '1', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('3', '2', '新建', 'save', '3', '2', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('4', '2', '编辑', 'update', '3', '2', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('5', '2', '查看列表', 'index', '3', '2', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('6', '2', '查看详情', 'read', '3', '2', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('7', '2', '导入', 'excelImport', '3', '2', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('8', '2', '导出', 'excelExport', '3', '2', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('9', '2', '刪除', 'delete', '3', '2', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('10', '2', '客户管理', 'customer', '2', '1', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('11', '2', '新建', 'save', '3', '10', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('12', '2', '编辑', 'update', '3', '10', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('13', '2', '查看列表', 'index', '3', '10', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('14', '2', '查看详情', 'read', '3', '10', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('15', '2', '导入', 'excelImport', '3', '10', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('16', '2', '导出', 'excelExport', '3', '10', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('17', '2', '刪除', 'delete', '3', '10', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('18', '2', '转移', 'transfer', '3', '10', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('19', '2', '放入公海', 'putInPool', '3', '10', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('20', '2', '锁定/解锁', 'lock', '3', '10', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('21', '2', '编辑团队成员', 'teamSave', '3', '10', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('22', '2', '联系人管理', 'contacts', '2', '1', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('23', '2', '新建', 'save', '3', '22', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('24', '2', '编辑', 'update', '3', '22', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('25', '2', '查看列表', 'index', '3', '22', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('26', '2', '查看详情', 'read', '3', '22', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('27', '2', '刪除', 'delete', '3', '22', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('28', '2', '转移', 'transfer', '3', '22', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('29', '2', '公海管理', 'customer', '2', '1', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('30', '2', '查看列表', 'pool', '3', '29', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('31', '2', '查看详情', 'read', '3', '29', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('32', '2', '分配', 'distribute', '3', '29', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('33', '2', '领取', 'receive', '3', '29', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('34', '2', '商机管理', 'business', '2', '1', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('35', '2', '新建', 'save', '3', '34', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('36', '2', '编辑', 'update', '3', '34', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('37', '2', '查看列表', 'index', '3', '34', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('38', '2', '查看详情', 'read', '3', '34', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('39', '2', '刪除', 'delete', '3', '34', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('40', '2', '转移', 'transfer', '3', '34', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('41', '2', '编辑团队成员', 'teamSave', '3', '34', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('42', '2', '合同管理', 'contract', '2', '1', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('43', '2', '新建', 'save', '3', '42', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('44', '2', '编辑', 'update', '3', '42', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('45', '2', '查看列表', 'index', '3', '42', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('46', '2', '查看详情', 'read', '3', '42', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('47', '2', '刪除', 'delete', '3', '42', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('48', '2', '转移', 'transfer', '3', '42', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('49', '2', '编辑团队成员', 'teamSave', '3', '42', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('50', '2', '回款管理', 'receivables', '2', '1', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('51', '2', '新建', 'save', '3', '50', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('52', '2', '编辑', 'update', '3', '50', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('53', '2', '查看列表', 'index', '3', '50', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('54', '2', '查看详情', 'read', '3', '50', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('55', '2', '刪除', 'delete', '3', '50', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('56', '2', '产品管理', 'product', '2', '1', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('57', '2', '新建', 'save', '3', '56', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('58', '2', '编辑', 'update', '3', '56', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('59', '2', '查看列表', 'index', '3', '56', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('60', '2', '查看详情', 'read', '3', '56', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('61', '2', '上架/下架', 'status', '3', '56', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('62', '6', '商业智能', 'bi', '1', '0', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('63', '6', '员工客户分析', 'customer', '2', '62', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('64', '6', '查看', 'read', '3', '63', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('65', '6', '销售漏斗分析', 'business', '2', '62', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('66', '6', '查看', 'read', '3', '65', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('67', '6', '回款统计', 'receivables', '2', '62', '0');
INSERT INTO `5kcrm_admin_rule` VALUES ('68', '6', '查看', 'read', '3', '67', '0');
INSERT INTO `5kcrm_admin_rule` VALUES ('69', '6', '产品分析', 'product', '2', '62', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('70', '6', '查看', 'read', '3', '69', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('71', '6', '业绩目标完成情况', 'achievement', '2', '62', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('72', '6', '查看', 'read', '3', '71', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('73', '2', '转移', 'transfer', '3', '2', '1');
INSERT INTO `5kcrm_admin_rule` VALUES ('74', '2', '转化', 'transform', '3', '2', '1');

DROP TABLE IF EXISTS `5kcrm_admin_scene`;
CREATE TABLE `5kcrm_admin_scene` (
  `scene_id` int(10) NOT NULL AUTO_INCREMENT,
  `types` varchar(50) NOT NULL COMMENT '分类',
  `name` varchar(50) NOT NULL COMMENT '场景名称',
  `user_id` int(10) NOT NULL COMMENT '用户ID',
  `order_id` int(10) NOT NULL DEFAULT '1' COMMENT '排序ID',
  `data` text COMMENT '属性值',
  `is_hide` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1隐藏',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1系统0自定义',
  `bydata` varchar(50) NOT NULL DEFAULT '' COMMENT '系统参数',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`scene_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8 COMMENT='场景';

INSERT INTO `5kcrm_admin_scene` VALUES ('1', 'crm_customer', '我负责的客户', '0', '0', '', '0', '1', 'me', '1546272000', '1546272000');
INSERT INTO `5kcrm_admin_scene` VALUES ('2', 'crm_customer', '我参与的客户', '0', '0', '', '0', '1', 'mePart', '1546272000', '1546272000');
INSERT INTO `5kcrm_admin_scene` VALUES ('3', 'crm_customer', '下属负责的客户', '0', '0', '', '0', '1', 'sub', '1546272000', '1546272000');
INSERT INTO `5kcrm_admin_scene` VALUES ('4', 'crm_customer', '全部客户', '0', '0', '', '0', '1', 'all', '1546272000', '1546272000');
INSERT INTO `5kcrm_admin_scene` VALUES ('5', 'crm_leads', '我负责的线索', '0', '0', '', '0', '1', 'me', '1546272000', '1546272000');
INSERT INTO `5kcrm_admin_scene` VALUES ('6', 'crm_leads', '下属的线索', '0', '0', '', '0', '1', 'sub', '1546272000', '1546272000');
INSERT INTO `5kcrm_admin_scene` VALUES ('7', 'crm_leads', '全部线索', '0', '0', '', '0', '1', 'all', '1546272000', '1546272000');
INSERT INTO `5kcrm_admin_scene` VALUES ('8', 'crm_contacts', '我负责的联系人', '0', '0', '', '0', '1', 'me', '1546272000', '1546272000');
INSERT INTO `5kcrm_admin_scene` VALUES ('9', 'crm_contacts', '下属负责的联系人', '0', '0', '', '0', '1', 'sub', '1546272000', '1546272000');
INSERT INTO `5kcrm_admin_scene` VALUES ('10', 'crm_contacts', '全部联系人', '0', '0', '', '0', '1', 'all', '1546272000', '1546272000');
INSERT INTO `5kcrm_admin_scene` VALUES ('11', 'crm_business', '我负责的商机', '0', '0', '', '0', '1', 'me', '1546272000', '1546272000');
INSERT INTO `5kcrm_admin_scene` VALUES ('12', 'crm_business', '我参与的商机', '0', '0', '', '0', '1', 'mePart', '1546272000', '1546272000');
INSERT INTO `5kcrm_admin_scene` VALUES ('13', 'crm_business', '下属负责的商机', '0', '0', '', '0', '1', 'sub', '1546272000', '1546272000');
INSERT INTO `5kcrm_admin_scene` VALUES ('14', 'crm_business', '全部商机', '0', '0', '', '0', '1', 'all', '1546272000', '1546272000');
INSERT INTO `5kcrm_admin_scene` VALUES ('15', 'crm_contract', '我负责的合同', '0', '0', '', '0', '1', 'me', '1546272000', '1546272000');
INSERT INTO `5kcrm_admin_scene` VALUES ('16', 'crm_contract', '我参与的合同', '0', '0', '', '0', '1', 'mePart', '1546272000', '1546272000');
INSERT INTO `5kcrm_admin_scene` VALUES ('17', 'crm_contract', '下属负责的合同', '0', '0', '', '0', '1', 'sub', '1546272000', '1546272000');
INSERT INTO `5kcrm_admin_scene` VALUES ('18', 'crm_contract', '全部合同', '0', '0', '', '0', '1', 'all', '1546272000', '1546272000');
INSERT INTO `5kcrm_admin_scene` VALUES ('19', 'crm_receivables', '我负责的回款', '0', '0', '', '0', '1', 'me', '1546272000', '1551515457');
INSERT INTO `5kcrm_admin_scene` VALUES ('20', 'crm_receivables', '下属负责的回款', '0', '1', '', '0', '1', 'sub', '1546272000', '1551515457');
INSERT INTO `5kcrm_admin_scene` VALUES ('21', 'crm_receivables', '全部回款', '0', '2', '', '0', '1', 'all', '1546272000', '1551515457');
INSERT INTO `5kcrm_admin_scene` VALUES ('22', 'crm_product', '全部产品', '0', '0', '', '0', '1', 'all', '1546272000', '1546272000');
INSERT INTO `5kcrm_admin_scene` VALUES ('23', 'crm_leads', '已转化线索', '0', '0', '', '0', '1', 'is_transform', '1546272000', '1546272000');

DROP TABLE IF EXISTS `5kcrm_admin_scene_default`;
CREATE TABLE `5kcrm_admin_scene_default` (
  `default_id` int(11) NOT NULL AUTO_INCREMENT,
  `types` varchar(50) NOT NULL COMMENT '类型',
  `user_id` int(11) NOT NULL COMMENT '人员ID',
  `scene_id` int(11) NOT NULL COMMENT '场景ID',
  UNIQUE KEY `default_id` (`default_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='场景默认关系表';

DROP TABLE IF EXISTS `5kcrm_admin_structure`;
CREATE TABLE `5kcrm_admin_structure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL DEFAULT '',
  `pid` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='部门表';

INSERT INTO `5kcrm_admin_structure` VALUES ('1', '办公室', '0');

DROP TABLE IF EXISTS `5kcrm_admin_system`;
CREATE TABLE `5kcrm_admin_system` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `value` varchar(255) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

INSERT INTO `5kcrm_admin_system` VALUES ('1', 'name', '悟空软件', '网站名称');
INSERT INTO `5kcrm_admin_system` VALUES ('2', 'logo', '', '企业logo');

DROP TABLE IF EXISTS `5kcrm_admin_user`;
CREATE TABLE `5kcrm_admin_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `username` varchar(100) NOT NULL COMMENT '管理后台账号',
  `password` varchar(100) NOT NULL COMMENT '管理后台密码',
  `salt` varchar(4) NOT NULL COMMENT '安全符',
  `img` varchar(200) NOT NULL DEFAULT '' COMMENT '头像',
  `thumb_img` varchar(200) NOT NULL DEFAULT '' COMMENT '头像缩略图',
  `create_time` int(11) NOT NULL,
  `realname` varchar(100) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `num` varchar(100) NOT NULL DEFAULT '' COMMENT '员工编号',
  `email` varchar(50) NOT NULL DEFAULT '' COMMENT '邮箱',
  `mobile` varchar(50) NOT NULL DEFAULT '' COMMENT '手机号码',
  `sex` varchar(5) NOT NULL DEFAULT '' COMMENT '男、女',
  `structure_id` int(11) NOT NULL DEFAULT '0' COMMENT '部门',
  `post` varchar(50) NOT NULL DEFAULT '' COMMENT '岗位',
  `status` tinyint(3) NOT NULL DEFAULT '2' COMMENT '状态,0禁用,1启用,2未激活',
  `parent_id` int(10) NOT NULL DEFAULT '0' COMMENT '直属上级ID',
  `authkey` varchar(32) NOT NULL DEFAULT '' COMMENT '验证信息',
  `authkey_time` int(11) NOT NULL DEFAULT '0' COMMENT '验证失效时间',
  `type` tinyint(2) NOT NULL COMMENT '1系统用户 0非系统用户',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户表';

DROP TABLE IF EXISTS `5kcrm_admin_user_field`;
CREATE TABLE `5kcrm_admin_user_field` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `types` varchar(30) NOT NULL COMMENT '分类',
  `datas` text COMMENT '属性值',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='自定义字段展示排序关系表';

DROP TABLE IF EXISTS `5kcrm_crm_achievement`;
CREATE TABLE `5kcrm_crm_achievement` (
  `achievement_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '名字',
  `obj_id` int(11) NOT NULL DEFAULT '0' COMMENT '对象ID',
  `type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '1公司2部门3员工',
  `year` int(8) NOT NULL COMMENT '年',
  `january` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '一月',
  `february` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '二月',
  `march` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '三月',
  `april` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '四月',
  `may` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '五月',
  `june` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '六月',
  `july` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '七月',
  `august` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '八月',
  `september` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '九月',
  `october` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '十月',
  `november` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '十一月',
  `december` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '十二月',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '1销售（目标）2回款（目标）',
  `yeartarget` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '年目标',
  PRIMARY KEY (`achievement_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `5kcrm_crm_business`;
CREATE TABLE `5kcrm_crm_business` (
  `business_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL COMMENT '客户ID',
  `type_id` int(11) NOT NULL COMMENT '商机状态组',
  `status_id` int(11) NOT NULL COMMENT '销售阶段',
  `status_time` int(11) NOT NULL DEFAULT '0' COMMENT '阶段推进时间',
  `is_end` tinyint(4) NOT NULL DEFAULT '0' COMMENT '1赢单2输单3无效',
  `next_time` int(11) NOT NULL DEFAULT '0' COMMENT '下次联系时间',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '商机名称',
  `money` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '商机金额',
  `total_price` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '产品总金额',
  `deal_date` date DEFAULT NULL COMMENT '预计成交日期',
  `discount_rate` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '整单折扣',
  `remark` text COMMENT '备注',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `owner_user_id` int(10) NOT NULL COMMENT '负责人ID',
  `ro_user_id` varchar(500) NOT NULL DEFAULT '' COMMENT '只读权限',
  `rw_user_id` varchar(500) NOT NULL DEFAULT '' COMMENT '读写权限',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商机表';

DROP TABLE IF EXISTS `5kcrm_crm_business_file`;
CREATE TABLE `5kcrm_crm_business_file` (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL COMMENT '商机ID',
  `file_id` int(11) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商机附件关系表';

DROP TABLE IF EXISTS `5kcrm_crm_business_log`;
CREATE TABLE `5kcrm_crm_business_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL COMMENT '商机id',
  `status_id` int(11) NOT NULL COMMENT '状态id',
  `is_end` tinyint(4) NOT NULL COMMENT '1赢单2输单3无效',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `owner_user_id` int(11) NOT NULL COMMENT '负责人',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='商机推进日志';

DROP TABLE IF EXISTS `5kcrm_crm_business_product`;
CREATE TABLE `5kcrm_crm_business_product` (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL COMMENT '商机ID',
  `product_id` int(11) NOT NULL COMMENT '产品ID',
  `price` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '产品单价',
  `sales_price` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '销售价格',
  `num` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '数量',
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '折扣',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '小计（折扣后价格）',
  `unit` varchar(50) NOT NULL DEFAULT '' COMMENT '单位',
  PRIMARY KEY (`r_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商机产品关系表';

DROP TABLE IF EXISTS `5kcrm_crm_business_status`;
CREATE TABLE `5kcrm_crm_business_status` (
  `status_id` int(11) NOT NULL AUTO_INCREMENT,
  `type_id` int(11) NOT NULL COMMENT '商机状态类别ID',
  `name` varchar(50) NOT NULL COMMENT '标识',
  `rate` varchar(20) NOT NULL COMMENT '赢单率',
  `order_id` tinyint(4) NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`status_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COMMENT='商机状态';

INSERT INTO `5kcrm_crm_business_status` VALUES ('1', '0', '赢单', '100', '99');
INSERT INTO `5kcrm_crm_business_status` VALUES ('2', '0', '输单', '0', '100');
INSERT INTO `5kcrm_crm_business_status` VALUES ('3', '0', '无效', '0', '101');
INSERT INTO `5kcrm_crm_business_status` VALUES ('4', '1', '验证客户', '20', '1');
INSERT INTO `5kcrm_crm_business_status` VALUES ('5', '1', '需求分析', '15', '2');
INSERT INTO `5kcrm_crm_business_status` VALUES ('6', '1', '方案/报价', '30', '3');
INSERT INTO `5kcrm_crm_business_status` VALUES ('7', '1', '谈判审核', '30', '4');

DROP TABLE IF EXISTS `5kcrm_crm_business_type`;
CREATE TABLE `5kcrm_crm_business_type` (
  `type_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '标识',
  `structure_id` varchar(255) NOT NULL DEFAULT '' COMMENT '部门ID',
  `create_user_id` int(11) NOT NULL COMMENT '创建人ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1启用0禁用',
  PRIMARY KEY (`type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='商机状态组类别';

INSERT INTO `5kcrm_crm_business_type` VALUES ('1', '系统默认', '', '1', '1540973371', '1540973371', '1');

DROP TABLE IF EXISTS `5kcrm_crm_config`;
CREATE TABLE `5kcrm_crm_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '标识',
  `value` varchar(255) NOT NULL COMMENT '值',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '描述',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='CRM管理相关配置';

INSERT INTO `5kcrm_crm_config` VALUES ('1', 'follow_day', '7', '距跟进天数');
INSERT INTO `5kcrm_crm_config` VALUES ('2', 'deal_day', '30', '距成交天数');
INSERT INTO `5kcrm_crm_config` VALUES ('3', 'config', '0', '1启用规则');

DROP TABLE IF EXISTS `5kcrm_crm_contacts`;
CREATE TABLE `5kcrm_crm_contacts` (
  `contacts_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL COMMENT '客户ID',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '姓名',
  `mobile` varchar(50) NOT NULL DEFAULT '' COMMENT '手机',
  `telephone` varchar(50) NOT NULL DEFAULT '' COMMENT '电话',
  `email` varchar(255) NOT NULL DEFAULT '' COMMENT '电子邮箱',
  `decision` varchar(20) NOT NULL DEFAULT '' COMMENT '是否关键决策人',
  `post` varchar(255) NOT NULL DEFAULT '' COMMENT '职务',
  `sex` varchar(50) NOT NULL DEFAULT '' COMMENT '性别',
  `detail_address` varchar(255) NOT NULL DEFAULT '' COMMENT '地址',
  `remark` text COMMENT '备注',
  `ro_user_id` varchar(500) NOT NULL DEFAULT '' COMMENT '只读权限',
  `rw_user_id` varchar(500) NOT NULL DEFAULT '' COMMENT '读写权限',
  `create_user_id` int(11) NOT NULL COMMENT '创建人ID',
  `owner_user_id` int(11) NOT NULL COMMENT '负责人ID',
  `next_time` int(11) NOT NULL DEFAULT '0' COMMENT '下次联系时间',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`contacts_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='联系人表';

DROP TABLE IF EXISTS `5kcrm_crm_contacts_file`;
CREATE TABLE `5kcrm_crm_contacts_file` (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `contacts_id` int(11) NOT NULL COMMENT '联系人ID',
  `file_id` int(11) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='联系人附件关系表';

DROP TABLE IF EXISTS `5kcrm_crm_contract`;
CREATE TABLE `5kcrm_crm_contract` (
  `contract_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL COMMENT '客户ID',
  `business_id` int(11) NOT NULL COMMENT '商机ID',
  `contacts_id` int(11) NOT NULL COMMENT '客户签约人（联系人ID）',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '合同名称',
  `num` varchar(255) NOT NULL DEFAULT '' COMMENT '合同编号',
  `order_date` date DEFAULT NULL COMMENT '下单时间',
  `money` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '合同金额',
  `total_price` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '产品总金额',
  `discount_rate` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '整单折扣',
  `check_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0待审核、1审核中、2审核通过、3审核未通过',
  `flow_id` int(11) NOT NULL DEFAULT '0' COMMENT '审核流程ID',
  `order_id` int(11) NOT NULL DEFAULT '0' COMMENT '审核步骤排序ID',
  `check_user_id` varchar(255) NOT NULL DEFAULT '' COMMENT '审批人IDs',
  `flow_user_id` varchar(255) NOT NULL DEFAULT '' COMMENT '流程审批人ID',
  `start_time` date DEFAULT NULL COMMENT '开始时间',
  `end_time` date DEFAULT NULL COMMENT '结束时间',
  `order_user_id` varchar(255) NOT NULL DEFAULT '' COMMENT '公司签约人',
  `remark` text COMMENT '备注',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `owner_user_id` int(10) NOT NULL COMMENT '负责人ID',
  `ro_user_id` varchar(500) NOT NULL DEFAULT '' COMMENT '只读权限',
  `rw_user_id` varchar(500) NOT NULL DEFAULT '' COMMENT '读写权限',
  `next_time` int(11) NOT NULL DEFAULT '0' COMMENT '下次联系时间',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`contract_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='合同表';

DROP TABLE IF EXISTS `5kcrm_crm_contract_file`;
CREATE TABLE `5kcrm_crm_contract_file` (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `contract_id` int(11) NOT NULL COMMENT '合同ID',
  `file_id` int(11) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='合同附件关系表';

DROP TABLE IF EXISTS `5kcrm_crm_contract_product`;
CREATE TABLE `5kcrm_crm_contract_product` (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `contract_id` int(11) NOT NULL COMMENT '合同ID',
  `product_id` int(11) NOT NULL COMMENT '产品ID',
  `price` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '产品单价',
  `sales_price` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '销售价格',
  `num` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '数量',
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '折扣',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '小计（折扣后价格）',
  `unit` varchar(50) NOT NULL DEFAULT '' COMMENT '单位',
  PRIMARY KEY (`r_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='合同产品关系表';

DROP TABLE IF EXISTS `5kcrm_crm_customer`;
CREATE TABLE `5kcrm_crm_customer` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '客户名称',
  `is_lock` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1锁定',
  `deal_status` varchar(10) NOT NULL DEFAULT '未成交' COMMENT '成交状态',
  `deal_time` int(11) NOT NULL COMMENT '成交时间',
  `level` varchar(500) NOT NULL DEFAULT '' COMMENT '客户级别',
  `industry` varchar(500) NOT NULL DEFAULT '' COMMENT '客户行业',
  `source` varchar(500) NOT NULL DEFAULT '' COMMENT '客户来源',
  `telephone` varchar(50) NOT NULL DEFAULT '' COMMENT '电话',
  `mobile` varchar(50) NOT NULL DEFAULT '' COMMENT '手机',
  `website` varchar(255) NOT NULL DEFAULT '' COMMENT '网址',
  `remark` text COMMENT '备注',
  `create_user_id` int(11) NOT NULL COMMENT '创建人ID',
  `owner_user_id` int(11) NOT NULL COMMENT '负责人ID',
  `ro_user_id` varchar(500) NOT NULL DEFAULT '' COMMENT '只读权限',
  `rw_user_id` varchar(500) NOT NULL DEFAULT '' COMMENT '读写权限',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '省市区',
  `location` varchar(255) NOT NULL DEFAULT '' COMMENT '定位信息',
  `detail_address` varchar(255) NOT NULL DEFAULT '' COMMENT '详细地址',
  `lng` double(14,11) DEFAULT NULL COMMENT '地理位置经度',
  `lat` double(14,11) DEFAULT NULL COMMENT '地理位置维度',
  `next_time` int(11) NOT NULL DEFAULT '0' COMMENT '下次联系时间',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='客户表';

DROP TABLE IF EXISTS `5kcrm_crm_customer_file`;
CREATE TABLE `5kcrm_crm_customer_file` (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL COMMENT '客户ID',
  `file_id` int(11) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='客户附件关系表';

DROP TABLE IF EXISTS `5kcrm_crm_leads`;
CREATE TABLE `5kcrm_crm_leads` (
  `leads_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL DEFAULT '0' COMMENT '线索转化为客户ID',
  `is_transform` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1已转化',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '线索名称',
  `source` varchar(500) NOT NULL DEFAULT '' COMMENT '线索来源',
  `telephone` varchar(255) NOT NULL DEFAULT '' COMMENT '电话',
  `mobile` varchar(255) NOT NULL DEFAULT '' COMMENT '手机',
  `industry` varchar(500) NOT NULL DEFAULT '' COMMENT '客户行业',
  `level` varchar(500) NOT NULL DEFAULT '' COMMENT '客户级别',
  `detail_address` varchar(255) NOT NULL DEFAULT '' COMMENT '地址',
  `remark` text COMMENT '备注',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `owner_user_id` int(10) NOT NULL COMMENT '负责人ID',
  `next_time` int(11) DEFAULT '0' COMMENT '下次联系时间',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`leads_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='线索表';

DROP TABLE IF EXISTS `5kcrm_crm_leads_file`;
CREATE TABLE `5kcrm_crm_leads_file` (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `leads_id` int(11) NOT NULL COMMENT '线索ID',
  `file_id` int(11) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='线索附件关系表';

DROP TABLE IF EXISTS `5kcrm_crm_product`;
CREATE TABLE `5kcrm_crm_product` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '产品名称',
  `num` varchar(255) NOT NULL DEFAULT '' COMMENT '产品编码',
  `unit` varchar(500) NOT NULL DEFAULT '箱' COMMENT '单位',
  `price` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '标准价格',
  `status` varchar(500) NOT NULL DEFAULT '上架' COMMENT '是否上架',
  `category_id` varchar(255) NOT NULL DEFAULT '' COMMENT '产品类别',
  `category_str` varchar(255) NOT NULL DEFAULT '' COMMENT '产品分类id(层级)',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '产品描述',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `owner_user_id` int(10) NOT NULL COMMENT '负责人ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品表';

DROP TABLE IF EXISTS `5kcrm_crm_product_category`;
CREATE TABLE `5kcrm_crm_product_category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL DEFAULT '',
  `pid` int(11) DEFAULT '0',
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='产品分类表';

INSERT INTO `5kcrm_crm_product_category` VALUES ('1', '默认', '0');

DROP TABLE IF EXISTS `5kcrm_crm_product_file`;
CREATE TABLE `5kcrm_crm_product_file` (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL COMMENT '产品ID',
  `file_id` int(11) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品附件关系表';

DROP TABLE IF EXISTS `5kcrm_crm_receivables`;
CREATE TABLE `5kcrm_crm_receivables` (
  `receivables_id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL COMMENT '回款计划ID',
  `number` varchar(100) NOT NULL DEFAULT '' COMMENT '回款编号',
  `customer_id` int(11) NOT NULL COMMENT '客户ID',
  `contract_id` int(11) NOT NULL COMMENT '合同ID',
  `return_time` date DEFAULT NULL COMMENT '回款日期',
  `return_type` varchar(100) NOT NULL DEFAULT '' COMMENT '回款方式',
  `money` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '回款金额',
  `check_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0待审核、1审核中、2审核通过、3审核未通过',
  `flow_id` int(11) NOT NULL DEFAULT '0' COMMENT '审核流程ID',
  `order_id` int(11) NOT NULL DEFAULT '0' COMMENT '审核步骤排序ID',
  `check_user_id` varchar(255) NOT NULL DEFAULT '' COMMENT '审批人IDs',
  `flow_user_id` varchar(255) NOT NULL DEFAULT '' COMMENT '流程审批人ID',
  `remark` text COMMENT '备注',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `owner_user_id` int(10) NOT NULL COMMENT '负责人ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`receivables_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='回款表';

DROP TABLE IF EXISTS `5kcrm_crm_receivables_plan`;
CREATE TABLE `5kcrm_crm_receivables_plan` (
  `plan_id` int(11) NOT NULL AUTO_INCREMENT,
  `num` varchar(100) NOT NULL DEFAULT '' COMMENT '期数',
  `receivables_id` int(11) NOT NULL DEFAULT '0' COMMENT '回款ID',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '1完成',
  `contract_id` int(11) NOT NULL COMMENT '合同ID',
  `customer_id` int(11) NOT NULL COMMENT '客户ID',
  `money` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '计划回款金额',
  `return_date` date DEFAULT NULL COMMENT '计划回款日期',
  `return_type` varchar(255) NOT NULL DEFAULT '' COMMENT '计划回款方式',
  `remind` tinyint(4) NOT NULL DEFAULT '0' COMMENT '提前几天提醒',
  `remind_date` date DEFAULT NULL COMMENT '提醒日期',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_user_id` int(11) NOT NULL COMMENT '创建人ID',
  `owner_user_id` int(11) NOT NULL COMMENT '负责人ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `file` varchar(500) NOT NULL DEFAULT '' COMMENT '附件',
  PRIMARY KEY (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='回款计划表';

DROP TABLE IF EXISTS `5kcrm_hrm_user_det`;
CREATE TABLE `5kcrm_hrm_user_det` (
  `userdet_id` int(9) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '员工id',
  `join_time` int(11) NOT NULL DEFAULT '0' COMMENT '入职时间',
  `type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '工作性质：1全职 2兼职 3实习',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '用户类型：1非系统用户 2系统用户 3待离职 4离职 ',
  `userstatus` tinyint(2) NOT NULL DEFAULT '1' COMMENT '员工状态：1试用 2正式',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `sex` varchar(15) NOT NULL DEFAULT '' COMMENT '性别',
  `age` int(3) NOT NULL DEFAULT '0' COMMENT '年龄',
  `job_num` varchar(30) NOT NULL DEFAULT '' COMMENT '工号',
  `idtype` tinyint(2) NOT NULL DEFAULT '0' COMMENT '证件类型',
  `idnum` varchar(30) NOT NULL DEFAULT '' COMMENT '证件号码',
  `birth_time` varchar(30) NOT NULL DEFAULT '' COMMENT '出生日期',
  `nation` varchar(20) NOT NULL DEFAULT '' COMMENT '民族',
  `internship` tinyint(2) NOT NULL DEFAULT '0' COMMENT '试用期（月）',
  `done_time` int(11) NOT NULL DEFAULT '0' COMMENT '转正时间',
  `parroll_id` int(11) NOT NULL DEFAULT '0' COMMENT '工资信息表ID',
  `create_time` int(11) NOT NULL COMMENT '添加时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '编辑时间',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT '邮箱',
  `political` varchar(100) NOT NULL DEFAULT '' COMMENT '政治面貌',
  `location` varchar(255) NOT NULL DEFAULT '' COMMENT '户籍地址',
  `leave_time` int(11) NOT NULL DEFAULT '0' COMMENT '离职时间',
  PRIMARY KEY (`userdet_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='员工档案表';

DROP TABLE IF EXISTS `5kcrm_oa_announcement`;
CREATE TABLE `5kcrm_oa_announcement` (
  `announcement_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL COMMENT '标题',
  `content` text COMMENT '内容',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `start_time` int(11) NOT NULL COMMENT '开始时间',
  `end_time` int(11) NOT NULL COMMENT '结束时间',
  `structure_ids` varchar(100) NOT NULL DEFAULT '' COMMENT '通知部门',
  `owner_user_ids` varchar(100) NOT NULL DEFAULT '' COMMENT '通知人',
  PRIMARY KEY (`announcement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='公告表';

DROP TABLE IF EXISTS `5kcrm_oa_event`;
CREATE TABLE `5kcrm_oa_event` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT '日程标题',
  `start_time` int(11) NOT NULL COMMENT '开始时间',
  `end_time` int(11) NOT NULL COMMENT '结束时间',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `type` tinyint(2) DEFAULT '0' COMMENT '0不提醒1每天2每周3每月4每年',
  `remindtype` tinyint(4) NOT NULL DEFAULT '0' COMMENT '提醒时间0准时提醒 1：5分钟前 2：15分钟前 3：30分钟前 4：一个小时前 5：两个小时前 6：一天前 7：两天前 8：一周前',
  `owner_user_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '参与人',
  `remark` varchar(2000) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '' COMMENT '备注',
  `color` varchar(15) NOT NULL DEFAULT '' COMMENT '颜色',
  PRIMARY KEY (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='日程表';

DROP TABLE IF EXISTS `5kcrm_oa_event_notice`;
CREATE TABLE `5kcrm_oa_event_notice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL COMMENT '日程ID',
  `noticetype` varchar(255) NOT NULL DEFAULT '' COMMENT '1天 2周 3月 4年 0不提醒',
  `repeated` varchar(30) NOT NULL DEFAULT '',
  `start_time` int(11) NOT NULL COMMENT '开始时间',
  `stop_time` int(11) NOT NULL COMMENT '介绍时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='日程提醒设置表';

DROP TABLE IF EXISTS `5kcrm_oa_event_relation`;
CREATE TABLE `5kcrm_oa_event_relation` (
  `eventrelation_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日程关联业务表',
  `event_id` int(11) NOT NULL COMMENT '日程ID',
  `customer_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '客户IDs',
  `contacts_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '联系人IDs',
  `business_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '商机IDs',
  `contract_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '合同IDs',
  `status` tinyint(2) DEFAULT '0' COMMENT '状态1可用',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`eventrelation_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='日程关联业务表';

DROP TABLE IF EXISTS `5kcrm_oa_examine`;
CREATE TABLE `5kcrm_oa_examine` (
  `examine_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL DEFAULT '1' COMMENT '审批类型',
  `content` varchar(500) NOT NULL DEFAULT '' COMMENT '内容',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `money` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '差旅、报销总金额',
  `start_time` int(11) NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int(11) NOT NULL DEFAULT '0' COMMENT '结束时间',
  `duration` decimal(10,1) NOT NULL DEFAULT '0.0' COMMENT '时长',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `check_user_id` varchar(200) NOT NULL COMMENT '审批人ID',
  `flow_user_id` varchar(500) NOT NULL DEFAULT '' COMMENT '流程审批人ID',
  `check_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态（0待审、1审批中、2通过、3失败、4撤销）',
  `flow_id` int(11) NOT NULL DEFAULT '0' COMMENT '审批流程ID',
  `order_id` int(10) NOT NULL DEFAULT '0' COMMENT '审批流程排序ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`examine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='审批表';

DROP TABLE IF EXISTS `5kcrm_oa_examine_category`;
CREATE TABLE `5kcrm_oa_examine_category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL COMMENT '名称',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '描述',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1启用，0禁用',
  `is_sys` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1为系统类型，不能删除',
  `user_ids` varchar(500) NOT NULL DEFAULT '' COMMENT '可见范围（员工）',
  `structure_ids` varchar(500) NOT NULL DEFAULT '' COMMENT '可见范围（部门）',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1已删除',
  `delete_time` int(11) NOT NULL DEFAULT '0' COMMENT '删除时间',
  `delete_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '删除人ID',
  `flow_id` int(11) NOT NULL DEFAULT '0' COMMENT '审批流ID',
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COMMENT='审批类型表';

INSERT INTO `5kcrm_oa_examine_category` VALUES ('1', '普通审批', '普通审批', '1', '1', '1', '', '', '1548911542', '1548911542', '0', '0', '0', '1');
INSERT INTO `5kcrm_oa_examine_category` VALUES ('2', '请假审批', '请假审批', '1', '1', '1', '', '', '1551351810', '1551351810', '0', '0', '0', '1');
INSERT INTO `5kcrm_oa_examine_category` VALUES ('3', '出差审批', '出差审批', '1', '1', '1', '', '', '1548911542', '1548911542', '0', '0', '0', '1');
INSERT INTO `5kcrm_oa_examine_category` VALUES ('4', '加班审批', '加班审批', '1', '1', '1', '', '', '1548911542', '1548911542', '0', '0', '0', '1');
INSERT INTO `5kcrm_oa_examine_category` VALUES ('5', '差旅报销', '差旅报销', '1', '1', '1', '', '', '1548911542', '1548911542', '0', '0', '0', '1');
INSERT INTO `5kcrm_oa_examine_category` VALUES ('6', '借款申请', '借款申请', '1', '1', '1', '', '', '1548911542', '1548911542', '0', '0', '0', '1');

DROP TABLE IF EXISTS `5kcrm_oa_examine_data`;
CREATE TABLE `5kcrm_oa_examine_data` (
  `data_id` int(11) NOT NULL AUTO_INCREMENT,
  `examine_id` int(11) NOT NULL COMMENT '审批ID',
  `field` varchar(30) NOT NULL COMMENT '字段名',
  `value` varchar(1000) NULL DEFAULT NULL COMMENT '值',
  PRIMARY KEY (`data_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='审批数据扩展表';

DROP TABLE IF EXISTS `5kcrm_oa_examine_file`;
CREATE TABLE `5kcrm_oa_examine_file` (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `examine_id` int(11) NOT NULL COMMENT '审批ID',
  `file_id` int(11) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='审批附件关系表';

DROP TABLE IF EXISTS `5kcrm_oa_examine_relation`;
CREATE TABLE `5kcrm_oa_examine_relation` (
  `r_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '审批关联业务表',
  `examine_id` int(11) NOT NULL COMMENT '审批ID',
  `customer_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '客户IDs',
  `contacts_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '联系人IDs',
  `business_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '商机IDs',
  `contract_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '合同IDs',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态1可用',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='审批关联业务表';

DROP TABLE IF EXISTS `5kcrm_oa_examine_travel`;
CREATE TABLE `5kcrm_oa_examine_travel` (
  `travel_id` int(11) NOT NULL AUTO_INCREMENT,
  `examine_id` int(11) NOT NULL COMMENT '审批ID',
  `start_address` varchar(100) NOT NULL DEFAULT '' COMMENT '出发地',
  `start_time` int(11) NOT NULL COMMENT '出发时间',
  `end_address` varchar(100) NOT NULL DEFAULT '' COMMENT '目的地',
  `end_time` int(11) NOT NULL COMMENT '到达时间',
  `traffic` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '交通费',
  `stay` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '住宿费',
  `diet` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '餐饮费',
  `other` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '其他费用',
  `money` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `vehicle` varchar(255) NOT NULL DEFAULT '' COMMENT '交通工具',
  `trip` varchar(50) NOT NULL DEFAULT '' COMMENT '单程往返（单程、往返）',
  `duration` decimal(10,1) NOT NULL DEFAULT '0.0' COMMENT '时长',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`travel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='差旅行程表';

DROP TABLE IF EXISTS `5kcrm_oa_examine_travel_file`;
CREATE TABLE `5kcrm_oa_examine_travel_file` (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `travel_id` int(11) NOT NULL COMMENT '差旅id',
  `file_id` int(11) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='差旅附件关系表';

DROP TABLE IF EXISTS `5kcrm_oa_log`;
CREATE TABLE `5kcrm_oa_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` tinyint(2) NOT NULL DEFAULT '1' COMMENT '日志类型（1日报，2周报，3月报）',
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '日志标题',
  `content` text COMMENT '日志内容',
  `tomorrow` varchar(1000) NOT NULL DEFAULT '' COMMENT '明日工作内容',
  `question` varchar(1000) NOT NULL DEFAULT '' COMMENT '遇到问题',
  `create_user_id` int(10) NOT NULL COMMENT '创建人ID',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `send_user_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '通知人',
  `send_structure_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '通知部门',
  `read_user_ids` text COMMENT '已读ids',
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='工作日志表';

DROP TABLE IF EXISTS `5kcrm_oa_log_file`;
CREATE TABLE `5kcrm_oa_log_file` (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `log_id` int(11) NOT NULL COMMENT '日志ID',
  `file_id` int(11) NOT NULL COMMENT '附件ID',
  PRIMARY KEY (`r_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='日志附件关系表';

DROP TABLE IF EXISTS `5kcrm_oa_log_relation`;
CREATE TABLE `5kcrm_oa_log_relation` (
  `r_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志关联业务表',
  `log_id` int(11) NOT NULL COMMENT '日志ID',
  `customer_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '客户IDs',
  `contacts_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '联系人IDs',
  `business_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '商机IDs',
  `contract_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '合同IDs',
  `status` tinyint(2) NOT NULL COMMENT '状态1可用',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='日志关联业务表';

DROP TABLE IF EXISTS `5kcrm_task`;
CREATE TABLE `5kcrm_task` (
  `task_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '任务表',
  `name` varchar(50) NOT NULL COMMENT '任务名称',
  `create_user_id` int(11) NOT NULL COMMENT '创建人ID',
  `main_user_id` int(11) NOT NULL COMMENT '负责人ID',
  `owner_user_id` varchar(255) NOT NULL DEFAULT '' COMMENT '团队成员ID',
  `structure_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '部门IDs',
  `create_time` int(11) NOT NULL COMMENT '新建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '完成状态 1正在进行2延期3归档 5结束',
  `class_id` int(5) NOT NULL DEFAULT '0' COMMENT '分类 要做 在做 待定',
  `lable_id` varchar(255) NOT NULL DEFAULT '' COMMENT '标签 ,号拼接',
  `description` text COMMENT '描述',
  `pid` int(11) DEFAULT '0' COMMENT '上级ID',
  `start_time` int(11) NOT NULL DEFAULT '0' COMMENT '开始时间',
  `stop_time` int(11) NOT NULL DEFAULT '0' COMMENT '结束时间',
  `priority` tinyint(2) NOT NULL DEFAULT '0' COMMENT '优先级 从大到小',
  `work_id` int(11) DEFAULT '0' COMMENT '项目ID',
  `is_top` tinyint(2) DEFAULT '0' COMMENT '工作台展示 0收件箱 1，2，3',
  `is_open` tinyint(2) DEFAULT '1' COMMENT '是否公开',
  `order_id` tinyint(4) NOT NULL DEFAULT '0' COMMENT '排序ID',
  `top_order_id` tinyint(4) NOT NULL DEFAULT '0' COMMENT '我的任务排序ID',
  `archive_time` int(11) NOT NULL DEFAULT '0' COMMENT '归档时间',
  `ishidden` tinyint(2) DEFAULT '0' COMMENT '是否删除',
  `hidden_time` int(11) NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`task_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='任务表';

DROP TABLE IF EXISTS `5kcrm_task_relation`;
CREATE TABLE `5kcrm_task_relation` (
  `r_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '任务关联业务表',
  `task_id` int(11) NOT NULL COMMENT '任务ID',
  `customer_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '客户IDs',
  `contacts_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '联系人IDs',
  `business_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '商机IDs',
  `contract_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '合同IDs',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态1可用',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='任务关联业务表';

DROP TABLE IF EXISTS `5kcrm_work`;
CREATE TABLE `5kcrm_work` (
  `work_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '项目名字',
  `status` tinyint(2) DEFAULT '0' COMMENT '状态 1启用 0归档',
  `create_time` int(11) NOT NULL,
  `create_user_id` int(11) NOT NULL COMMENT '创建人ID',
  `description` text COMMENT '描述',
  `color` varchar(15) NOT NULL DEFAULT '' COMMENT '颜色',
  `is_open` tinyint(2) DEFAULT '0' COMMENT '是否所有人可见 1可见',
  `owner_user_id` varchar(255) NOT NULL DEFAULT '' COMMENT '项目成员',
  `ishidden` tinyint(2) DEFAULT '0' COMMENT '是否删除',
  `archive_time` int(11) NOT NULL DEFAULT '0' COMMENT '归档时间',
  PRIMARY KEY (`work_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='项目表';

DROP TABLE IF EXISTS `5kcrm_work_relation`;
CREATE TABLE `5kcrm_work_relation` (
  `r_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志关联业务表',
  `work_id` int(11) NOT NULL COMMENT '项目ID',
  `customer_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '客户IDs',
  `contacts_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '联系人IDs',
  `business_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '商机IDs',
  `contract_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '合同IDs',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态1可用',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='项目关联业务表';

DROP TABLE IF EXISTS `5kcrm_work_task_class`;
CREATE TABLE `5kcrm_work_task_class` (
  `class_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '任务分类表',
  `name` varchar(50) NOT NULL COMMENT '分类名',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `create_user_id` int(11) NOT NULL COMMENT '创建人ID',
  `status` tinyint(2) DEFAULT '0' COMMENT '状态1正常',
  `work_id` int(11) NOT NULL COMMENT '项目ID',
  `order_id` tinyint(4) NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`class_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='任务分类表';

DROP TABLE IF EXISTS `5kcrm_work_task_file`;
CREATE TABLE `5kcrm_work_task_file` (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL COMMENT '文件ID',
  `task_id` int(11) NOT NULL COMMENT '任务ID',
  PRIMARY KEY (`r_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `5kcrm_work_task_lable`;
CREATE TABLE `5kcrm_work_task_lable` (
  `lable_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '标签名',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `create_user_id` int(11) NOT NULL COMMENT '创建人ID',
  `status` tinyint(2) DEFAULT '0' COMMENT '状态',
  `color` varchar(15) NOT NULL DEFAULT '' COMMENT '颜色',
  PRIMARY KEY (`lable_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='任务标签表';

DROP TABLE IF EXISTS `5kcrm_work_task_log`;
CREATE TABLE `5kcrm_work_task_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '项目日志表',
  `user_id` int(11) NOT NULL COMMENT '操作人ID',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '内容',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `status` tinyint(2) DEFAULT '0' COMMENT '状态 4删除',
  `task_id` int(11) DEFAULT '0' COMMENT '任务ID',
  `work_id` int(11) DEFAULT '0' COMMENT '项目ID',
  PRIMARY KEY (`log_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='任务日志表';

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

ALTER TABLE `5kcrm_oa_announcement` ADD `read_user_ids` TEXT  COMMENT '阅读人' AFTER `owner_user_ids`;

CREATE TABLE `5kcrm_crm_top` (
  `top_id` int(10) NOT NULL AUTO_INCREMENT,
  `module_id` int(10) NOT NULL COMMENT '相关模块ID',
  `set_top` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1置顶',
  `top_time` int(10) NOT NULL COMMENT '置顶时间',
  `create_role_id` int(10) NOT NULL COMMENT '创建人ID',
  `module` varchar(50) NOT NULL DEFAULT 'business' COMMENT '置顶模块',
  PRIMARY KEY (`top_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='置顶表';