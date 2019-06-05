<?php
// +----------------------------------------------------------------------
// | Description: 基础框架路由配置文件
// +----------------------------------------------------------------------
// | Author: Michael_xu <gengxiaoxu@5kcrm.com>
// +----------------------------------------------------------------------

return [
    // 定义资源路由
    '__rest__'=>[
        // 'crm/customer'		   =>'crm/customer',
    ],

	// 【仪表盘】销售简报
	'crm/index/index' => ['crm/index/index', ['method' => 'POST']],    

	// 【客户】列表
	'crm/customer/index' => ['crm/customer/index', ['method' => 'POST']],
	// 【客户】创建
	'crm/customer/save' => ['crm/customer/save', ['method' => 'POST']],
	// 【客户】编辑
	'crm/customer/update' => ['crm/customer/update', ['method' => 'POST']],	
	// 【客户】详情
	'crm/customer/read' => ['crm/customer/read', ['method' => 'POST']],		
	// 【客户】转移
	'crm/customer/transfer' => ['crm/customer/transfer', ['method' => 'POST']],		
	// 【客户】放入公海
	'crm/customer/putInPool' => ['crm/customer/putInPool', ['method' => 'POST']],	
	// 【客户】锁定
	'crm/customer/lock' => ['crm/customer/lock', ['method' => 'POST']],	
	// 【客户】回款
	'crm/customer/receivables' => ['crm/customer/receivables', ['method' => 'POST']],	
	// 【客户】回款计划
	'crm/customer/receivablesPlan' => ['crm/customer/receivablesPlan', ['method' => 'POST']],
	// 【客户】导出
	'crm/customer/excelExport' => ['crm/customer/excelExport', ['method' => 'POST']],
	// 【客户】导入模板下载
	'crm/customer/excelDownload' => ['crm/customer/excelDownload', ['method' => 'GET']],
	// 【客户】导入
	'crm/customer/excelImport' => ['crm/customer/excelImport', ['method' => 'POST']],					
	// 【客户】 员工客户分析
	'crm/customer/statistics' => ['crm/customer/statistics', ['method' => 'POST']],
	// 【客户】合同
	'crm/customer/contract' => ['crm/customer/contract', ['method' => 'POST']],	
	// 【客户】商机
	'crm/customer/business' => ['crm/customer/business', ['method' => 'POST']],	
	// 【客户】删除
	'crm/customer/delete' => ['crm/customer/delete', ['method' => 'POST']],	
	// 【客户】领取
	'crm/customer/receive' => ['crm/customer/receive', ['method' => 'POST']],	
	// 【客户】分配
	'crm/customer/distribute' => ['crm/customer/distribute', ['method' => 'POST']],	

	// 【客户】公海
	'crm/customer/pool' => ['crm/customer/pool', ['method' => 'POST']],
	// 【客户】公海领取
	'crm/customer/receive' => ['crm/customer/receive', ['method' => 'POST']],		
	
	// 【线索】列表
	'crm/leads/index' => ['crm/leads/index', ['method' => 'POST']],
	// 【线索】创建
	'crm/leads/save' => ['crm/leads/save', ['method' => 'POST']],
	// 【线索】编辑
	'crm/leads/update' => ['crm/leads/update', ['method' => 'POST']],
	// 【线索】详情
	'crm/leads/read' => ['crm/leads/read', ['method' => 'POST']],
	// 【线索】转移
	'crm/leads/transfer' => ['crm/leads/transfer', ['method' => 'POST']],
	// 【线索】转化
	'crm/leads/transform' => ['crm/leads/transform', ['method' => 'POST']],	
	// 【线索】导出
	'crm/leads/excelExport' => ['crm/leads/excelExport', ['method' => 'POST']],
	// 【线索】导入模板下载
	'crm/leads/excelDownload' => ['crm/leads/excelDownload', ['method' => 'GET']],
	// 【线索】导入
	'crm/leads/excelImport' => ['crm/leads/excelImport', ['method' => 'POST']],
	// 【线索】删除
	'crm/leads/delete' => ['crm/leads/delete', ['method' => 'POST']],				

	// 【联系人】列表
	'crm/contacts/index' => ['crm/contacts/index', ['method' => 'POST']],
	// 【联系人】创建
	'crm/contacts/save' => ['crm/contacts/save', ['method' => 'POST']],
	// 【联系人】编辑
	'crm/contacts/update' => ['crm/contacts/update', ['method' => 'POST']],
	// 【联系人】详情
	'crm/contacts/read' => ['crm/contacts/read', ['method' => 'POST']],	
	// 【联系人】联系人列表
	'crm/contacts/indexCustomer' => ['crm/contacts/indexCustomer', ['method' => 'POST']],
	// 【联系人】转移
	'crm/contacts/transfer' => ['crm/contacts/transfer', ['method' => 'POST']],	
	// 【联系人】删除
	'crm/contacts/delete' => ['crm/contacts/delete', ['method' => 'POST']],
	// 【联系人】导出
	'crm/contacts/excelExport' => ['crm/contacts/excelExport', ['method' => 'POST']],
	// 【联系人】导入模板下载
	'crm/contacts/excelDownload' => ['crm/contacts/excelDownload', ['method' => 'GET']],
	// 【联系人】导入
	'crm/contacts/excelImport' => ['crm/contacts/excelImport', ['method' => 'POST']],						

	// 【商机】列表
	'crm/business/index' => ['crm/business/index', ['method' => 'POST']],
	// 【商机】创建
	'crm/business/save' => ['crm/business/save', ['method' => 'POST']],
	// 【商机】编辑
	'crm/business/update' => ['crm/business/update', ['method' => 'POST']],	
	// 【商机】详情
	'crm/business/read' => ['crm/business/read', ['method' => 'POST']],		
	// 【商机】状态组
	'crm/business/statusList' => ['crm/business/statusList', ['method' => 'POST']],	
	// 【商机】转移
	'crm/business/transfer' => ['crm/business/transfer', ['method' => 'POST']],	
	// 【商机】相关产品
	'crm/business/product' => ['crm/business/product', ['method' => 'POST']],
	// 【商机】商机状态推进
	'crm/business/advance' => ['crm/business/advance', ['method' => 'POST']],	
	// 【商机】漏斗
	'crm/business/funnel' => ['crm/business/funnel', ['method' => 'POST']],	
	// 【商机】删除
	'crm/business/delete' => ['crm/business/delete', ['method' => 'POST']],				

	// 【合同】列表
	'crm/contract/index' => ['crm/contract/index', ['method' => 'POST']],
	// 【合同】创建
	'crm/contract/save' => ['crm/contract/save', ['method' => 'POST']],
	// 【合同】编辑
	'crm/contract/update' => ['crm/contract/update', ['method' => 'POST']],	
	// 【合同】详情
	'crm/contract/read' => ['crm/contract/read', ['method' => 'POST']],
	// 【合同】转移
	'crm/contract/transfer' => ['crm/contract/transfer', ['method' => 'POST']],
	// 【合同】审核
	'crm/contract/product' => ['crm/contract/product', ['method' => 'POST']],		
	// 【合同】回款计划
	'crm/contract/receivablesPlan' => ['crm/contract/receivablesPlan', ['method' => 'POST']],
	// 【合同】回款
	'crm/contract/receivables' => ['crm/contract/receivables', ['method' => 'POST']],	
	// 【合同】审核
	'crm/contract/check' => ['crm/contract/check', ['method' => 'POST']],	
	// 【合同】撤销审核
	'crm/contract/revokeCheck' => ['crm/contract/revokeCheck', ['method' => 'POST']],
	// 【合同】删除
	'crm/contract/delete' => ['crm/contract/delete', ['method' => 'POST']],		
	
	// 【产品】列表
	'crm/product/index' => ['crm/product/index', ['method' => 'POST']],
	// 【产品】创建
	'crm/product/save' => ['crm/product/save', ['method' => 'POST']],
	// 【产品】编辑
	'crm/product/update' => ['crm/product/update', ['method' => 'POST']],
	// 【产品】详情
	'crm/product/read' => ['crm/product/read', ['method' => 'POST']],
	// 【产品】上架/下架
	'crm/product/status' => ['crm/product/status', ['method' => 'POST']],
	// 【产品】导出
	'crm/product/excelExport' => ['crm/product/excelExport', ['method' => 'POST']],
	// 【产品】导入模板下载
	'crm/product/excelDownload' => ['crm/product/excelDownload', ['method' => 'GET']],
	// 【产品】导入
	'crm/product/excelImport' => ['crm/product/excelImport', ['method' => 'POST']],			

	// 【回款】列表
	'crm/receivables/index' => ['crm/receivables/index', ['method' => 'POST']],
	// 【回款】创建
	'crm/receivables/save' => ['crm/receivables/save', ['method' => 'POST']],
	// 【回款】编辑
	'crm/receivables/update' => ['crm/receivables/update', ['method' => 'POST']],	
	// 【回款】详情
	'crm/receivables/read' => ['crm/receivables/read', ['method' => 'POST']],	
	// 【回款】统计柱状图
	'crm/receivables/statistics' => ['crm/receivables/statistics', ['method' => 'POST']],
	// 【回款】统计列表
	'crm/receivables/statisticList' => ['crm/receivables/statisticList', ['method' => 'POST']],
	// 【回款】删除
	'crm/receivables/delete' => ['crm/receivables/delete', ['method' => 'POST']],
	// 【回款】审核
	'crm/receivables/check' => ['crm/receivables/check', ['method' => 'POST']],	
	// 【回款】撤销审核
	'crm/receivables/revokeCheck' => ['crm/receivables/revokeCheck', ['method' => 'POST']],		
	
	// 【回款计划】列表
	'crm/receivables_plan/index' => ['crm/receivables_plan/index', ['method' => 'POST']],
	// 【回款计划】创建
	'crm/receivables_plan/save' => ['crm/receivables_plan/save', ['method' => 'POST']],	
	// 【回款计划】编辑
	'crm/receivables_plan/update' => ['crm/receivables_plan/update', ['method' => 'POST']],	
	// 【回款计划】详情
	'crm/receivables_plan/read' => ['crm/receivables_plan/read', ['method' => 'POST']],	
	// 【回款计划】删除
	'crm/receivables_plan/delete' => ['crm/receivables_plan/delete', ['method' => 'POST']],				
	
	// 【相关团队】列表
	'crm/setting/team' => ['crm/setting/team', ['method' => 'POST']],
	// 【相关团队】创建
	'crm/setting/teamSave' => ['crm/setting/teamSave', ['method' => 'POST']],
	// 【客户保护规则】保存
	'crm/setting/config' => ['crm/setting/config', ['method' => 'POST']],
	// 【客户保护规则】详情
	'crm/setting/configData' => ['crm/setting/configData', ['method' => 'POST']],
	// 【合同到期提醒】
	'crm/setting/contractDay' => ['crm/setting/contractDay', ['method' => 'POST']],				

	// 【商机状态组】列表
	'crm/business_status/type' => ['crm/business_status/type', ['method' => 'POST']],
	// 【商机状态组】创建
	'crm/business_status/save' => ['crm/business_status/save', ['method' => 'POST']],
	// 【商机状态组】编辑
	'crm/business_status/update' => ['crm/business_status/update', ['method' => 'POST']],
	// 【商机状态组】详情
	'crm/business_status/read' => ['crm/business_status/read', ['method' => 'POST']],		
	// 【商机状态组】停用
	'crm/business_status/enables' => ['crm/business_status/enables', ['method' => 'POST']],
	// 【商机状态组】删除
	'crm/business_status/delete' => ['crm/business_status/delete', ['method' => 'POST']],	

	// 【产品分类】列表
	'crm/product_category/index' => ['crm/product_category/index', ['method' => 'POST']],
	// 【产品分类】创建
	'crm/product_category/save' => ['crm/product_category/save', ['method' => 'POST']],	
	// 【产品分类】编辑
	'crm/product_category/update' => ['crm/product_category/update', ['method' => 'POST']],	
	// 【产品分类】删除
	'crm/product_category/delete' => ['crm/product_category/delete', ['method' => 'POST']],		
	
	// 【业绩目标】
	'crm/achievement/save' => ['crm/achievement/save', ['method' => 'POST']],			
	'crm/achievement/update' => ['crm/achievement/update', ['method' => 'POST']],			
	'crm/achievement/index' => ['crm/achievement/index', ['method' => 'POST']],			
	'crm/achievement/datalist' => ['crm/achievement/datalist', ['method' => 'POST']],
	'crm/achievement/indexForuser' => ['crm/achievement/indexForuser', ['method' => 'POST']],	

	// 【工作台】业绩指标
	'crm/index/achievementData' => ['crm/index/achievementData', ['method' => 'POST']],
	// 【工作台】销售漏斗
	'crm/index/funnel' => ['crm/index/funnel', ['method' => 'POST']],
	// 【工作台】销售趋势
	'crm/index/saletrend' => ['crm/index/saletrend', ['method' => 'POST']],	
	// 【工作台】查重
	'crm/index/search' => ['crm/index/search', ['method' => 'POST']],	

	// 【代办事项】今日需联系
	'crm/message/todayCustomer' => ['crm/message/todayCustomer', ['method' => 'POST']],				
	'crm/message/num' => ['crm/message/num', ['method' => 'POST']],		
	'crm/message/followleads' => ['crm/message/followleads', ['method' => 'POST']],				
	'crm/message/followcustomer' => ['crm/message/followcustomer', ['method' => 'POST']],				
	'crm/message/checkcontract' => ['crm/message/checkcontract', ['method' => 'POST']],				
	'crm/message/checkreceivables' => ['crm/message/checkreceivables', ['method' => 'POST']],				
	'crm/message/remindreceivablesplan' => ['crm/message/remindreceivablesplan', ['method' => 'POST']],				
	'crm/message/endContract' => ['crm/message/endContract', ['method' => 'POST']],

	// 【客户】标记跟进
	'crm/customer/setFollow' => ['crm/customer/setFollow', ['method' => 'POST']],					
	'crm/leads/setFollow' => ['crm/leads/setFollow', ['method' => 'POST']],	

	// 【跟进记录类型设置】列表
	'crm/setting/recordList' => ['crm/setting/recordList', ['method' => 'POST']],	
	// 【跟进记录类型设置】记录类型编辑
	'crm/setting/recordEdit' => ['crm/setting/recordEdit', ['method' => 'POST']],
	// 【客户】联系人商机关联/取消关联
	'crm/contacts/relation' => ['crm/contacts/relation', ['method' => 'POST']],	

	// 【公海】数据统计 导出
	'crm/customer/poolExcelExport' => ['crm/customer/poolExcelExport', ['method' => 'POST']],	

	// MISS路由
	'__miss__'  => 'admin/base/miss',
];