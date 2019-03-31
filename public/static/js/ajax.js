var baseUrl = '';
var timeout = 60 * 1000;

/**
 * Ajax
 * @constructor
 */
function Ajax() {
}

Ajax.get = _get;
Ajax.post = _post;

/**
 * get
 * @param url
 * @param params
 * @return {*|{readyState, getResponseHeader, getAllResponseHeaders, setRequestHeader, overrideMimeType, statusCode, abort}}
 * @private
 */
function _get(url, params) {
  return $.ajax({
    url: baseUrl + url,
    method: 'GET',
    timeout: timeout,
    data: params,
    dataType: 'json',
    cache: false,
    success: function (response) {
    },
    error: function (XMLHttpRequest, textStatus, errorThrown) {
      alert('网络加载失败，请稍后重试！');
    }
  })
}

/**
 * post
 * @param url
 * @param params
 * @return {*|{readyState, getResponseHeader, getAllResponseHeaders, setRequestHeader, overrideMimeType, statusCode, abort}}
 * @private
 */
function _post(url, params) {
  return $.ajax({
    url: baseUrl + url,
    method: 'POST',
    timeout: timeout,
    data: params,
    cache: false,
    success: function (response) {
    },
    error: function (XMLHttpRequest, textStatus, errorThrown) {
      alert('网络加载失败，请稍后重试！');
    }
  })
}
