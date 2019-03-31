import request from '@/utils/request'

// 审批类型列表
export function oaExamineCategory(data) {
  return request({
    url: 'oa/examine/category',
    method: 'post',
    data: data
  })
}

/**
 * 审批类型的创建
 * @param {*} data
 * title 审批类型名称
 * remark 审批类型说明
 * examineFlow 审批流程数据
 */
export function oaExamineCategorySave(data) {
  return request({
    url: 'oa/examine/categorySave',
    method: 'post',
    data: data
  })
}

/**
 * 审批类型编辑
 * @param {*} data
 * id 审批类型ID
 */
export function oaExamineCategoryUpdate(data) {
  return request({
    url: 'oa/examine/categoryUpdate',
    method: 'post',
    data: data
  })
}

/**
 * 审批删除
 * @param {*} data
 * id 审批ID
 */
export function oaExamineCategoryDelete(data) {
  return request({
    url: 'oa/examine/categoryDelete',
    method: 'post',
    data: data
  })
}

/**
 * 审批状态（启用、停用）
 * @param {*} data
 * id 审批ID
 * status 状态1启用0禁用
 */
export function oaExamineCategoryEnables(data) {
  return request({
    url: 'oa/examine/categoryEnables',
    method: 'post',
    data: data
  })
}
