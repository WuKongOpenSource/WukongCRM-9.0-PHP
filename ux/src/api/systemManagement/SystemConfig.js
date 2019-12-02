import request from '@/utils/request'

// 企业首页
export function adminSystemSave(data) {
  return request({
    url: 'admin/system/save',
    method: 'post',
    data: data,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
}

// 企业首页
export function adminSystemIndex(data) {
  return request({
    url: 'admin/system/index',
    method: 'post',
    data: data,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
}
