/** 获取file大小的名称 */
export function fileSize(size) {
  var size_int = size
  if (typeof size === 'string' && size.constructor == String) {
    size_int = parseInt(size)
  }
  var formatSize
  if (parseInt(size_int / 1024 / 1024) > 0) {
    formatSize = (size_int / 1024 / 1024).toFixed(2) + 'MB'
  } else if (parseInt(size_int / 1024) > 0) {
    formatSize = (size_int / 1024).toFixed(2) + 'kB'
  } else {
    formatSize = size_int + 'Byte'
  }
  return formatSize
}

/** 获取最大 z-index 的值 */
import {
  PopupManager
} from 'element-ui/lib/utils/popup'
export function getMaxIndex() {
  return PopupManager.nextZIndex()
}

/** 深拷贝 */
export function objDeepCopy(source) {
  var sourceCopy = source instanceof Array ? [] : {}
  for (var item in source) {
    if (!source[item]) {
      sourceCopy[item] = source[item]
    } else {
      sourceCopy[item] = typeof source[item] === 'object' ? objDeepCopy(source[item]) : source[item]
    }
  }
  return sourceCopy
}

/** 获取文件类型图标 */
export function getFileTypeIcon(file) {
  if (file.type.indexOf('image') !== -1) {
    return require('@/assets/img/file_img.png')
  } else if (file.type.indexOf('audio') !== -1 || file.type.indexOf('video') !== -1) {
    return require('@/assets/img/file_video.png')
  } else {
    var index = file.name.lastIndexOf('.')
    var ext = file.name.substr(index + 1)
    if (arrayContain(['xlsx', 'xls', 'XLSX', 'XLS'], ext)) {
      return require('@/assets/img/file_excle.png')
    } else if (arrayContain(['doc', 'docx', 'DOC', 'DOCX'], ext)) {
      return require('@/assets/img/file_word.png')
    } else if (arrayContain(['rar', 'zip'], ext)) {
      return require('@/assets/img/file_zip.png')
    } else if (ext === 'pdf') {
      return require('@/assets/img/file_pdf.png')
    } else if (ext === 'ppt' || ext === 'pptx') {
      return require('@/assets/img/file_ppt.png')
    } else if (arrayContain(['txt', 'text'], ext)) {
      return require('@/assets/img/file_txt.png')
    }
  }
  return require('@/assets/img/file_unknown.png')
}

export function getFileTypeIconWithSuffix(ext) {
  if (arrayContain(['jpg', 'png', 'gif'], ext)) {
    return require('@/assets/img/file_img.png')
  } else if (arrayContain(['mp4', 'mp3', 'avi'], ext)) {
    return require('@/assets/img/file_excle.png')
  } else if (arrayContain(['xlsx', 'xls', 'XLSX', 'XLS'], ext)) {
    return require('@/assets/img/file_excle.png')
  } else if (arrayContain(['doc', 'docx', 'DOC', 'DOCX'], ext)) {
    return require('@/assets/img/file_word.png')
  } else if (arrayContain(['rar', 'zip'], ext)) {
    return require('@/assets/img/file_zip.png')
  } else if (ext === 'pdf') {
    return require('@/assets/img/file_pdf.png')
  } else if (ext === 'ppt' || ext === 'pptx') {
    return require('@/assets/img/file_ppt.png')
  } else if (arrayContain(['txt', 'text'], ext)) {
    return require('@/assets/img/file_txt.png')
  }
  return require('@/assets/img/file_unknown.png')
}

function arrayContain(array, string) {
  return array.some((item) => {
    return item === string
  })
}

/** 判断输入的是number */
export function regexIsNumber(nubmer) {
  var regex = /^[0-9]+.?[0-9]*/
  if (!regex.test(nubmer)) {
    return false
  }
  return true
}

/** 判断输入的是crm数字 数字的整数部分须少于12位，小数部分须少于4位*/
export function regexIsCRMNumber(nubmer) {
  var regex = /^([-+]?\d{1,12})(\.\d{0,4})?$/
  if (!regex.test(nubmer)) {
    return false
  }
  return true
}

