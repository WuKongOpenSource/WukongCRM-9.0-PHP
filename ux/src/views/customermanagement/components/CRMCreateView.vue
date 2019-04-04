<template>
  <create-view :loading="loading"
               :body-style="{ height: '100%'}">
    <flexbox direction="column"
             align="stretch"
             class="crm-create-container">
      <flexbox class="crm-create-header">
        <div style="flex:1;font-size:17px;color:#333;">{{title}}</div>
        <img @click="hidenView"
             class="close"
             src="@/assets/img/task_close.png" />
      </flexbox>
      <div class="crm-create-flex">
        <create-sections title="基本信息">
          <flexbox direction="column"
                   align="stretch">
            <div class="crm-create-body">
              <el-form ref="crmForm"
                       :model="crmForm"
                       label-position="top"
                       class="crm-create-box">
                <el-form-item v-for="(item, index) in this.crmForm.crmFields"
                              :key="item.key"
                              :prop="'crmFields.' + index + '.value'"
                              :class="{ 'crm-create-block-item': item.showblock, 'crm-create-item': !item.showblock }"
                              :rules="crmRules[item.key]"
                              :style="{'padding-left': getPaddingLeft(item, index), 'padding-right': getPaddingRight(item, index)}">
                  <div slot="label"
                       style="display: inline-block;">
                    <div style="margin:5px 0;font-size:12px;word-wrap:break-word;word-break:break-all;">
                      {{item.data.name}}
                      <span style="color:#999;">
                        {{item.data.input_tips ? '（'+item.data.input_tips+'）':''}}
                      </span>
                    </div>
                  </div>
                  <!-- 员工 和部门 为多选（radio=false）  relation 相关合同商机使用-->
                  <component :is="item.data.form_type | typeToComponentName"
                             :value="item.value"
                             :index="index"
                             :item="item"
                             :relation="item.relation"
                             :radio="false"
                             :disabled="item.disabled"
                             @value-change="fieldValueChange">
                  </component>
                </el-form-item>
              </el-form>
            </div>
          </flexbox>
        </create-sections>
        <create-sections v-if="showExamine"
                         title="审核信息">
          <div slot="header"
               v-if="examineInfo.config===1 || examineInfo.config===0"
               class="examine-type">{{examineInfo.config===1 ? '固定审批流' : '授权审批人'}}</div>
          <create-examine-info ref="examineInfo"
                               :types="'crm_' + crmType"
                               :types_id="action.id"
                               @value-change="examineValueChange"></create-examine-info>
        </create-sections>
      </div>

      <div class="handle-bar">
        <el-button class="handle-button"
                   @click.native="hidenView">取消</el-button>
        <el-button v-if="crmType=='customer' && action.type == 'save'"
                   class="handle-button"
                   type="primary"
                   @click.native="saveField(true)">保存并新建联系人</el-button>
        <el-button class="handle-button"
                   type="primary"
                   @click.native="saveField(false)">保存</el-button>
      </div>
    </flexbox>
  </create-view>
</template>
<script type="text/javascript">
import CreateView from '@/components/CreateView'
import CreateSections from '@/components/CreateSections'
import CreateExamineInfo from '@/components/Examine/CreateExamineInfo'
import { filedGetField, filedValidates } from '@/api/customermanagement/common'
import { crmLeadsSave, crmLeadsUpdate } from '@/api/customermanagement/clue'
import {
  crmCustomerSave,
  crmCustomerUpdate
} from '@/api/customermanagement/customer'
import {
  crmContactsSave,
  crmContactsUpdate
} from '@/api/customermanagement/contacts'
import {
  crmBusinessSave,
  crmBusinessUpdate,
  crmBusinessProduct // 商机下产品
} from '@/api/customermanagement/business'
import {
  crmContractSave,
  crmContractUpdate
} from '@/api/customermanagement/contract'
import {
  crmProductSave,
  crmProductUpdate
} from '@/api/customermanagement/product'
import {
  crmReceivablesSave,
  crmReceivablesUpdate
} from '@/api/customermanagement/money'
import { crmReceivablesPlanSave } from '@/api/customermanagement/contract'

