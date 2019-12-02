import request from '@/utils/request'

/**
 * 项目任务统计
 * @param {*} data
 */
export function workWorkStatisticAPI(data) {
  return request({
    url: 'work/work/statistic',
    method: 'post',
    data: data
  })
}
