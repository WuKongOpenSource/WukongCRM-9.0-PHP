<?php
// +----------------------------------------------------------------------
// | Description: 基础框架路由配置文件
// +----------------------------------------------------------------------
// | Author: Michael_xu <gengxiaoxu@5kcrm.com>
// +----------------------------------------------------------------------

return [
    // 定义资源路由
    '__rest__'=>[
        // 'oa/log'		   =>'oa/log',
    ],
	// 【工作台】工作圈
	'oa/index/index' => ['oa/index/index', ['method' => 'POST']],
	//日程
	'oa/index/eventList' => ['oa/index/eventList', ['method' => 'POST']],
	//日程详情
	'oa/index/event' => ['oa/index/event', ['method' => 'POST']],
	//任务列表
	'oa/index/taskList' => ['oa/index/taskList', ['method' => 'POST']],
	//所有项目列表
	'oa/task/workList' => ['oa/task/workList', ['method' => 'POST']],
	//我下属的任务
	'oa/task/subTaskList' => ['oa/task/subTaskList', ['method' => 'POST']],
	//我的任务
	'oa/task/myTask' => ['oa/task/myTask', ['method' => 'POST']],
	//任务详情
	'oa/task/read' => ['oa/task/read', ['method' => 'POST']],
	
	//【标签】编辑
	'oa/tasklable/update' => ['oa/tasklable/update', ['method' => 'POST']],		
	//【标签】添加
	'oa/tasklable/save' => ['oa/tasklable/save', ['method' => 'POST']],		
	//【标签】删除
	'oa/tasklable/delete' => ['oa/tasklable/delete', ['method' => 'POST']],	
	//【标签】列表展示
	'oa/tasklable/index' => ['oa/tasklable/index', ['method' => 'POST']],	
	
	'oa/tasklable/groupList' => ['oa/tasklable/groupList', ['method' => 'POST']],	
	//【标签】标签获取任务列表
	'oa/tasklable/getWokList' => ['oa/tasklable/getWokList', ['method' => 'POST']],	
	
	//【任务】详情
	'oa/task/read'  => ['oa/task/read', ['method' => 'POST']],
	//【任务】删除
	'oa/task/delete'  => ['oa/task/delete', ['method' => 'POST']],
	//【任务】编辑
	'oa/task/update' => ['oa/task/update', ['method' => 'POST']],	
	//【任务】保存
	'oa/task/save' => ['oa/task/save', ['method' => 'POST']],	
	//【任务】编辑任务名
	'oa/task/updateName' => ['oa/task/updateName', ['method' => 'POST']],	
	//【任务】编辑标签
	'oa/task/updateLable' => ['oa/task/updateLable', ['method' => 'POST']],	
	//【任务】设置截止时间 
	'oa/task/updateStoptime' => ['oa/task/updateStoptime', ['method' => 'POST']],	
	//【任务】编辑参与人
	'oa/task/updateOwner' => ['oa/task/updateOwner', ['method' => 'POST']],	
	//【任务】单独删除参与部门
	'oa/task/delStruceureById' => ['oa/task/delStruceureById', ['method' => 'POST']],	
	//【任务】单独删除参与人
	'oa/task/delOwnerById' => ['oa/task/delOwnerById', ['method' => 'POST']],
	//【任务】编辑优先级
	'oa/task/updatePriority' => ['oa/task/updatePriority', ['method' => 'POST']],	
	//【任务】结束
	'oa/task/taskOver' => ['oa/task/taskOver', ['method' => 'POST']],	
	//【任务】操作记录
	'oa/task/readLoglist' => ['oa/task/readLoglist', ['method' => 'POST']],	
	//【任务】解除关联关系
	'oa/task/delrelation' => ['oa/task/delrelation', ['method' => 'POST']],	
	//任务评论添加
	'oa/taskcomment/save' => ['oa/taskcomment/save', ['method' => 'POST']],	
	'oa/taskcomment/delete' => ['oa/taskcomment/delete', ['method' => 'POST']],	
	
	//通讯录
	'oa/addresslist/index' => ['oa/addresslist/index', ['method' => 'POST']],	

	// 【日程】列表
	'oa/event/index' => ['oa/event/index', ['method' => 'POST']],
	// 【日程】添加
	'oa/event/save' => ['oa/event/save', ['method' => 'POST']],
	// 【日程】编辑
	'oa/event/update' => ['oa/event/update', ['method' => 'POST']],	
	// 【日程】详情
	'oa/event/read' => ['oa/event/read', ['method' => 'POST']],		
	// 【日程】删除
	'oa/event/delete' => ['oa/event/delete', ['method' => 'POST']],		
	
	// 【公告】列表
	'oa/announcement/index' => ['oa/announcement/index', ['method' => 'POST']],
	// 【公告】添加
	'oa/announcement/save' => ['oa/announcement/save', ['method' => 'POST']],
	// 【公告】编辑
	'oa/announcement/update' => ['oa/announcement/update', ['method' => 'POST']],	
	// 【公告】详情
	'oa/announcement/read' => ['oa/announcement/read', ['method' => 'POST']],		
	// 【公告】详情
	'oa/announcement/delete' => ['oa/announcement/delete', ['method' => 'POST']],		
	
	// 【日志】添加
	'oa/log/save' => ['oa/log/save', ['method' => 'POST']],		
	// 【日志】编辑
	'oa/log/update' => ['oa/log/update', ['method' => 'POST']],		
	// 【日志】删除
	'oa/log/delete' => ['oa/log/delete', ['method' => 'POST']],		
	// 【日志】列表
	'oa/log/index' => ['oa/log/index', ['method' => 'POST']],		
	// 【日志】详情
	'oa/log/read' => ['oa/log/read', ['method' => 'POST']],	
	// 【日志】添加评论
	'oa/log/commentSave'=>['oa/log/commentSave', ['method' => 'POST']],
	// 【日志】删除评论
	'oa/log/commentDel'=>['oa/log/commentDel', ['method' => 'POST']],
	//标记已读
	'oa/log/setread' => ['oa/log/setread', ['method' => 'POST']],	
	
	// 【审批】类型列表
	'oa/examine/category'=>['oa/examine/category', ['method' => 'POST']],
	// 【审批】类型列表（添加）
	'oa/examine/categoryList'=>['oa/examine/categoryList', ['method' => 'POST']],	
	// 【审批】类型创建
	'oa/examine/categorySave'=>['oa/examine/categorySave', ['method' => 'POST']],
	// 【审批】类型编辑
	'oa/examine/categoryUpdate'=>['oa/examine/categoryUpdate', ['method' => 'POST']],
	// 【审批】类型删除
	'oa/examine/categoryDelete'=>['oa/examine/categoryDelete', ['method' => 'POST']],	
	// 【审批】类型状态
	'oa/examine/categoryEnables'=>['oa/examine/categoryEnables', ['method' => 'POST']],	
	// 【审批】列表
	'oa/examine/index'=>['oa/examine/index', ['method' => 'POST']],	
	// 【审批】创建
	'oa/examine/save'=>['oa/examine/save', ['method' => 'POST']],	
	// 【审批】编辑
	'oa/examine/update'=>['oa/examine/update', ['method' => 'POST']],	
	// 【审批】详情
	'oa/examine/read'=>['oa/examine/read', ['method' => 'POST']],	
	// 【审批】删除
	'oa/examine/delete'=>['oa/examine/delete', ['method' => 'POST']],				
	// 【审批】审核
	'oa/examine/check'=>['oa/examine/check', ['method' => 'POST']],	
	// 【审批】撤销审核
	'oa/examine/revokeCheck'=>['oa/examine/revokeCheck', ['method' => 'POST']],	

	// 【代办事项】办公
	'oa/message/num'=>['oa/message/num', ['method' => 'POST']],		
	
	// MISS路由
	'__miss__'  => 'admin/base/miss',
];