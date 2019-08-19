<?php
// +----------------------------------------------------------------------
// | Description: 基础框架路由配置文件
// +----------------------------------------------------------------------
// | Author: Michael_xu <gengxiaoxu@5kcrm.com>
// +----------------------------------------------------------------------

return [
    // 定义资源路由
    '__rest__'=>[
        // 'work/rules'		   =>'work/rules',=
    ],

	// 【默认首页】
	'work/index/index' => ['work/index/index', ['method' => 'POST']],
	'work/index/workList' => ['work/index/workList', ['method' => 'POST']],
	
	//【项目】添加
	'work/work/save' => ['work/work/save', ['method' => 'POST']],
	//【项目】详情
	'work/work/read' => ['work/work/read', ['method' => 'POST']],			
	//【项目】项目编辑
	'work/work/update' => ['work/work/update', ['method' => 'POST']],		
	//【项目】项目删除
	'work/work/delete' => ['work/work/delete', ['method' => 'POST']],		
	//【项目】项目归档
	'work/work/archive' => ['work/work/archive', ['method' => 'POST']],	
	//【项目】项目归档恢复
	'work/work/arRecover' => ['work/work/arRecover', ['method' => 'POST']],	
	//【项目】项目归档列表
	'work/work/archiveList' => ['work/work/archiveList', ['method' => 'POST']],	
	'work/work/fileList' => ['work/work/fileList', ['method' => 'POST']],	
	//添加参与成员角色
	'work/work/addUserGroup' => ['work/work/addUserGroup', ['method' => 'POST']],	
	//项目成员角色列表
	'work/work/groupList' => ['work/work/groupList', ['method' => 'POST']],	
	//【项目】任务统计
	'work/work/statistic' => ['work/work/statistic', ['method' => 'POST']],	
	//【项目】退出项目
	'work/work/leave' => ['work/work/leave', ['method' => 'POST']],	
	//【项目】添加参与人
	'work/work/ownerAdd' => ['work/work/ownerAdd', ['method' => 'POST']],	
	//【项目】删除参与人
	'work/work/ownerDel' => ['work/work/ownerDel', ['method' => 'POST']],	
	//【项目】参与人列表
	'work/work/ownerList' => ['work/work/ownerList', ['method' => 'POST']],	

	//【我的任务】查看我的任务
	'work/task/myTask'  => ['work/task/myTask', ['method' => 'POST']],
	//【我的任务】拖拽改变分类
	'work/task/updateTop'  => ['work/task/updateTop', ['method' => 'POST']],
    //【项目】拖拽改变分类
	'work/task/updateOrder'  => ['work/task/updateOrder', ['method' => 'POST']],
	//【项目】拖拽改变任务分类顺序
	'work/task/updateClassOrder'  => ['work/task/updateClassOrder', ['method' => 'POST']],
	//【任务】获取子任务
	'work/task/subTaskList'  => ['work/task/subTaskList', ['method' => 'POST']],
	//【任务】详情
	'work/task/read'  => ['work/task/read', ['method' => 'POST']],
	//【任务】编辑
	'work/task/update' => ['work/task/update', ['method' => 'POST']],	
	//【任务】编辑任务名
	'work/task/updateName' => ['work/task/updateName', ['method' => 'POST']],	
	//【任务】编辑标签
	'work/task/updateLable' => ['work/task/updateLable', ['method' => 'POST']],	
	//【任务】设置截止时间 
	'work/task/updateStoptime' => ['work/task/updateStoptime', ['method' => 'POST']],	
	//【任务】编辑参与人
	'work/task/updateOwner' => ['work/task/updateOwner', ['method' => 'POST']],	
	//【任务】单独删除参与部门
	'work/task/delStruceureById' => ['work/task/delStruceureById', ['method' => 'POST']],	
	//【任务】单独删除参与人
	'work/task/delOwnerById' => ['work/task/delOwnerById', ['method' => 'POST']],
	//【任务】编辑优先级
	'work/task/updatePriority' => ['work/task/updatePriority', ['method' => 'POST']],	
	//【任务】结束
	'work/task/taskOver' => ['work/task/taskOver', ['method' => 'POST']],	
	//【任务】操作记录
	'work/task/readLoglist' => ['work/task/readLoglist', ['method' => 'POST']],	
	//【任务】获取项目下列表
	'work/task/index' => ['work/task/index', ['method' => 'POST']],
	//【任务】添加
	'work/task/save' => ['work/task/save', ['method' => 'POST']],		
	//【任务】归档
	'work/task/archive' => ['work/task/archive', ['method' => 'POST']],		
	//【任务】归档恢复
	'work/task/recover' => ['work/task/recover', ['method' => 'POST']],		
	//【任务】归档列表
	'work/task/archList' => ['work/task/archList', ['method' => 'POST']],		
	//【任务】删除
	'work/task/delete' => ['work/task/delete', ['method' => 'POST']],		
	//【任务】日历任务展示
	'work/task/dateList' => ['work/task/dateList', ['method' => 'POST']],		
	//【任务】归档某类已完成任务
	'work/task/archiveTask'  => ['work/task/archiveTask', ['method' => 'POST']],
	
	//【标签】编辑
	'work/tasklable/update' => ['work/tasklable/update', ['method' => 'POST']],		
	//【标签】添加
	'work/tasklable/save' => ['work/tasklable/save', ['method' => 'POST']],		
	//【标签】删除
	'work/tasklable/delete' => ['work/tasklable/delete', ['method' => 'POST']],	
	//【标签】列表展示
	'work/tasklable/index' => ['work/tasklable/index', ['method' => 'POST']],	
	//【标签】详情
	'work/tasklable/read' => ['work/tasklable/read', ['method' => 'POST']],	
	'work/tasklable/groupList' => ['work/tasklable/groupList', ['method' => 'POST']],	
	//【标签】标签获取任务列表
	'work/tasklable/getWokList' => ['work/tasklable/getWokList', ['method' => 'POST']],	

	//【类别】编辑
	'work/taskclass/rename' => ['work/taskclass/rename', ['method' => 'POST']],		
	//【类别】添加
	'work/taskclass/save' => ['work/taskclass/save', ['method' => 'POST']],		
	//【类别】删除
	'work/taskclass/delete' => ['work/taskclass/delete', ['method' => 'POST']],		

	// 【回收站】列表
	'work/trash/index' => ['work/trash/index', ['method' => 'POST']],
	// 【回收站】删除
	'work/trash/delete' => ['work/trash/delete', ['method' => 'POST']],
	// 【回收站】恢复
	'work/trash/recover' => ['work/trash/recover', ['method' => 'POST']],	

	//任务评论添加
	'work/taskcomment/save' => ['work/taskcomment/save', ['method' => 'POST']],	
	'work/taskcomment/delete' => ['work/taskcomment/delete', ['method' => 'POST']],		

	// MISS路由
	'__miss__'  => 'admin/base/miss',
];