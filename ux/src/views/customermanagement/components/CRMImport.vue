<template>
  <el-dialog
    :visible.sync="showDialog"
    :title="'导入'+crmTypeName"
    :append-to-body="true"
    :close-on-click-modal="false"
    :before-close="beforeClose"
    :close-on-press-escape="false"
    width="550px"
    @close="closeView">
    <div class="dialog-body">
      <el-tabs
        v-model="activeName"
        :stretch="true"
        @tab-click="tabClick">
        <el-tab-pane
          :disabled="importExecStats"
          label="① 上传文件"
          name="first">
          <div class="sections">
            <div>一、请按照数据模板的格式准备要导入的数据。<span
              class="download"
              @click="download">点击下载</span>《{{ crmTypeName }}导入模板》</div>
            <div class="content content-tips">
              <div>注意事项：</div>
              <div>1、模板中的表头名称不能更改，表头行不能删除</div>
              <div>2、其中标*为必填项，必须填写</div>
              <div>3、导入文件请勿超过20MB</div>
            </div>
          </div>
          <div class="sections">
            <flexbox align="initial">
              <div>二、</div>
              <div>请选择数据重复时的处理方式（查重规则：【{{ fieldUniqueInfo }}】）</div>
            </flexbox>
            <div class="content">
              <el-select
                v-model="config"
                placeholder="请选择">
                <el-option
                  v-for="(item, index) in [{name: '覆盖系统原有数据',value: 1},{name: '跳过',value: 0}]"
                  :key="index"
                  :label="item.name"
                  :value="item.value"/>
              </el-select>
            </div>
          </div>
          <div class="sections">
            <div>三、请选择需要导入的文件</div>
            <div class="content">
              <flexbox class="file-select">
                <el-input
                  v-model="file.name"
                  :disabled="true"/>
                <el-button
                  type="primary"
                  @click="selectFile">选择文件</el-button>
              </flexbox>
            </div>
          </div>
          <div v-if="crmType != 'user'" class="sections">
            <div>四、请选择负责人（{{ crmType == 'customer' ? '如不选择，导入的客户将进入公海' : '必选' }}）</div>
            <div class="content">
              <div class="user-cell">
                <xh-user-cell
                  :value="user"
                  @value-change="userSelect"/>
              </div>
            </div>
          </div>
          <input
            id="importInputFile"
            type="file"
            accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
            @change="uploadFile">
        </el-tab-pane>
        <el-tab-pane
          :disabled="!importExecStats"
          label="② 导入数据"
          name="second">
          <div class="importProgress">
            <p
              v-if="errorInfo"
              class="error">
              {{ errorInfo }}
            </p>
            <div v-else>
              <p v-if="isCancel" class="cancel">
                <i class="el-icon-warning"/>
                已取消
              </p>
              <p v-else-if="importExecStats">
                <i class="el-icon-loading"/>
                导入中...
              </p>
              <p v-else class="success">
                <i class="el-icon-success"/>
                已完成
              </p>
              <p v-if="importResponseShow">
                共 {{ total }} 条数据，
                已导入 <span class="primary">{{ done }}</span> 条，
                成功 <span class="success">{{ done - error }}</span> 条，
                <span v-if="config">覆盖 <span>{{ cover }}</span> 条,</span>
                失败<span v-if="config == 0">/跳过</span> <span class="error">{{ error }}</span> 条
              </p>
              <p v-if="errorFilePath">
                <el-link type="primary" @click.native="downloadErrorFile">
                  <i class="el-icon-download" />
                  点击下载错误数据
                </el-link>
              </p>
            </div>
          </div>
        </el-tab-pane>
      </el-tabs>
    </div>
    <span
      slot="footer"
      class="dialog-footer">
      <el-button
        v-if="activeName === 'first'"
        @click="closeView">取 消</el-button>
      <el-button
        v-if="activeName === 'first'"
        type="primary"
        @click="sure({}, true)">确 定</el-button>
      <el-button
        v-if="importExecStats"
        @click="cancelImport">取消导入</el-button>
    </span>
  </el-dialog>
</template>

<script>
import { mapGetters } from 'vuex'
import {
  crmCustomerExcelImport,
  crmCustomerExcelDownloadURL
} from '@/api/customermanagement/customer'
import {
  crmLeadsExcelImport,
  crmLeadsExcelDownloadURL
} from '@/api/customermanagement/clue'
import {
  crmContactsExcelImport,
  crmContactsExcelDownloadURL
} from '@/api/customermanagement/contacts'
import {
  crmProductExcelImport,
  crmProductExcelDownloadURL
} from '@/api/customermanagement/product'
import { adminFieldUniqueFieldAPI } from '@/api/customermanagement/common'
import {
  userExcelImport,
  userExcelDownloadURL,
  adminFileDownload
} from '@/api/common'

