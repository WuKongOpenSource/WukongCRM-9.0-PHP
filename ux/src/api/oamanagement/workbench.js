import request from '@/utils/request'

// 工作圈列表
export function workbenchList(data) {
  return request({
    url: 'oa/index/index',
    method: 'post',
    data: data
  })
}
// 工作圈列表
export function eventList(data) {
  return request({
    url: 'oa/index/eventList',
    method: 'post',
    data: data
  })
}
// 根据时间查日程
export function scheduleListAPI(data) {
  return request({
    url: 'oa/index/event',
    method: 'post',
    data: data
  })
}
// 工作圈列表
export function taskListAPI(data) {
  return request({
    url: 'oa/index/taskList',
    method: 'post',
    data: data
  })
}

/**
 * 待办消息数
 * @param {*} data 
 */
export function oaMessageNumAPI(data) {
  return request({
    url: 'oa/message/num',
    method: 'post',
    data: data
  })
}
