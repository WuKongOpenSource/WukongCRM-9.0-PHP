import request from '@/utils/request'

export function businessGroupList(data) {
  return request({
    url: 'crm/business_status/type',
    method: 'post',
    data: data
  })
}

export function businessGroupAdd(data) {
  return request({
    url: 'crm/business_status/save',
    method: 'post',
    data: data
  })
}

/** 商机状态组详情 */
export function businessGroupRead(data) {
  return request({
    url: 'crm/business_status/read',
    method: 'post',
    data: data
  })
}

// 商机状态组编辑
export function businessGroupUpdate(data) {
  return request({
    url: 'crm/business_status/update',
    method: 'post',
    data: data
  })
}

/** 商机状态组删除 */
export function businessGroupDelete(data) {
  return request({
    url: 'crm/business_status/delete',
    method: 'post',
    data: data
  })
}

/** 自定义字段（字段数据）的添加编辑操作 */
export function customFieldHandle(data) {
  return request({
    url: 'admin/field/update',
    method: 'post',
    data: data
  })
}

/** 自定义字段（字段数据）的详情 */
export function customFieldList(data) {
  return request({
    url: 'admin/field/read',
    method: 'post',
    data: data
  })
}

/** 自定义字段（字段数据）的列表更新时间 */
export function customFieldIndex(data) {
  return request({
    url: 'admin/field/index',
    method: 'post',
    data: data
  })
}

/** 产品类别 数据获取 */
export function productCategoryIndex(data) {
  return request({
    url: 'crm/product_category/index',
    method: 'post',
    data: data
  })
}

/** 产品分类添加*/
export function productCategorySave(data) {
  return request({
    url: 'crm/product_category/save',
    method: 'post',
    data: data
  })
}

/** 产品分类编辑*/
export function productCategoryUpdate(data) {
  return request({
    url: 'crm/product_category/update',
    method: 'post',
    data: data
  })
}

/** 产品分类删除*/
export function productCategoryDelete(data) {
  return request({
    url: 'crm/product_category/delete',
    method: 'post',
    data: data
  })
}

/** 客户保护规则*/
export function crmSettingConfig(data) {
  return request({
    url: 'crm/setting/config',
    method: 'post',
    data: data
  })
}

/** 客户保护规则*/
export function crmSettingConfigData(data) {
  return request({
    url: 'crm/setting/configData',
    method: 'post',
    data: data
  })
}

/**
 * 部门业绩目标列表
 * @param {*} data
 * year 年
 * status 1销售（目标）2回款（目标）
 * id 部门ID
 */
export function crmAchievementIndex(data) {
  return request({
    url: 'crm/achievement/index',
    method: 'post',
    data: data
  })
}

/**
 * 业绩目标编辑接口
 * @param {*} data
 * datalist 对应数组
 */
export function crmAchievementUpdate(data) {
  return request({
    url: 'crm/achievement/update',
    method: 'post',
    data: data
  })
}

/**
 * 员工业绩目标列表
 * @param {*} data
 * year 年
 * status 1销售（目标）2回款（目标）
 * user_id 员工ID
 * structure_id 部门ID
 */
export function crmAchievementIndexForuser(data) {
  return request({
    url: 'crm/achievement/indexForuser',
    method: 'post',
    data: data
  })
}