import { XhUserCell } from '@/components/CreateCom'

export default {
  name: 'CRMImport', // 文件导入
  components: {
    XhUserCell
  },

  props: {
    show: {
      type: Boolean,
      default: false
    },
    // CRM类型
    crmType: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      showDialog: false,
      config: 1, // 	1 覆盖，0跳过
      file: { name: '' },
      user: [],
      // 字段验重信息
      fieldUniqueInfo: '',
      // 选项卡名称
      activeName: 'first',
      // 导入执行状态
      importExecStats: false,
      // 错误信息
      errorInfo: '',
      // 执行完成状态
      isDone: false,
      // 是否取消导入
      isCancel: false,
      // 导入结果展示状态
      importResponseShow: false,
      // 已导入数
      done: 0,
      // 总计
      total: 0,
      // 错误数
      error: 0,
      // 覆盖数
      cover: 0,
      // 错误文件路径
      errorFilePath: '',
      // 导入响应缓存
      resTemp: {},
      // 队列标识
      importQueueIndex: ''
    }
  },
  computed: {
    ...mapGetters(['userInfo']),
    crmTypeName() {
      return (
        {
          customer: '客户',
          leads: '线索',
          contacts: '联系人',
          product: '产品',
          user: '员工'
        }[this.crmType] || ''
      )
    }
  },
  watch: {
    show: function(val) {
      this.showDialog = val
      if (!this.fieldUniqueInfo) {
        this.getFieldUniqueInfo()
      }
    }
  },
  mounted() {
    this.user.push(this.userInfo)
  },
  methods: {
    sure(data = {}, start = false) {
      var params = {
        import_queue_index: this.importQueueIndex,
        cover: this.cover,
        ...data
      }
      if (!this.file.name) {
        this.$message.error('请选择导入文件')
      } else if (
        this.crmType != 'customer' &&
        (!this.user || this.user.length == 0)
      ) {
        this.$message.error('请选择负责人')
      } else {
        this.activeName = 'second'
        if (start) {
          this.importExecStats = true
        }
        this.isDone = false
        this.refreshOrCloseTips()
        params.config = this.config
        if (!params.temp_file) {
          params.file = this.file
        }
        console.log(params)
        params.owner_user_id = this.user.length > 0 ? this.user[0].id : ''
        var request = {
          customer: crmCustomerExcelImport,
          leads: crmLeadsExcelImport,
          contacts: crmContactsExcelImport,
          product: crmProductExcelImport,
          user: userExcelImport
        }[this.crmType]
        request(params)
          .then(res => {
            this.importQueueIndex = res.data.import_queue_index
            this.resTemp = res
            this.importResponseShow = true
            this.done = res.data.done ? res.data.done : this.done
            this.total = res.data.total ? res.data.total : this.total
            this.error = res.data.error ? res.data.error : this.error
            this.cover = res.data.cover ? res.data.cover : this.cover
            // 取消导入
            if (this.isCancel && res.data.page === -1) {
              this.importExecStats = false
              window.onbeforeunload = null
              if (this.error) {
                this.errorFilePath = res.data.error_file_path
              } else {
                this.errorFilePath = ''
              }
            // 系统繁忙等待中
            } else if (!this.isCancel && res.data.page === -2) {
              this.importResponseShow = false
              setTimeout(() => {
                this.sure({
                  page: 1,
                  temp_file: res.data.temp_file
                })
              }, 1000)
            // 导入已完成
            } else if (res.data.done >= res.data.total) {
              this.isDone = true
              this.importExecStats = false
              this.$emit('listRefresh')
              window.onbeforeunload = null
              if (res.data.error) {
                this.errorFilePath = res.data.error_file_path
              } else {
                this.errorFilePath = ''
              }
            // 继续导入
            } else {
              if (this.isCancel) {
                this.sure({
                  page: -1,
                  temp_file: res.data.temp_file,
                  error_file: res.data.error_file,
                  error: res.data.error
                })
              } else {
                this.sure({
                  page: res.data.page,
                  temp_file: res.data.temp_file,
                  error_file: res.data.error_file,
                  error: res.data.error
                })
              }
            }
          })
          .catch(() => {
            this.isCancel = true
            this.importExecStats = false
            this.importExecStats = false
            window.onbeforeunload = null
          })
      }
    },
    // 下载模板操作
    download() {
      var a = document.createElement('a')
      a.href =
        window.BASE_URL +
        {
          customer: crmCustomerExcelDownloadURL,
          leads: crmLeadsExcelDownloadURL,
          contacts: crmContactsExcelDownloadURL,
          product: crmProductExcelDownloadURL,
          user: userExcelDownloadURL
        }[this.crmType]
      a.target = '_black'
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
    },
    // 选择文件
    selectFile() {
      document.getElementById('importInputFile').click()
    },
    /** 图片选择出发 */
    uploadFile(event) {
      var files = event.target.files
      const file = files[0]
      this.file = file
      event.target.value = ''
    },
    // 用户选择
    userSelect(data) {
      if (data.value && data.value.length > 0) {
        this.user = data.value
      } else {
        this.user = []
      }
    },
    // 关闭操作
    closeView() {
      if (this.activeName == 'second') {
        this.file = { name: '' }
      }
      this.activeName = 'first'
      this.importResponseShow = false
      this.errorFilePath = ''
      this.isDone = false
      this.importExecStats = false
      this.errorInfo = ''
      this.isCancel = false
      this.importQueueIndex = ''
      this.$emit('close')
    },
    /**
     * 获取字段验重信息
     */
    getFieldUniqueInfo() {
      adminFieldUniqueFieldAPI({ types: 'crm_' + this.crmType })
        .then(res => {
          this.fieldUniqueInfo = res.data
        })
        .catch(() => {})
    },
    // 选项卡切换
    tabClick() {
      if (this.activeName === 'first') {
        this.importQueueIndex = ''
        this.importResponseShow = false
        this.errorFilePath = ''
        this.isCancel = false
        this.isDone = false
        this.errorInfo = ''
        this.importExecStats = false
        this.file = { name: '' }
      }
    },
    // 下载导入错误数据
    downloadErrorFile() {
      adminFileDownload({
        path: this.errorFilePath,
        name: '导入错误数据'
      }).then(res => {
        var blob = new Blob([res.data], {
          type: 'application/vnd.ms-excel;charset=utf-8'
        })
        var downloadElement = document.createElement('a')
        var href = window.URL.createObjectURL(blob) // 创建下载的链接
        downloadElement.href = href
        downloadElement.download =
              decodeURI(
                res.headers['content-disposition'].split('filename=')[1]
              ) || '' // 下载后文件名
        document.body.appendChild(downloadElement)
        downloadElement.click() // 点击下载
        document.body.removeChild(downloadElement) // 下载完成移除元素
        window.URL.revokeObjectURL(href) // 释放掉blob对象
      })
    },
    // 取消导入
    cancelImport() {
      if (this.importExecStats) {
        this.$confirm('此操作将取消数据导入, 是否继续?', '提示', {
          confirmButtonText: '确定',
          cancelButtonText: '取消',
          type: 'warning'
        }).then(() => {
          this.importExecStats = false
          this.isCancel = true
        }).catch(() => {})
      }
    },
    // 导入中禁止关闭弹窗
    beforeClose(done) {
      if (this.importExecStats && this.activeName == 'second') {
        this.$message.error('导入中无法关闭窗口')
      } else {
        done()
      }
    },
    // 标签关闭刷新提示
    refreshOrCloseTips() {
      window.onbeforeunload = (event) => {
        this.sure({
          page: -1,
          temp_file: this.resTemp.temp_file,
          error_file: this.resTemp.error_file
        })
        return event
      }
    }
  }
}
</script>

