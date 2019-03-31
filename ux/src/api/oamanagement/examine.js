import request from '@/utils/request'

// 审批类型列表
export function oaExamineCategoryList(data) {
  return request({
    url: 'oa/examine/categoryList',
    method: 'post',
    data: data
  })
}

// 审批新建
export function oaExamineSave(data) {
  return request({
    url: 'oa/examine/save',
    method: 'post',
    data: data
  })
}

// 审批编辑
export function oaExamineUpdate(data) {
  return request({
    url: 'oa/examine/update',
    method: 'post',
    data: data
  })
}

// 审批列表
export function oaExamineIndex(data) {
  return request({
    url: 'oa/examine/index',
    method: 'post',
    data: data
  })
}

// 审批删除
export function oaExamineDelete(data) {
  return request({
    url: 'oa/examine/delete',
    method: 'post',
    data: data
  })
}

// 审批详情
export function oaExamineRead(data) {
  return request({
    url: 'oa/examine/read',
    method: 'post',
    data: data
  })
}

// OA审批审核
export function oaExamineCheck(data) {
  return request({
    url: 'oa/examine/check',
    method: 'post',
    data: data
  })
}

// OA审批撤回审批
export function oaExamineRevokeCheck(data) {
  return request({
    url: 'oa/examine/revokeCheck',
    method: 'post',
    data: data
  })
}
