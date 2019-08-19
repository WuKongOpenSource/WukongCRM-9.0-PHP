<?php
// +----------------------------------------------------------------------
// | Description: 基础框架路由配置文件
// +----------------------------------------------------------------------
// | Author: Michael_xu <gengxiaoxu@5kcrm.com>
// +----------------------------------------------------------------------

return [
    // 定义资源路由
    '__rest__'=>[

    ],

	// 【商业智能】员工客户分析
	'bi/customer/statistics' => ['bi/customer/statistics', ['method' => 'POST']],
	// 【商业智能】销售漏斗
	'bi/business/funnel' => ['bi/business/funnel', ['method' => 'POST']],
	// 【商业智能】回款统计 柱状图
	'bi/receivables/statistics' => ['bi/receivables/statistics', ['method' => 'POST']],
	// 【商业智能】回款统计列表
	'bi/receivables/statisticList' => ['bi/receivables/statisticList', ['method' => 'POST']],	
	// 【商业智能】产品销量统计
	'bi/product/statistics' => ['bi/product/statistics', ['method' => 'POST']],	
	// 【商业智能】业绩目标完成情况
	'bi/achievement/statistics' => ['bi/achievement/statistics', ['method' => 'POST']],	
	// 【商业智能】新增商机数与金额趋势分析
	'bi/business/businessTrend' => ['bi/business/businessTrend', ['method' => 'POST']],	
	// 【商业智能】新增商机数与金额趋势分析 列表
	'bi/business/trendList' => ['bi/business/trendList', ['method' => 'POST']],	
	// 【商业智能】赢单机会转化率趋势分析
	'bi/business/win' => ['bi/business/win', ['method' => 'POST']],	
	// 【商业智能】合同金额排行
	'bi/ranking/contract' => ['bi/ranking/contract', ['method' => 'POST']],
	// 【商业智能】回款金额排序
	'bi/ranking/receivables' => ['bi/ranking/receivables', ['method' => 'POST']],
	// 【商业智能】签约合同排序
	'bi/ranking/signing' => ['bi/ranking/signing', ['method' => 'POST']],
	// 【商业智能】新增客户排序
	'bi/ranking/addCustomer' => ['bi/ranking/addCustomer', ['method' => 'POST']],	
	// 【商业智能】新增联系人排序
	'bi/ranking/addContacts' => ['bi/ranking/addContacts', ['method' => 'POST']],
	// 【商业智能】跟进次数排行
	'bi/ranking/recordNun' => ['bi/ranking/recordNun', ['method' => 'POST']],	
	// 【商业智能】跟进客户数排行
	'bi/ranking/recordCustomer' => ['bi/ranking/recordCustomer', ['method' => 'POST']],	
	// 【商业智能】出差次数排行
	'bi/ranking/examine' => ['bi/ranking/examine', ['method' => 'POST']],	
	// 【商业智能】产品销量排行
	'bi/ranking/product' => ['bi/ranking/product', ['method' => 'POST']],	
	// 【商业智能】产品分类销量分析
	'bi/product/productCategory' => ['bi/product/productCategory', ['method' => 'POST']],
	// 【商业智能】合同数量分析/金额分析/回款金额分析
	'bi/contract/analysis' => ['bi/contract/analysis', ['method' => 'POST']],
	// 【商业智能】合同汇总表
	'bi/contract/summary' => ['bi/contract/summary', ['method' => 'POST']],	
	// 【商业智能】员工客户总量分析
	'bi/customer/total' => ['bi/customer/total', ['method' => 'POST']],
	// 【商业智能】员工客户跟进次数分析
	'bi/customer/recordTimes' => ['bi/customer/recordTimes', ['method' => 'POST']],
	// 【商业智能】员工客户跟进次数分析 具体员工列表
	'bi/customer/recordList' => ['bi/customer/recordList', ['method' => 'POST']],	
	// 【商业智能】员工跟进方式分析
	'bi/customer/recordMode' => ['bi/customer/recordMode', ['method' => 'POST']],
	// 【商业智能】客户转化率分析
	'bi/customer/conversion' => ['bi/customer/conversion', ['method' => 'POST']],	
	// 【商业智能】客户转化率分析具体数据
	'bi/customer/conversionInfo' => ['bi/customer/conversionInfo', ['method' => 'POST']],	
	// 【商业智能】公海客户分析
	'bi/customer/pool' => ['bi/customer/pool', ['method' => 'POST']],
	// 【商业智能】公海客户分析 列表
	'bi/customer/poolList' => ['bi/customer/poolList', ['method' => 'POST']],	
	// 【商业智能】员工客户成交周期
	'bi/customer/userCycle' => ['bi/customer/userCycle', ['method' => 'POST']],	
	// 【商业智能】产品成交周期
	'bi/customer/productCycle' => ['bi/customer/productCycle', ['method' => 'POST']],
	// 【商业智能】地区成交周期
	'bi/customer/addressCycle' => ['bi/customer/addressCycle', ['method' => 'POST']],
	// 【商业智能】客户所在城市分析
	'bi/customer/addressAnalyse' => ['bi/customer/addressAnalyse', ['method' => 'POST']],
	// 【商业智能】客户行业/级别/来源分析
	'bi/customer/portrait' => ['bi/customer/portrait', ['method' => 'POST']],
	// 【商业智能】日志统计
	'bi/log/statistics' => ['bi/log/statistics', ['method' => 'POST']],	
	'bi/log/excelExport' => ['bi/log/excelExport', ['method' => 'POST']],
	// 【商业智能】审批统计
	'bi/examine/statistics' => ['bi/examine/statistics', ['method' => 'POST']],	
	'bi/examine/index' => ['bi/examine/index', ['method' => 'POST']],	
	'bi/examine/excelExport' => ['bi/examine/excelExport', ['method' => 'POST']],

	// MISS路由
	'__miss__'  => 'admin/base/miss',
];