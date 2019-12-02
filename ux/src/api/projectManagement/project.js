import request from '@/utils/request'

/**
 * 归档任务列表
 * @param {*} data
 */
export function workTaskArchListAPI(data) {
  return request({
    url: 'work/task/archList',
    method: 'post',
    data: data
  })
}

/**
 * 项目详情
 * @param {*} data
 */
export function workWorkReadAPI(data) {
  return request({
    url: 'work/work/read',
    method: 'post',
    data: data
  })
}

/**
 * 项目删除
 * @param {*} data
 */
export function workWorkDeleteAPI(data) {
  return request({
    url: 'work/work/delete',
    method: 'post',
    data: data
  })
}

/**
 * 新建项目
 * @param {*} data
 */
export function workWorkSaveAPI(data) {
  return request({
    url: 'work/work/save',
    method: 'post',
    data: data
  })
}

/**
 * 退出项目
 * @param {*} data
 */
export function workWorkLeaveAPI(data) {
  return request({
    url: 'work/work/leave',
    method: 'post',
    data: data
  })
}

/**
 * 任务板列表
 * @param {*} data
 */
export function workTaskIndexAPI(data) {
  return request({
    url: 'work/task/index',
    method: 'post',
    data: data
  })
}

/**
 * 归档项目
 * @param {*} data
 */
export function workWorkArchiveAPI(data) {
  return request({
    url: 'work/work/archive',
    method: 'post',
    data: data
  })
}

/**
 * 新建分类列表
 * @param {*} data
 */
export function workTaskclassSaveAPI(data) {
  return request({
    url: 'work/taskclass/save',
    method: 'post',
    data: data
  })
}

/**
 * 分类重命名
 * @param {*} data
 */
export function workTaskclassRenameAPI(data) {
  return request({
    url: 'work/taskclass/rename',
    method: 'post',
    data: data
  })
}

/**
 * 分类删除
 * @param {*} data
 */
export function workTaskclassDeleteAPI(data) {
  return request({
    url: 'work/taskclass/delete',
    method: 'post',
    data: data
  })
}

/**
 * 获取附件列表
 * @param {*} data
 */
export function workWorkFileListAPI(data) {
  return request({
    url: 'work/work/fileList',
    method: 'post',
    data: data
  })
}

/**
 * 项目 -- 基础设置
 * @param {*} data
 */
export function workWorkUpdateAPI(data) {
  return request({
    url: 'work/work/update',
    method: 'post',
    data: data
  })
}

/**
 * 项目 -- 成员列表
 * @param {*} data
 */
export function workWorkOwnerListAPI(data) {
  return request({
    url: 'work/work/ownerList',
    method: 'post',
    data: data
  })
}

/**
 * 项目 -- 成员添加
 * @param {*} data
 */
export function workWorkOwnerAddAPI(data) {
  return request({
    url: 'work/work/ownerAdd',
    method: 'post',
    data: data
  })
}

/**
 * 项目 -- 成员删除
 * @param {*} data
 */
export function workWorkOwnerDelAPI(data) {
  return request({
    url: 'work/work/ownerDel',
    method: 'post',
    data: data
  })
}

/**
 * 项目 -- 归档已完成任务
 * @param {*} data
 */
export function workTaskArchiveTaskAPI(data) {
  return request({
    url: 'work/task/archiveTask',
    method: 'post',
    data: data
  })
}

/**
 * 拖拽改变分类
 * @param {*} data
 */
export function workTaskUpdateOrderAPI(data) {
  return request({
    url: 'work/task/updateOrder',
    method: 'post',
    data: data
  })
}

/**
 * 拖拽改变分类列表
 * @param {*} data
 */
export function workTaskUpdateClassOrderAPI(data) {
  return request({
    url: 'work/task/updateClassOrder',
    method: 'post',
    data: data
  })
}

/**
 * 项目成员添加角色
 * @param {*} data
 */
export function workWorkAddUserGroupAPI(data) {
  return request({
    url: 'work/work/addUserGroup',
    method: 'post',
    data: data
  })
}

/**
 * 项目成员角色列表
 * @param {*} data
 */
export function workWorkGroupListAPI(data) {
  return request({
    url: 'work/work/groupList',
    method: 'post',
    data: data
  })
}
