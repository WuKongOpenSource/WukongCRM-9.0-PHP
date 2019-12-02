<template>
  <create-view
    :loading="loading"
    :body-style="{ height: '100%'}">
    <flexbox
      direction="column"
      align="stretch"
      class="crm-create-container">
      <flexbox class="crm-create-header">
        <div style="flex:1;font-size:17px;color:#333;">{{ name }}</div>
        <img
          class="close"
          src="@/assets/img/task_close.png"
          @click="hidenView" >
      </flexbox>
      <flexbox
        class="crm-create-flex"
        direction="column"
        align="stretch">
        <div class="crm-create-body">
          <el-form
            ref="crmForm"
            :model="crmForm"
            label-position="top"
            class="crm-create-box">
            <el-form-item
              v-for="(item, index) in crmForm.crmFields"
              :key="'item'+index"
              :prop="'crmFields.' + index + '.value'"
              :class="{ 'crm-create-block-item': item.showblock, 'crm-create-item': !item.showblock }"
              :rules="crmRules[item.key]"
              :style="{'padding-left': getPaddingLeft(item, index), 'padding-right': getPaddingRight(item, index)}">
              <div
                slot="label"
                style="display: inline-block;">
                <div style="margin:5px 0;font-size:12px;word-wrap:break-word;word-break:break-all;">
                  {{ item.data.name }}
                  <span style="color:#999;">
                    {{ item.data.input_tips ? '（'+item.data.input_tips+'）':'' }}
                  </span>
                </div>
              </div>
              <component
                :is="item.data.form_type | typeToComponentName"
                :value="item.value"
                :index="index"
                :item="item"
                :disabled="item.disabled"
                @value-change="fieldValueChange"/>
            </el-form-item>
          </el-form>
        </div>
      </flexbox>
      <div class="handle-bar">
        <el-button
          class="handle-button"
          @click.native="hidenView">取消</el-button>
        <el-button
          class="handle-button"
          type="primary"
          @click.native="saveField">保存</el-button>
      </div>
    </flexbox>
  </create-view>
</template>
<script type="text/javascript">
import { crmReceivablesPlanSave } from '@/api/customermanagement/contract'

import CreateView from '@/components/CreateView'
import {
  XhInput,
  XhTextarea,
  XhSelect,
  XhDate,
  CrmRelativeCell,
  XhFiles
} from '@/components/CreateCom'
import { formatTimeToTimestamp, timestampToFormatTime } from '@/utils'