/** 判断输入的是货币 货币的整数部分须少于10位，小数部分须少于2位*/
export function regexIsCRMMoneyNumber(nubmer) {
  var regex = /^([-+]?\d{1,10})(\.\d{0,2})?$/
  if (!regex.test(nubmer)) {
    return false
  }
  return true
}

/** 判断输入的是电话*/
export function regexIsCRMMobile(mobile) {
  var regex = /^1[3-9]\d{9}$/
  if (!regex.test(mobile)) {
    return false
  }
  return true
}

/** 判断输入的是邮箱*/
export function regexIsCRMEmail(email) {
  var regex = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/
  if (!regex.test(email)) {
    return false
  }
  return true
}

/**
 * 时间操作
 * @param 
 */
/** 时间戳转date*/
import moment from 'moment'

export function getDateFromTimestamp(time) {
  var times = 0
  if (time.length === 13) {
    times = parseInt(time)
  } else {
    times = parseInt(time) * 1000
  }
  return new Date(times) // 如果date为13位不需要乘1000
}

/**
 * 
 * @param {*} timestamp 时间戳 
 * @param {*} format 格式化
 */
export function timestampToFormatTime(timestamp, format) {
  if (timestamp && timestamp.toString().length >= 10) {
    return moment(getDateFromTimestamp(timestamp.toString())).format(format)
  }
  return ''
}
/**
 * 
 * @param {*} format 格式化字符串
 */
export function formatTimeToTimestamp(format) {
  if (format && format.length > 0) {
    var timeValue = moment(format)
      .valueOf()
      .toString()
    return timeValue.length > 10 ? timeValue.substr(0, 10) : timeValue
  }
  return ''
}

/** image 下载 */
/**
 *
 * @param {*} data url
 * @param {*} filename 名称
 */
export function downloadImage(data, filename) {
  var httpindex = data.indexOf('http')
  if (httpindex === 0) {
    const image = new Image()
    // 解决跨域 canvas 污染问题
    image.setAttribute('crossOrigin', 'anonymous')
    image.onload = function () {
      const canvas = document.createElement('canvas')
      canvas.width = image.width
      canvas.height = image.height
      const context = canvas.getContext('2d')
      context.drawImage(image, 0, 0, image.width, image.height)
      const dataURL = canvas.toDataURL('image/png')
      // 生成一个 a 标签
      const a = document.createElement('a')
      // 创建一个点击事件
      const event = new MouseEvent('click')
      // 将 a 的 download 属性设置为我们想要下载的图片的名称，若 name 不存在则使用'图片'作为默认名称
      a.download = filename || '图片'
      // 将生成的 URL 设置为 a.href 属性
      var blob = dataURLtoBlob(dataURL)
      a.href = URL.createObjectURL(blob)
      // 触发 a 的点击事件
      a.dispatchEvent(event)
    }
    image.src = data
  } else {
    // 生成一个 a 标签
    const a = document.createElement('a')
    // 创建一个点击事件
    const event = new MouseEvent('click')
    // 将 a 的 download 属性设置为我们想要下载的图片的名称，若 name 不存在则使用'图片'作为默认名称
    a.download = filename || '图片'
    // 将生成的 URL 设置为 a.href 属性
    a.href = data
    // 触发 a 的点击事件
    a.dispatchEvent(event)
  }
}
/** 
 * path  和 name
 */
export function downloadFile(data) {
  var a = document.createElement('a')
  a.href = data.path
  a.download = data.name ? data.name : '文件'
  a.target = '_black'
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
}

export function dataURLtoBlob(dataurl) {
  // eslint-disable-next-line one-var
  var arr = dataurl.split(','),
    mime = arr[0].match(/:(.*?);/)[1],
    bstr = atob(arr[1]),
    n = bstr.length,
    u8arr = new Uint8Array(n)
  while (n--) {
    u8arr[n] = bstr.charCodeAt(n)
  }
  return new Blob([u8arr], {
    type: mime
  })
}
