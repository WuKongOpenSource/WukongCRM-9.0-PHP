import request from '@/utils/request'

// 审批类型列表
export function addresslist(data) {
  return request({
    url: 'oa/addresslist/index',
    method: 'post',
    data: data
  })
}
