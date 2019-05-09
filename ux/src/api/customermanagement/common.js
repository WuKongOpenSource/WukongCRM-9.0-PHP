import request from '@/utils/request'

// crm 自定义字段的添加
/**
 *
 * @param {*} data
 * 操作方法 (save:添加、update:编辑、read:详情、index:列表)
 * 操作ID (如：客户ID)
 */
export function filedGetField(data) {
  return request({
    url: 'admin/field/getField',
    method: 'post',
    data: data
  })
}

// crm 自定义字段验重
export function filedValidates(data) {
  return request({
    url: 'admin/field/validates',
    method: 'post',
    data: data
  })
}

// crm 自定义字段(高级筛选)
export function filterIndexfields(data) {
  return request({
    url: 'admin/index/fields',
    method: 'post',
    data: data
  })
}

// 商机状态组列表 systemCustomer.js 也包含该接口
export function businessGroupList(data) {
  return request({
    url: 'crm/business_status/type',
    method: 'post',
    data: data
  })
}
// 场景列表
export function crmSceneIndex(data) {
  return request({
    url: 'admin/scene/index',
    method: 'post',
    data: data
  })
}

// 场景创建
export function crmSceneSave(data) {
  return request({
    url: 'admin/scene/save',
    method: 'post',
    data: data
  })
}

// 场景编辑
export function crmSceneUpdate(data) {
  return request({
    url: 'admin/scene/update',
    method: 'post',
    data: data
  })
}

// 场景默认
export function crmSceneDefaults(data) {
  return request({
    url: 'admin/scene/defaults',
    method: 'post',
    data: data
  })
}

// 场景详情
export function crmSceneRead(data) {
  return request({
    url: 'admin/scene/read',
    method: 'post',
    data: data
  })
}

// 场景删除
export function crmSceneDelete(data) {
  return request({
    url: 'admin/scene/delete',
    method: 'post',
    data: data
  })
}

// 场景排序
export function crmSceneSort(data) {
  return request({
    url: 'admin/scene/sort',
    method: 'post',
    data: data
  })
}

// 列表字段排序数据
export function crmFieldConfigIndex(data) {
  return request({
    url: 'admin/field/configIndex',
    method: 'post',
    data: data
  })
}

// 列表排序编辑
export function crmFieldConfig(data) {
  return request({
    url: 'admin/field/config',
    method: 'post',
    data: data
  })
}

// 列表宽度设置
export function crmFieldColumnWidth(data) {
  return request({
    url: 'admin/field/columnWidth',
    method: 'post',
    data: data
  })
}

// 跟进记录列表
export function crmRecordIndex(data) {
  return request({
    url: 'admin/record/index',
    method: 'post',
    data: data
  })
}

// 跟进记录添加
export function crmRecordSave(data) {
  return request({
    url: 'admin/record/save',
    method: 'post',
    data: data
  })
}

// 跟进记录删除
export function crmRecordDelete(data) {
  return request({
    url: 'admin/record/delete',
    method: 'post',
    data: data
  })
}

// 操作记录
export function crmIndexFieldRecord(data) {
  return request({
    url: 'admin/index/fieldRecord',
    method: 'post',
    data: data
  })
}

// 客户管理下 合同审批信息
export function crmExamineFlowStepList(data) {
  return request({
    url: 'admin/examine_flow/stepList',
    method: 'post',
    data: data
  })
}

/**
 * 审批记录
 * @param {*} data
 * types
 * types_id
 */
export function crmExamineFlowRecordList(data) {
  return request({
    url: 'admin/examine_flow/recordList',
    method: 'post',
    data: data
  })
}

/**
 * 合同审批人信息
 * @param {*} data
 * types crm_contract
 */
export function crmExamineFlowUserList(data) {
  return request({
    url: 'admin/examine_flow/userList',
    method: 'post',
    data: data
  })
}

/**
 * 相关团队列表
 * @param {*} data
 * types crm_leads
 * types_id 分类ID
 */
export function crmSettingTeam(data) {
  return request({
    url: 'crm/setting/team',
    method: 'post',
    data: data
  })
}

/**
 * 相关团队创建
 * @param {*} data
 * types crm_leads
 * types_id 分类ID
 */
export function crmSettingTeamSave(data) {
  return request({
    url: 'crm/setting/teamSave',
    method: 'post',
    data: data
  })
}

/**
 * 获取导入描述信息
 * @param {*} data
 * type crm_leads
 */
export function adminFieldUniqueFieldAPI(data) {
  return request({
    url: 'admin/field/uniqueField',
    method: 'post',
    data: data
  })
}
