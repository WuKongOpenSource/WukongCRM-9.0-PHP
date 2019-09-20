var form = {
  databaseUrl: '127.0.0.1',
  databasePort: '3306',
  databaseTable: '5kcrm_',
  databasePwd: '',
  databaseUser: 'root'
};
var rules = {
  databaseUrl: {
    required: true,
    regexp: /[^\u4e00-\u9fa5]+$/,
    label: '数据库主机地址'
  },
  databaseName: {
    required: true,
    regexp: /[^\u4e00-\u9fa5]+$/,
    label: '数据库名'
  },
  databasePort: {
    required: true,
    regexp: /^[0-9]*$/,
    label: '数据库端口号'
  },
  databaseUser: {
    required: true,
    regexp: /[^\u4e00-\u9fa5]+$/,
    label: '数据库用户名'
  },
  databasePwd: {
    required: false,
    regexp: null,
    label: '数据库密码'
  },
  databaseTable: {
    required: true,
    regexp: /[^\u4e00-\u9fa5]+$/,
    label: '数据库表前缀'
  },
  root: {
    required: true,
    regexp: /^[1][3-9][0-9]{9}$/,
    label: '管理员账号'
  },
  pwd: {
    required: true,
    regexp: /^.{6,}$/,
    label: '管理员密码'
  }
};

var timer = null;
var flag = true;

_initFormValue();

// 上一步
$('.prev').click(function () {
  window.location = 'step1.html'
});
// 下一步
$('.next').click(function () {
  // 获取form数据
  // 校验form数据 true 校验通过  false 校验失败
  var forms = getFormData();
  var result = checkForm();
  console.log('result---', result, forms);
  if (result) {
    $.ajax({
      cache: true,
      type: "POST",
      dataType: 'json',
      url: "./step4",
      data: {
        form: forms
      },
      async: false,
      success: function (result) {
        if (result.code == '200') {
          alert(result.data);
          window.location = '../../../index.html';
        } else if (result.code == '400') {
          alert(result.error); //失败
          return false;
          // window.location = 'step3.html'
        } else {
          window.location = 'step3.html'
          alert('安装失败');
        }
      }
      // getRes();
    });    
  }
});

function getRes() {
  if (timer) {
    clearTimeout(timer);
    timer = null;
  }
  // $.post("/admin/install/progress",function(result){
  //   $(".install_progress_a").show();
  //   $(".install_progress_a").empty();
  //   $(".form").hide();
  //   $(".control").hide();
  //   if(result.data.now < result.data.length ){
  //     $(".install_progress_a").append("<progress class='install_progress' max='"+ result.data.length+"' value='"+ result.data.now +"''></progress>");
  //     html ="<div class='install_pro'> 正在安装中，请稍后...</div>";
  //     $(".install_progress_a").append(html);
  //     timer = setTimeout(getRes, 1000)
  //   } else {
  //     $(".install_progress_a").append("<progress class='install_progress' max='100' value='100'></progress>");
  //     html ="<div class='install_pro'> 安装成功 </div>";
  //     $(".install_progress_a").append(html);
  //     clearTimeout(timer);
  //     timer = null;
  //   }
  // });

  $.ajax({
    cache: true,
    type: "POST",
    dataType: 'json',
    url: './progress',
    data: '',
    async: false,
    success: function (result) {
      $(".install_progress_a").show();
      $(".install_progress_a").empty();
      $(".form").hide();
      $(".control").hide();
      if (result.data.now < result.data.length) {
        $(".install_progress_a").append("<progress class='install_progress' max='" + result.data.length + "' value='" + result.data.now + "''></progress>");
        html = "<div class='install_pro'> 正在安装中，请稍后...</div>";
        $(".install_progress_a").append(html);
        timer = setTimeout(getRes, 1000)
      } else {
        $(".install_progress_a").append("<progress class='install_progress' max='100' value='100'></progress>");
        html = "<div class='install_pro'> 安装成功 </div>";
        $(".install_progress_a").append(html);
        clearTimeout(timer);
        timer = null;
      }
    }
  });

}

/**
 * 给 form 输入框赋初值
 * @private
 */
function _initFormValue() {
  $('input').each(function (index, item) {
    item.value = form[item.name] || null
    $(item).change(function () {
      $(this).removeClass('input-error');
      var borther = $(this).siblings('.error');
      borther.remove();
      if (borther.filter('.remind')[0]) {
        borther.filter('.remind').show()
      }
    })
  });
}

/**
 * 获取 form 输入的值
 * @return {form}
 */
function getFormData() {
  $('input').each(function (index, item) {
    form[item.name] = item.value;
    // 重置表单状态
    $(item).removeClass('input-error');
    var borther = $(item).siblings();
    borther.remove('.error');
    if (borther.filter('.remind')[0]) {
      borther.filter('.remind').show()
    }
  });
  return form
}

/**
 * 校验表单
 * @return {boolean}
 */
function checkForm() {
  var result = {};
  for (var key in rules) {
    var rule = rules[key];
    if (rule.required) {
      if (!form[key] || form[key] == '') {
        result[key] = rule.label + '不能为空！';
      }
    }
    if (rule.regexp && !result[key]) {
      var flag = rule.regexp.test(form[key]);
      if (!flag) {
        let msg = rule.label === '管理员账号' ? '手机号码' : rule.label
        result[key] = '请输入正确的' + msg + '！';
      }
    }
  }
  var flag = true;
  if (JSON.stringify(result) !== '{}' && typeof result === 'object') {
    for (var key in result) {
      renderErrorMsg(key, result[key])
      flag = false;
    }
  }
  return flag
}

/**
 * 渲染错误信息
 * @param key
 * @param msg
 */
function renderErrorMsg(key, msg) {
  $('input').each(function (index, item) {
    if (item.name === key) {
      var remindDom = $(item).next()[0] || null;
      if (remindDom) {
        $(remindDom).hide()
      }
      $(item).parent().append('<div class="error">' + msg + '</div>');
      $(item).addClass('input-error')
    }
  })
}

/**
 * 提交表单
 */
function submitForm() {}