import {
  regexIsNumber,
  regexIsCRMNumber,
  regexIsCRMMoneyNumber,
  regexIsCRMMobile,
  regexIsCRMEmail,
  formatTimeToTimestamp,
  timestampToFormatTime
} from '@/utils'

import {
  XhInput,
  XhTextarea,
  XhSelect,
  XhMultipleSelect,
  XhDate,
  XhDateTime,
  XhUserCell,
  XhStructureCell,
  XhFiles,
  CrmRelativeCell,
  XhProuctCate,
  XhProduct,
  XhBusinessStatus,
  XhCustomerAddress,
  XhReceivablesPlan // 回款计划期数
} from '@/components/CreateCom'

export default {
  name: 'crm-create-view', // 所有新建效果的view
  components: {
    CreateView,
    CreateSections,
    CreateExamineInfo, // 审核信息
    XhInput,
    XhTextarea,
    XhSelect,
    XhMultipleSelect,
    XhDate,
    XhDateTime,
    XhUserCell,
    XhStructureCell,
    XhFiles,
    CrmRelativeCell,
    XhProuctCate,
    XhProduct,
    XhBusinessStatus,
    XhCustomerAddress,
    XhReceivablesPlan
  },
  computed: {
    /** 合同 回款 下展示审批人信息 */
    showExamine() {
      if (this.crmType === 'contract' || this.crmType === 'receivables') {
        return true
      }
      return false
    }
  },
  watch: {
    crmType: function(value) {
      this.title = this.getTitle()
      this.crmRules = {}
      this.crmForm = {
        crmFields: []
      }
      this.examineInfo = {}
      this.getField()
    }
  },
  data() {
    return {
      // 标题展示名称
      title: '',
      loading: false,
      saveAndCreate: false, // 保存并新建
      // 自定义字段验证规则
      crmRules: {},
      // 自定义字段信息表单
      crmForm: {
        crmFields: []
      },
      // 审批信息
      examineInfo: {}
    }
  },
  filters: {
    /** 根据type 找到组件 */
    typeToComponentName(form_type) {
      if (
        form_type == 'text' ||
        form_type == 'number' ||
        form_type == 'floatnumber' ||
        form_type == 'mobile' ||
        form_type == 'email'
      ) {
        return 'XhInput'
      } else if (form_type == 'textarea') {
        return 'XhTextarea'
      } else if (form_type == 'select' || form_type == 'business_status') {
        return 'XhSelect'
      } else if (form_type == 'checkbox') {
        return 'XhMultipleSelect'
      } else if (form_type == 'date') {
        return 'XhDate'
      } else if (form_type == 'datetime') {
        return 'XhDateTime'
      } else if (form_type == 'user') {
        return 'XhUserCell'
      } else if (form_type == 'structure') {
        return 'XhStructureCell'
      } else if (form_type == 'file') {
        return 'XhFiles'
      } else if (
        form_type == 'contacts' ||
        form_type == 'customer' ||
        form_type == 'contract' ||
        form_type == 'business'
      ) {
        return 'CrmRelativeCell'
      } else if (form_type == 'category') {
        // 产品类别
        return 'XhProuctCate'
      } else if (form_type == 'business_type') {
        // 商机类别
        return 'XhBusinessStatus'
      } else if (form_type == 'product') {
        return 'XhProduct'
      } else if (form_type == 'map_address') {
        return 'XhCustomerAddress'
      } else if (form_type == 'receivables_plan') {
        return 'XhReceivablesPlan'
      }
    }
  },
  props: {
    // CRM类型
    crmType: {
      type: String,
      default: ''
    },
    /**
     * save:添加、update:编辑(action_id)、read:详情、index:列表
     * relative: 相关 添加(目前用于客户等相关添加)
     */
    action: {
      type: Object,
      default: () => {
        return {
          type: 'save',
          id: ''
        }
      }
    }
  },
  mounted() {
    // 获取title展示名称
    document.body.appendChild(this.$el)
    this.title = this.getTitle()
    this.getField()
  },
  methods: {
    // 审批信息值更新
    examineValueChange(data) {
      this.examineInfo = data
    },
    // 字段的值更新
    fieldValueChange(data) {
      var item = this.crmForm.crmFields[data.index]
      item.value = data.value
      //商机下处理商机状态
      if (
        this.crmType == 'business' &&
        item.data.form_type == 'business_type'
      ) {
        //找到阶段数据
        for (
          let statusIndex = 0;
          statusIndex < this.crmForm.crmFields.length;
          statusIndex++
        ) {
          const statusElement = this.crmForm.crmFields[statusIndex]
          if (statusElement.data.form_type == 'business_status') {
            for (let typeIndex = 0; typeIndex < data.data.length; typeIndex++) {
              const typeElement = data.data[typeIndex]
              if (typeElement.type_id == data.value) {
                statusElement.data.setting = typeElement.statusList.map(
                  function(item, index) {
                    item['value'] = item.status_id
                    return item
                  }
                )
                statusElement.value = ''
                this.$set(this.crmForm.crmFields, statusIndex, statusElement)
                break
              }
            }
          }
        }
      } else if (this.crmType == 'contract') {
        if (item.data.form_type == 'customer') {
          // 新建合同 选择客户 要将id交于 商机
          for (let index = 0; index < this.crmForm.crmFields.length; index++) {
            const element = this.crmForm.crmFields[index]
            if (element.key === 'business_id') {
              // 如果是商机 改变商机样式和传入客户ID
              if (item.value.length > 0) {
                element.disabled = false
                var customerItem = item.value[0]
                customerItem['form_type'] = 'customer'
                element['relation'] = customerItem
              } else {
                element.disabled = true
                element['relation'] = {}
                element.value = []
              }
              break
            }
          }
        } else if (item.data.form_type == 'business') {
          if (item.value.length > 0) {
            crmBusinessProduct({
              business_id: item.value[0].business_id
            })
              .then(res => {
                for (
                  let index = 0;
                  index < this.crmForm.crmFields.length;
                  index++
                ) {
                  const element = this.crmForm.crmFields[index]
                  if (element.key === 'product') {
                    element['value'] = {
                      product: res.data.list,
                      total_price: res.data.total_price,
                      discount_rate: res.data.discount_rate
                    }
                    break
                  }
                }
              })
              .catch(() => {})
          }
        }
      } else if (this.crmType == 'receivables') {
        // 新建回款 选择客户 要将id交于 合同
        if (item.data.form_type == 'customer') {
          var planItem = null // 合同更改 重置回款计划
          for (let index = 0; index < this.crmForm.crmFields.length; index++) {
            const element = this.crmForm.crmFields[index]
            if (element.key === 'contract_id') {
              // 如果是合同 改变合同样式和传入客户ID
              if (item.value.length > 0) {
                element.disabled = false
                var customerItem = item.value[0]
                customerItem['form_type'] = 'customer'
                customerItem['params'] = { check_status: 2 }
                element['relation'] = customerItem
              } else {
                element.disabled = true
                element['relation'] = {}
                element.value = []
              }
            } else if (element.key === 'plan_id') {
              planItem = element
            }
          }
          if (planItem) {
            planItem.disabled = true
            planItem['relation'] = {}
            planItem.value = ''
          }
        } else if (item.data.form_type == 'contract') {
          for (let index = 0; index < this.crmForm.crmFields.length; index++) {
            const element = this.crmForm.crmFields[index]
            if (element.key === 'plan_id') {
              // 如果是回款 改变回款样式和传入客户ID
              if (item.value.length > 0) {
                element.disabled = false
                var contractItem = item.value[0]
                contractItem['form_type'] = 'contract'
                element['relation'] = contractItem
              } else {
                element.disabled = true
                element['relation'] = {}
                element.value = ''
              }
              break
            }
          }
        }
      }

      //无事件的处理 后期可换成input实现
      if (
        item.data.form_type == 'user' ||
        item.data.form_type == 'structure' ||
        item.data.form_type == 'file' ||
        item.data.form_type == 'category' ||
        item.data.form_type == 'customer' ||
        item.data.form_type == 'business' ||
        item.data.form_type == 'contract'
      ) {
        this.$refs.crmForm.validateField('crmFields.' + data.index + '.value')
      }
    },
    // 获取自定义字段
    getField() {
      this.loading = true
      // 获取自定义字段的更新时间
      var params = {}
      params.types = 'crm_' + this.crmType
      params.module = 'crm'
      params.controller = this.crmType
      params.action =
        this.action.type === 'relative' ? 'save' : this.action.type
      // 进行编辑操作
      if (this.action.type == 'update') {
        params.action_id = this.action.id
      }

      filedGetField(params)
        .then(res => {
          this.getcrmRulesAndModel(res.data)
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 根据自定义字段获取自定义字段规则
    getcrmRulesAndModel(list) {
      let showStyleIndex = -1
      for (let index = 0; index < list.length; index++) {
        const item = list[index]
        showStyleIndex += 1
        /**
         * 规则数据
         */

        this.crmRules[item.field] = this.getItemRulesArrayFromItem(item)

        /**
         * 表单数据
         */
        if (
          // crm相关信息特殊处理
          item.form_type == 'contacts' ||
          item.form_type == 'customer' ||
          item.form_type == 'contract' ||
          item.form_type == 'business' ||
          item.form_type == 'receivables_plan'
        ) {
          var params = {}
          params['key'] = item.field
          params['data'] = item
          // 获取 value relative 信息
          this.getParamsValueAndRelativeInfo(params, item, list)
          params['disabled'] = this.getItemDisabledFromItem(item)
          params['styleIndex'] = showStyleIndex
          this.crmForm.crmFields.push(params)
        } else if (item.form_type == 'category') {
          /** 产品分类 */
          var params = {}
          params['key'] = item.field
          params['data'] = item
          if (this.action.type == 'update' && item.value) {
            params['value'] = item.value
              ? item.value.map(function(item, index, array) {
                  return parseInt(item)
                })
              : []
          } else {
            params['value'] = []
          }
          params['disabled'] = false // 是否可交互
          params['styleIndex'] = showStyleIndex
          this.crmForm.crmFields.push(params)
        } else if (item.form_type == 'product') {
          // 关联产品信息比较多 用字典接收
          var params = {}
          params['value'] = item.value
          params['key'] = item.field
          params['data'] = item
          params['disabled'] = false // 是否可交互
          params['showblock'] = true // 展示整行效果
          if (index % 2 == 0) {
            showStyleIndex = -1
          }
          this.crmForm.crmFields.push(params)
        } else if (item.form_type == 'map_address') {
          // 关联产品信息比较多 用字典接收
          var params = {}

          if (this.action.type == 'update') {
            params['value'] = item.value // 编辑的值 在value字段
          } else {
            params['value'] = {} // 加入默认值 可能编辑的时候需要调整
          }
          params['key'] = item.field
          params['data'] = item
          params['disabled'] = false // 是否可交互
          params['showblock'] = true // 展示整行效果
          if (index % 2 == 0) {
            showStyleIndex = -1
          }
          this.crmForm.crmFields.push(params)
        } else if (item.form_type == 'datetime') {
          // 返回的时间戳  要处理为格式化时间（编辑的时候）
          // 关联产品信息比较多 用字典接收
          var params = {}

          if (this.action.type == 'update') {
            params['value'] =
              item.value && item.value !== 0
                ? timestampToFormatTime(item.value, 'YYYY-MM-DD HH:mm:ss')
                : '' // 编辑的值 在value字段
          } else {
            params['value'] = item.default_value // 加入默认值 可能编辑的时候需要调整
          }

          params['key'] = item.field
          params['data'] = item
          params['disabled'] = false // 是否可交互
          params['styleIndex'] = showStyleIndex
          this.crmForm.crmFields.push(params)
        } else {
          var params = {}
          if (this.action.type == 'update') {
            params['value'] = item.value // 编辑的值 在value字段
          } else {
            params['value'] = item.default_value
              ? item.default_value
              : item.value // 加入默认值 可能编辑的时候需要调整
          }
          params['key'] = item.field
          params['data'] = item
          params['disabled'] = false // 是否可交互
          params['styleIndex'] = showStyleIndex
          this.crmForm.crmFields.push(params)
        }
      }
    },
    /**
     * 获取关联项的值 和 关联信息
     */
    getParamsValueAndRelativeInfo(params, item, list) {
      if (this.action.type == 'relative') {
        let relativeData = this.action.data[item.form_type]
        if (item.form_type == 'receivables_plan') {
          params['value'] = ''
        } else {
          params['value'] = relativeData ? [relativeData] : []
        }
      } else {
        params['value'] = item.value
      }
      if (this.action.type == 'relative' || this.action.type == 'update') {
        // 回款计划 需要合同信息
        if (item.form_type === 'receivables_plan') {
          let contractItem = this.getItemRelatveInfo(item, list, 'contract')
          if (contractItem) {
            contractItem['form_type'] = 'contract'
            params['relation'] = contractItem
          }
          // 商机合同 需要客户信息
        } else if (
          item.form_type == 'business' ||
          item.form_type == 'contract'
        ) {
          let customerItem = this.getItemRelatveInfo(item, list, 'customer')
          if (item.form_type == 'business' && customerItem) {
            customerItem['form_type'] = 'customer'
            params['relation'] = customerItem
          } else if (item.form_type == 'contract' && customerItem) {
            customerItem['form_type'] = 'customer'
            customerItem['params'] = { check_status: 2 }
            params['relation'] = customerItem
          }
        }
      }
    },
    /**
     * 获取相关联item
     */
    getItemRelatveInfo(item, list, from_type) {
      let crmItem = null
      if (this.action.type == 'relative') {
        crmItem = this.action.data[from_type]
      } else {
        let crmObj = list.find(listItem => {
          return listItem.form_type === from_type
        })
        if (crmObj && crmObj.value && crmObj.value.length > 0) {
          crmItem = crmObj.value[0]
        }
      }
      return crmItem
    },
    /**
     * 获取关联项是否可操作
     */
    getItemDisabledFromItem(item) {
      // 相关添加
      if (this.action.type == 'relative') {
        let relativeDisInfos = {
          business: {
            customer: { customer: true },
            contacts: { customer: true }
          },
          contacts: {
            customer: { customer: true }
          },
          contract: {
            customer: { customer: true },
            business: { customer: true, business: true }
          },
          receivables_plan: {
            contract: { customer: true, contract: true },
            customer: { customer: true }
          },
          receivables: {
            contract: { customer: true, contract: true },
            customer: { customer: true }
          }
        }
        // 添加类型
        let crmTypeDisInfos = relativeDisInfos[this.crmType]
        if (crmTypeDisInfos) {
          // 在哪个类型下添加
          let relativeTypeDisInfos = crmTypeDisInfos[this.action.crmType]
          if (relativeTypeDisInfos) {
            // 包含的字段值
            return relativeTypeDisInfos[item.form_type] || false
          }
        }
        return false
      } else if (this.action.type != 'update') {
        // 新建
        if (this.crmType === 'contract' && item.form_type === 'business') {
          return true
          // 回款下 新建 合同 和 回款计划 默认不能操作
        } else if (this.crmType === 'receivables') {
          if (item.form_type === 'contract') {
            return true
          } else if (item.form_type === 'receivables_plan') {
            return true
          }
        }
      }
      return false
    },
    /**
     * item 当行数据源
     */
    getItemRulesArrayFromItem(item) {
      var tempList = []

      //验证必填
      if (item.is_null == 1) {
        if (item.form_type == 'category') {
          tempList.push({
            required: true,
            message: item.name + '不能为空',
            trigger: []
          })
        } else {
          tempList.push({
            required: true,
            message: item.name + '不能为空',
            trigger: ['blur', 'change']
          })
        }
      }

      //验证唯一
      if (item.is_unique == 1) {
        var validateUnique = (rule, value, callback) => {
          if (!value && rule.item.is_null == 0) {
            callback()
          } else {
            var validatesParams = {}
            validatesParams.field = rule.item.field
            validatesParams.val = value
            validatesParams.types = 'crm_' + this.crmType
            if (this.action.type == 'update') {
              validatesParams.id = this.action.id
            }
            filedValidates(validatesParams)
              .then(res => {
                callback()
              })
              .catch(error => {
                callback(new Error(error.error ? error.error : '验证出错'))
              })
          }
        }
        tempList.push({
          validator: validateUnique,
          item: item,
          trigger: ['blur']
        })
      }

      // 特殊字符
      if (item.form_type == 'number') {
        var validateCRMNumber = (rule, value, callback) => {
          if (!value || value == '' || regexIsCRMNumber(value)) {
            callback()
          } else {
            callback(new Error('数字的整数部分须少于12位，小数部分须少于4位'))
          }
        }
        tempList.push({
          validator: validateCRMNumber,
          item: item,
          trigger: ['blur']
        })
      } else if (item.form_type == 'floatnumber') {
        var validateCRMMoneyNumber = (rule, value, callback) => {
          if (!value || value == '' || regexIsCRMMoneyNumber(value)) {
            callback()
          } else {
            callback(new Error('货币的整数部分须少于10位，小数部分须少于2位'))
          }
        }
        tempList.push({
          validator: validateCRMMoneyNumber,
          item: item,
          trigger: ['blur']
        })
      } else if (item.form_type == 'mobile') {
        var validateCRMMobile = (rule, value, callback) => {
          if (!value || value == '' || regexIsCRMMobile(value)) {
            callback()
          } else {
            callback(new Error('手机格式有误'))
          }
        }
        tempList.push({
          validator: validateCRMMobile,
          item: item,
          trigger: ['blur']
        })
      } else if (item.form_type == 'email') {
        var validateCRMEmail = (rule, value, callback) => {
          if (!value || value == '' || regexIsCRMEmail(value)) {
            callback()
          } else {
            callback(new Error('邮箱格式有误'))
          }
        }
        tempList.push({
          validator: validateCRMEmail,
          item: item,
          trigger: ['blur']
        })
      }
      return tempList
    },
    // 保存数据
    saveField(saveAndCreate) {
      this.saveAndCreate = saveAndCreate
      this.$refs.crmForm.validate(valid => {
        if (valid) {
          if (this.showExamine) {
            /** 验证审批数据 */
            this.$refs.examineInfo.validateField(() => {
              var params = this.getSubmiteParams(this.crmForm.crmFields)
              if (this.examineInfo.config === 0) {
                params['check_user_id'] = this.examineInfo.value[0].id
              }
              this.submiteParams(params)
            })
          } else {
            var params = this.getSubmiteParams(this.crmForm.crmFields)
            this.submiteParams(params)
          }
        } else {
          return false
        }
      })
    },
    /** 上传 */
    submiteParams(params) {
      this.loading = true
      var crmRequest = this.getSubmiteRequest()
      if (this.action.type == 'update') {
        params.id = this.action.id
      }
      crmRequest(params)
        .then(res => {
          this.loading = false
          if (this.crmType == 'customer') {
            if (!this.saveAndCreate) {
              this.$message.success(
                this.action.type == 'update' ? '编辑成功' : '添加成功'
              )
              this.hidenView()
            }
          } else {
            this.hidenView()
            this.$message.success(res.data)
          }
          // 回到保存成功
          this.$emit('save-success', {
            type: this.crmType,
            data: res.data,
            saveAndCreate: this.saveAndCreate
          })
        })
        .catch(() => {
          this.loading = false
        })
    },
    /** 获取上传url */
    getSubmiteRequest() {
      if (this.crmType == 'leads') {
        return this.action.type == 'update' ? crmLeadsUpdate : crmLeadsSave
      } else if (this.crmType == 'customer') {
        return this.action.type == 'update'
          ? crmCustomerUpdate
          : crmCustomerSave
      } else if (this.crmType == 'contacts') {
        return this.action.type == 'update'
          ? crmContactsUpdate
          : crmContactsSave
      } else if (this.crmType == 'business') {
        return this.action.type == 'update'
          ? crmBusinessUpdate
          : crmBusinessSave
      } else if (this.crmType == 'product') {
        return this.action.type == 'update' ? crmProductUpdate : crmProductSave
      } else if (this.crmType == 'contract') {
        return this.action.type == 'update'
          ? crmContractUpdate
          : crmContractSave
      } else if (this.crmType == 'receivables') {
        return this.action.type == 'update'
          ? crmReceivablesUpdate
          : crmReceivablesSave
      } else if (this.crmType == 'receivables_plan') {
        // 回款计划 不能编辑
        return crmReceivablesPlanSave
      }
    },
    /** 拼接上传传输 */
    getSubmiteParams(array) {
      var params = {}
      for (let index = 0; index < array.length; index++) {
        const element = array[index]
        // 关联产品数据需要特殊拼接
        if (element.key == 'product') {
          this.getProductParams(params, element)
        } else if (element.key == 'customer_address') {
          // 位置信息需要注入多个字段
          this.getCustomerAddressParams(params, element)
        } else {
          let value = this.getRealParams(element)
          if (!(element.data.form_type == 'date' && !value)) {
            params[element.key] = value
          }
        }
      }
      return params
    },
    getProductParams(params, element) {
      params['product'] = element.value.product
      params['total_price'] = element.value.total_price
        ? element.value.total_price
        : 0
      params['discount_rate'] = element.value.discount_rate
        ? element.value.discount_rate
        : 0
    },
    // 获取客户位置参数
    getCustomerAddressParams(params, element) {
      params['address'] = element.value.address
      params['detail_address'] = element.value.detail_address
      params['location'] = element.value.location
      params['lng'] = element.value.lng
      params['lat'] = element.value.lat
    },
    // 关联客户 联系人等数据要特殊处理
    getRealParams(element) {
      if (
        element.key == 'customer_id' ||
        element.key == 'contacts_id' ||
        element.key == 'business_id' ||
        element.key == 'leads_id' ||
        element.key == 'contract_id'
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
        return element.value.map(function(item, index, array) {
          return item.id
        })
      } else if (element.data.form_type == 'file') {
        return element.value.map(function(item, index, array) {
          return item.file_id
        })
      } else if (element.data.form_type == 'datetime') {
        // datetime 时间戳 date 格式化时间
        return element.value
          ? formatTimeToTimestamp(element.value)
          : element.value
      }

      return element.value
    },
    hidenView() {
      this.$emit('hiden-view')
    },
    // 根据类型获取标题展示名称
    getTitle() {
      if (this.crmType == 'leads') {
        return this.action.type == 'update' ? '编辑线索' : '新建线索'
      } else if (this.crmType == 'customer') {
        return this.action.type == 'update' ? '编辑客户' : '新建客户'
      } else if (this.crmType == 'contacts') {
        return this.action.type == 'update' ? '编辑联系人' : '新建联系人'
      } else if (this.crmType == 'business') {
        return this.action.type == 'update' ? '编辑商机' : '新建商机'
      } else if (this.crmType == 'product') {
        return this.action.type == 'update' ? '编辑产品' : '新建产品'
      } else if (this.crmType == 'contract') {
        return this.action.type == 'update' ? '编辑合同' : '新建合同'
      } else if (this.crmType == 'receivables') {
        return this.action.type == 'update' ? '编辑回款' : '新建回款'
      } else if (this.crmType == 'receivables_plan') {
        return this.action.type == 'update' ? '编辑回款计划' : '新建回款计划'
      }
    },
    // 获取左边padding
    getPaddingLeft(item, index) {
      if (item.showblock && item.showblock == true) {
        return '0'
      }
      return item.styleIndex % 2 == 0 ? '0' : '25px'
    },
    // 获取左边padding
    getPaddingRight(item, index) {
      if (item.showblock && item.showblock == true) {
        return '0'
      }

      return item.styleIndex % 2 == 0 ? '25px' : '0'
    }
  },
  destroyed() {
    // remove DOM node after destroy
    if (this.$el && this.$el.parentNode) {
      this.$el.parentNode.removeChild(this.$el)
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
    cursor: pointer;
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
  // overflow: hidden;
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

// 审核信息 里的审核类型
.examine-type {
  font-size: 12px;
  color: white;
  background-color: #fd715a;
  padding: 0 8px;
  margin-left: 5px;
  height: 16px;
  line-height: 16px;
  border-radius: 8px;
  transform: scale(0.8, 0.8);
}
</style>
