<template>
  <el-dialog v-loading="loading"
             element-loading-text="资料导入中"
             element-loading-spinner="el-icon-loading"
             :visible.sync="showDialog"
             :title="'导入'+crmTypeName"
             width="550px"
             :append-to-body="true"
             @close="closeView">
    <div class="dialog-body">
      <div class="sections">
        <div>一、请按照数据模板的格式准备要导入的数据。<span class="download"
                @click="download">点击下载</span>《{{crmTypeName}}导入模板》</div>
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
          <div>请选择数据重复时的处理方式（查重规则：【{{fieldUniqueInfo}}】）</div>
        </flexbox>
        <div class="content">
          <el-select v-model="config"
                     placeholder="请选择">
            <el-option v-for="(item, index) in [{name: '覆盖系统原有数据',value: 1},{name: '跳过',value: 0}]"
                       :key="index"
                       :label="item.name"
                       :value="item.value">
            </el-option>
          </el-select>
        </div>
      </div>
      <div class="sections">
        <div>三、请选择需要导入的文件</div>
        <div class="content">
          <flexbox class="file-select">
            <el-input v-model="file.name"
                      :disabled="true"></el-input>
            <el-button type="primary"
                       @click="selectFile">选择文件</el-button>
          </flexbox>
        </div>
      </div>
      <div class="sections">
        <div>四、请选择负责人（{{crmType == 'customer' ? '如不选择，导入的客户将进入公海' : '必选'}}）</div>
        <div class="content">
          <div class="user-cell">
            <xh-user-cell :value="user"
                          @value-change="userSelect"></xh-user-cell>
          </div>
        </div>
      </div>
      <input type="file"
             id="importInputFile"
             accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
             @change="uploadFile">
    </div>
    <span slot="footer"
          class="dialog-footer">
      <el-button @click="closeView">取 消</el-button>
      <el-button type="primary"
                 @click="sure">确 定</el-button>
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

import { XhUserCell } from '@/components/CreateCom'

export default {
  name: 'c-r-m-import', // 文件导入
  components: {
    XhUserCell
  },
  data() {
    return {
      loading: false,
      showDialog: false,
      config: 1, // 	1 覆盖，0跳过
      file: { name: '' },
      user: [],
      fieldUniqueInfo: '' // 字段验重信息
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
          product: '产品'
        }[this.crmType] || ''
      )
    }
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
    sure() {
      var params = {}
      if (!this.file.name) {
        this.$message.error('请选择导入文件')
      } else if (
        this.crmType != 'customer' &&
        (!this.user || this.user.length == 0)
      ) {
        this.$message.error('请选择负责人')
      } else {
        params.config = this.config
        params.file = this.file
        params.owner_user_id = this.user.length > 0 ? this.user[0].id : ''

        var request = {
          customer: crmCustomerExcelImport,
          leads: crmLeadsExcelImport,
          contacts: crmContactsExcelImport,
          product: crmProductExcelImport
        }[this.crmType]
        this.loading = true
        request(params)
          .then(res => {
            this.loading = false
            this.$message.success(res.data)
            this.closeView()
          })
          .catch(() => {
            this.loading = false
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
          product: crmProductExcelDownloadURL
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
