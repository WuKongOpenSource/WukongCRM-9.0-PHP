var tableData = [
  {
    option: '操作系统',
    now: 'LINUX',
    suggest: 'WINNT/LINUX',
    affect: '功能影响',
  },
  {
    option: 'PHP版本',
    now: 'LINUX',
    suggest: '开启',
    affect: '功能影响',
  },
  {
    option: 'MySQL扩展',
    now: 0,
    suggest: '开启',
    affect: '功能影响',
  },
  {
    option: 'session',
    now: 1,
    suggest: '开启',
    affect: '功能影响',
  },
  {
    option: 'curl扩展',
    now: 1,
    suggest: '开启',
    affect: '功能影响',
  },
  {
    option: 'zlib扩展',
    now: 0,
    suggest: '开启',
    affect: '功能影响',
  },
  {
    option: 'mb_string扩展',
    now: 1,
    suggest: '开启',
    affect: '功能影响',
  },
  {
    option: '附件上传',
    now: '20M',
    suggest: '>2M、<20M',
    affect: '功能影响',
  },
];

var catalogueTableData = [
  {catalogue: '/Uploads', need: '可写、读', status: 1},
  {catalogue: '/App/Runtime', need: '可写、读', status: 0},
  {catalogue: '/App/Conf', need: '可写、读', status: 0},
];

//renderTable();
//renderCatalogueTable();

$('.prev').click(function () {
  window.location = 'index.html'
});
$('.next').click(function () {
  window.location = 'step2.html'
});

function renderTable() {
  var template = "" +
    "<tr>\n" +
    "  <td>{option}</td>\n" +
    "  <td>{now}</td>\n" +
    "  <td>{suggest}</td>\n" +
    "  <td>{affect}</td>\n" +
    "</tr>";

  tableData.forEach(function (item, index) {
    var str = template.replace('{option}', item.option);

    str = str.replace('{suggest}', item.suggest);
    str = str.replace('{affect}', item.affect);

    if (item.now === 0) {
      str = str.replace('{now}', '<img src="../icon/error.png" width="20">');
    } else if (item.now === 1) {
      str = str.replace('{now}', '<img src="../icon/success.png" width="20">');
    } else {
      str = str.replace('{now}', item.now);
    }

    $('.table_01 tbody').append(str);
    str = ''
  })
}

function renderCatalogueTable() {
  var template = "" +
    "<tr>\n" +
    "  <td>{catalogue}</td>\n" +
    "  <td>{need}</td>\n" +
    "  <td>{status}</td>\n" +
    "</tr>";

  catalogueTableData.forEach(function (item, index) {
    var str = template.replace('{catalogue}', item.catalogue);

    str = str.replace('{need}', item.need);

    if (item.status === 0) {
      str = str.replace('{status}', '<img src="../icon/error.png" width="20">');
    } else if (item.status === 1) {
      str = str.replace('{status}', '<img src="../icon/success.png" width="20">');
    } else {
      str = str.replace('{status}', item.status);
    }

    $('.catalogue-table tbody').append(str);
    str = ''
  })
}