<style scoped lang="scss">
.sections {
  font-size: 14px;
  .download {
    cursor: pointer;
    color: #3e84e9;
  }
}

.content {
  padding: 10px 10px 10px 30px;
  .el-select {
    width: 300px;
  }
  .user-cell {
    width: 300px;
  }
}

.content-tips {
  font-size: 12px;
  color: #a9a9a9;
  line-height: 15px;
}

#importInputFile {
  display: none;
}

.file-select {
  .el-input {
    width: 300px;
  }
  button {
    margin-left: 20px;
  }
}

/deep/ .el-dialog__body {
  padding: 0 20px !important;
}

.el-tabs /deep/ .el-tabs__nav-wrap::after {
  display: none !important;
}

.el-tabs /deep/ .el-tabs__active-bar {
  width: 80px !important;
  margin-left: 78px !important;
}

.importProgress {
  height: 150px;
  text-align: center;
  padding-top: 25px;
  .el-icon-loading {
    color: #659DED;
    margin-bottom: 10px;
    display: block;
  }
  p {
    margin-bottom: 10px;
  }
}

.primary {
  color: #659DED;
}
.success {
  color: #67C23A;
  .el-icon-success {
    font-size: 30px;
    display: block;
  }
}
.error {
  color: #F56C6C;
}
.cancel {
  color: #E6A23C;
  .el-icon-warning {
    font-size: 30px;
    display: block;
  }
}

.el-dialog__wrapper {
  /deep/ .el-icon-loading {
    font-size: 30px;
  }

  /deep/ .el-loading-text {
    font-size: 18px;
    margin-top: 10px;
  }
}
</style>
