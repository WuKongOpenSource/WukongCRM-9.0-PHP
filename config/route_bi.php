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
	// 【商业智能】回款统计
	'bi/receivables/statistics' => ['bi/receivables/statistics', ['method' => 'POST']],
	// 【商业智能】回款统计列表
	'bi/receivables/statisticList' => ['bi/receivables/statisticList', ['method' => 'POST']],	
	// 【商业智能】产品销量统计
	'bi/product/statistics' => ['bi/product/statistics', ['method' => 'POST']],	
	// 【商业智能】业绩目标完成情况
	'bi/achievement/statistics' => ['bi/achievement/statistics', ['method' => 'POST']],	

	// MISS路由
	'__miss__'  => 'admin/base/miss',
];