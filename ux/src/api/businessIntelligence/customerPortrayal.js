import request from '@/utils/request'

/**
 * 城市分布分析
 */
export function biAchievementAnalysisAPI(data) {
  return request({
    url: 'bi/customer/addressAnalyse',
    method: 'post',
    data: data
  })
}

export function biAchievementPortraitAPI(data) {
  return request({
    url: 'bi/customer/portrait',
    method: 'post',
    data: data
  })
}
