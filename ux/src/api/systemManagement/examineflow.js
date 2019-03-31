import request from '@/utils/request'

// 审批流程列表
export function examineFlowIndex(data) {
  return request({
    url: 'admin/examine_flow/index',
    method: 'post',
    data: data
  })
}

// 审批流程创建
export function examineFlowSave(data) {
  return request({
    url: 'admin/examine_flow/save',
    method: 'post',
    data: data
  })
}

/**
 * 审批流程更新
 * @param {*} data
 * id 审批流ID
 */
export function examineFlowUpdate(data) {
  return request({
    url: 'admin/examine_flow/update',
    method: 'post',
    data: data
  })
}

/**
 * 审批流程删除
 * @param {*} data
 * id 审批流ID
 */
export function examineFlowDelete(data) {
  return request({
    url: 'admin/examine_flow/delete',
    method: 'post',
    data: data
  })
}

/**
 * 审批流程状态（启用、停用）
 * @param {*} data
 * id 审批流ID
 * status 状态1启用0禁用
 */
export function examineFlowEnables(data) {
  return request({
    url: 'admin/examine_flow/enables',
    method: 'post',
    data: data
  })
}