export default {
  name: 'MoneyPlanCreate', // 回款计划新建
  components: {
    CreateView,
    XhInput,
    XhTextarea,
    XhSelect,
    XhDate,
    XhFiles,
    CrmRelativeCell
  },
  filters: {
    /** 根据type 找到组件 */
    typeToComponentName(form_type) {
      if (form_type == 'text') {
        return 'XhInput'
      } else if (form_type == 'textarea') {
        return 'XhTextarea'
      } else if (form_type == 'select') {
        return 'XhSelect'
      } else if (form_type == 'date') {
        return 'XhDate'
      } else if (form_type == 'file') {
        return 'XhFiles'
      } else if (form_type == 'customer') {
        return 'CrmRelativeCell'
      } else if (form_type == 'contract') {
        return 'CrmRelativeCell'
      }
    }
  },
  props: {
    // CRM类型
    crmType: {
      type: String,
      default: ''
    },
    /** 模块ID */
    id: [String, Number],
    action: {
      type: Object,
      default: () => {
        return {
          type: 'save',
          params: {}
        }
      }
    }
  },
  data() {
    return {
      name: '',
      loading: false,
      // 自定义字段验证规则
      crmRules: {},
      // 自定义字段信息表单
      crmForm: {
        crmFields: []
      }
    }
  },
  computed: {},
  mounted() {
    document.body.appendChild(this.$el)
    if (this.action.type == 'update') {
      this.name = '编辑回款计划'
      this.getField(this.action.data)
    } else {
      this.name = '新建回款计划'
      this.getField()
    }
  },
  destroyed() {
    // remove DOM node after destroy
    if (this.$el && this.$el.parentNode) {
      this.$el.parentNode.removeChild(this.$el)
    }
  },
  methods: {
    // 字段的值更新
    fieldValueChange(data) {
      var item = this.crmForm.crmFields[data.index]
      item.value = data.value
    },
    // 获取自定义字段
    getField(data) {
      var field = [
        {
          field: 'customer_id',
          form_type: 'customer',
          is_null: 0,
          name: '客户名称',
          setting: [],
          input_tips: '',
          value: []
        },
        {
          field: 'contract_id',
          form_type: 'contract',
          is_null: 1,
          name: '合同编号',
          setting: [],
          id: '', // 用于关联客户id
          input_tips: '请先选择客户',
          value: []
        },
        {
          field: 'money',
          form_type: 'text',
          is_null: 0,
          name: '计划回款金额',
          setting: [],
          input_tips: '',
          value: data ? data.money : ''
        },
        {
          field: 'return_date',
          form_type: 'date',
          is_null: 0,
          name: '计划回款日期',
          setting: [],
          input_tips: '',
          value: data
            ? timestampToFormatTime(data.return_date, 'YYYY-MM-DD')
            : ''
        },
        {
          field: 'return_type',
          form_type: 'select',
          is_null: 1,
          name: '计划回款方式',
          setting: ['支付宝', '微信', '银行转账'],
          input_tips: '',
          value: data ? data.return_type : ''
        },
        {
          field: 'remind',
          form_type: 'text',
          is_null: 0,
          name: '提前几日提醒',
          setting: [],
          input_tips: '',
          value: data ? data.remind : ''
        },
        {
          field: 'remark',
          form_type: 'textarea',
          is_null: 0,
          name: '备注',
          setting: [],
          input_tips: '',
          value: data ? data.remark : ''
        },
        {
          field: 'file_ids',
          form_type: 'file',
          is_null: 0,
          name: '附件',
          setting: [],
          input_tips: '',
          value: data ? data.fileList : []
        }
      ]

      this.getcrmRulesAndModel(field)
    },
    // 根据自定义字段获取自定义字段规则
    getcrmRulesAndModel(list) {
      for (let index = 0; index < list.length; index++) {
        const item = list[index]
        /** 规则数据 */
        var tempList = []

        // 验证必填
        if (item.is_null == 1) {
          tempList.push({
            required: true,
            message: item.name + '不能为空',
            trigger: ['blur', 'change']
          })
        }
        this.crmRules[item.field] = tempList

        var params = {}
        params['value'] = item.value // 加入默认值 可能编辑的时候需要调整
        params['key'] = item.field
        params['data'] = item
        // 合同下新建回款计划客户合同信息都有
        if (this.crmType === 'contract') {
          if (item.form_type === 'customer') {
            params['value'] = [this.action.params.customer]
            params['disabled'] = true
          }
          if (item.form_type === 'contract') {
            params['value'] = [this.action.params.contract]
            params['disabled'] = true
          }
          // 客户下新建包含客户信息 默认禁止点击合同
        } else if (this.crmType === 'customer') {
          if (item.form_type === 'customer') {
            params['value'] = [this.action.params.customer]
            params['disabled'] = true
          }

          // 注入客户ID
          if (item.form_type === 'contract') {
            item['relation_id'] = this.action.params.customer.customer_id
            params['data'] = item
          }
        }

        this.crmForm.crmFields.push(params)
      }
    },
    // 保存数据
    saveField() {
      this.$refs.crmForm.validate(valid => {
        if (valid) {
          this.submiteParams(this.crmForm.crmFields)
        } else {
          return false
        }
      })
    },
    /** 上传 */
    submiteParams(array) {
      var params = this.getSubmiteParams(array)
      this.loading = true
      crmReceivablesPlanSave(params)
        .then(res => {
          this.loading = false
          this.hidenView()
          // 回到保存成功
          this.$emit('save')
        })
        .catch(() => {
          this.loading = false
        })
    },
    /** 拼接上传传输 */
    getSubmiteParams(array) {
      var params = {}
      for (let index = 0; index < array.length; index++) {
        const element = array[index]
        params[element.key] = this.getRealParams(element)
      }
      return params
    },
    // 部分数据要特殊处理
    getRealParams(element) {
      if (
        element.key == 'customer_id' ||
        element.key == 'contacts_id' ||
        element.key == 'contract_id' ||
        element.key == 'business_id' ||
        element.key == 'leads_id'
      ) {
        if (element.value.length) {
          return element.value[0][element.key]
        } else {
          return ''
        }
      } else if (
        element.data.form_type == 'user' ||
        element.data.form_type == 'structure'
      ) {
        if (element.value.length > 0) {
          return element.value[0].id
        } else {
          return ''
        }
      } else if (element.data.form_type == 'date') {
        if (element.value) {
          return formatTimeToTimestamp(element.value)
        }
        return ''
      } else if (element.data.form_type == 'file') {
        if (element.value.length > 0) {
          var temps = []
          for (let index = 0; index < element.value.length; index++) {
            const file = element.value[index]
            if (file.isNewUpload && file.isNewUpload == true) {
              temps.push(file.file_id)
            }
          }
          return temps
        }
        return []
      }

      return element.value
    },
    hidenView() {
      this.$emit('close')
    },
    // 获取左边padding
    getPaddingLeft(item, index) {
      if (item.showblock && item.showblock == true) {
        return '0'
      }
      return index % 2 == 0 ? '0' : '25px'
    },
    // 获取左边padding
    getPaddingRight(item, index) {
      if (item.showblock && item.showblock == true) {
        return '0'
      }
      return index % 2 == 0 ? '25px' : '0'
    }
  }
}
</script>
<style lang="scss" scoped>
.crm-create-container {
  position: relative;
  height: 100%;
}

.crm-create-flex {
  position: relative;
  overflow-x: hidden;
  overflow-y: auto;
  flex: 1;
}

.crm-create-header {
  height: 40px;
  margin-bottom: 20px;
  padding: 0 10px;
  flex-shrink: 0;
  .close {
    display: block;
    width: 40px;
    height: 40px;
    margin-right: -10px;
    padding: 10px;
  }
}

.crm-create-body {
  flex: 1;
  overflow-x: hidden;
  overflow-y: auto;
}

/** 将其改变为flex布局 */
.crm-create-box {
  display: flex;
  flex-wrap: wrap;
  padding: 0 10px;
}

.crm-create-item {
  flex: 0 0 50%;
  flex-shrink: 0;
  padding-bottom: 10px;
}

// 占用一整行
.crm-create-block-item {
  flex: 0 0 100%;
  flex-shrink: 0;
  padding-bottom: 10px;
}

.el-form-item /deep/ .el-form-item__label {
  line-height: normal;
  font-size: 13px;
  color: #333333;
  position: relative;
  padding-left: 8px;
  padding-bottom: 0;
}

.el-form /deep/ .el-form-item {
  margin-bottom: 0px;
}

.el-form /deep/ .el-form-item.is-required .el-form-item__label:before {
  content: '*';
  color: #f56c6c;
  margin-right: 4px;
  position: absolute;
  left: 0;
  top: 5px;
}

.handle-bar {
  position: relative;
  .handle-button {
    float: right;
    margin-top: 5px;
    margin-right: 20px;
  }
}
</style>
