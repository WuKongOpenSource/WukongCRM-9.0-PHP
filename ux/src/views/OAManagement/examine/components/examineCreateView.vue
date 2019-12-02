<template>
  <create-view
    :loading="loading"
    :body-style="{ height: '100%'}">
    <flexbox
      direction="column"
      align="stretch"
      class="crm-create-container">
      <flexbox class="crm-create-header">
        <div style="flex:1;font-size:17px;color:#333;">{{ title }}</div>
        <img
          class="close"
          src="@/assets/img/task_close.png"
          @click="hidenView" >
      </flexbox>
      <div class="crm-create-flex">
        <!-- 基本信息 -->
        <create-sections title="基本信息">
          <flexbox
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
                  :key="item.key"
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
                  <!-- 员工 和部门 为多选（radio=false）  relation 相关合同商机使用-->
                  <component
                    :is="item.data.form_type | typeToComponentName"
                    :value="item.value"
                    :index="index"
                    :item="item"
                    :relation="item.relation"
                    :radio="false"
                    :disabled="item.disabled"
                    @value-change="fieldValueChange"/>
                </el-form-item>
              </el-form>
            </div>
          </flexbox>
        </create-sections>
        <!-- 图片附件 -->
        <div class="img-accessory">
          <div class="img-box">
            <el-upload
              ref="imageUpload"
              :action="crmFileSaveUrl"
              :headers="httpHeader"
              :on-preview="handleFilePreview"
              :before-remove="beforeRemove"
              :on-success="imgFileUploadSuccess"
              :file-list="imgFileList"
              name="img[]"
              multiple
              accept="image/*"
              list-type="picture-card">
              <p class="add-img">
                <span class="el-icon-picture"/>
                <span>添加图片</span>
              </p>
              <i class="el-icon-plus"/>
            </el-upload>
          </div>
          <p class="add-accessory">
            <el-upload
              ref="fileUpload"
              :action="crmFileSaveUrl"
              :headers="httpHeader"
              :on-preview="handleFilePreview"
              :before-remove="handleFileRemove"
              :on-success="fileUploadSuccess"
              :file-list="fileList"
              name="file[]"
              multiple
              accept="*.*">
              <p>
                <img
                  src="@/assets/img/relevance_file.png"
                  alt="">
                添加附件
              </p>
            </el-upload>
          </p>
        </div>
        <!-- 关联业务 -->
        <related-business
          :selected-infos="relatedBusinessInfo"
          class="related-business"
          @value-change="relativeValueChange"/>
        <!-- 审核信息 -->
        <create-sections
          v-if="showExamine"
          title="审核信息">
          <div
            v-if="examineInfo.config===1 || examineInfo.config===0"
            slot="header"
            class="examine-type">{{ examineInfo.config===1 ? '固定审批流' : '授权审批人' }}</div>
          <create-examine-info
            ref="examineInfo"
            :types_id="category_id"
            types="oa_examine"
            @value-change="examineValueChange"/>
        </create-sections>
      </div>

      <div class="handle-bar">
        <el-button
          class="handle-button"
          @click.native="hidenView">取消</el-button>
        <el-button
          class="handle-button"
          type="primary"
          @click.native="saveField()">保存</el-button>
      </div>
    </flexbox>
  </create-view>
</template>
<script type="text/javascript">
import { filedGetField, filedValidates } from '@/api/customermanagement/common'
import { crmFileDelete, crmFileSaveUrl } from '@/api/common'
import axios from 'axios'
import { oaExamineSave, oaExamineUpdate } from '@/api/oamanagement/examine'

import CreateView from '@/components/CreateView'
import CreateSections from '@/components/CreateSections'
import CreateExamineInfo from '@/components/Examine/CreateExamineInfo'
import XhExpenses from './xhExpenses' // 报销事项
import XhLeaves from './xhLeaves' // 出差事项
import RelatedBusiness from './relatedBusiness'

import {
  regexIsCRMNumber,
  regexIsCRMMoneyNumber,
  regexIsCRMMobile,
  regexIsCRMEmail,
  formatTimeToTimestamp,
  timestampToFormatTime
} from '@/utils'
import { isArray } from '@/utils/types'

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
  CrmRelativeCell
} from '@/components/CreateCom'

export default {
  name: 'ExamineCreateView', // 所有新建效果的view
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
    XhExpenses,
    XhLeaves,
    RelatedBusiness
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
      } else if (form_type == 'select') {
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
      } else if (form_type == 'examine_cause') {
        return 'XhExpenses'
      } else if (form_type == 'business_cause') {
        return 'XhLeaves'
      }
    }
  },
  props: {
    // 类型ID
    category_id: {
      type: [String, Number],
      default: ''
    },
    // 类型名称
    category_title: {
      type: String,
      default: ''
    },
    /**
     * save:添加、update:编辑(action_id)、read:详情、index:列表
     * relative: 相关 添加
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
  data() {
    return {
      // 标题展示名称
      title: '',
      loading: false,
      // 自定义字段验证规则
      crmRules: {},
      // 自定义字段信息表单
      crmForm: {
        crmFields: []
      },
      // 图片附件
      imgFileList: [],
      fileList: [],
      // 审批信息
      examineInfo: {},
      relatedBusinessInfo: {} // 关联业务信息
    }
  },
  computed: {
    /** 审批 下展示审批人信息 */
    showExamine() {
      return true
    },
    crmFileSaveUrl() {
      return window.BASE_URL + crmFileSaveUrl
    },
    httpHeader() {
      return {
        authKey: axios.defaults.headers.authKey,
        sessionId: axios.defaults.headers.sessionId
      }
    }
  },
  watch: {},
  mounted() {
    // 获取title展示名称
    document.body.appendChild(this.$el)
    this.title = this.getTitle()
    this.getField()
  },
  destroyed() {
    // remove DOM node after destroy
    if (this.$el && this.$el.parentNode) {
      this.$el.parentNode.removeChild(this.$el)
    }
  },
  methods: {
    // 关联业务的值更新
    relativeValueChange(data) {
      this.relatedBusinessInfo = data.value
    },
    // 审批信息值更新
    examineValueChange(data) {
      this.examineInfo = data
    },
    // 字段的值更新
    fieldValueChange(data) {
      var item = this.crmForm.crmFields[data.index]
      item.value = data.value

      // 出差事项
      if (item.data.form_type == 'business_cause' && item.value.update) {
        for (let index = 0; index < this.crmForm.crmFields.length; index++) {
          const element = this.crmForm.crmFields[index]
          if (element.key === 'duration') {
            element.value = item.value.duration
            break
          }
        }
        // 报销
      } else if (item.data.form_type == 'examine_cause' && item.value.update) {
        for (let index = 0; index < this.crmForm.crmFields.length; index++) {
          const element = this.crmForm.crmFields[index]
          if (element.key === 'money') {
            element.value = item.value.money
            break
          }
        }
      }

      // 无事件的处理 后期可换成input实现
      if (
        item.data.form_type == 'user' ||
        item.data.form_type == 'structure' ||
        item.data.form_type == 'file'
      ) {
        this.$refs.crmForm.validateField('crmFields.' + data.index + '.value')
      }
    },
    // 获取自定义字段
    getField() {
      this.loading = true
      // 获取自定义字段的更新时间
      var params = {}
      params.types = 'oa_examine'
      params.module = 'oa'
      params.controller = 'examine'
      params.action = this.action.type
      params.types_id = this.category_id

      // 进行编辑操作
      if (this.action.type == 'update') {
        params.action_id = this.action.id
      }

      filedGetField(params)
        .then(res => {
          this.getcrmRulesAndModel(res.data)
          if (this.action.type == 'update') {
            this.getUpdateOtherInfo()
          }
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 更新图片附件关联业务信息
    getUpdateOtherInfo() {
      this.imgFileList = this.action.data.imgList.map(function(
        item,
        index,
        array
      ) {
        item.url = item.file_path_thumb
        return item
      })
      this.fileList = this.action.data.fileList.map(function(
        item,
        index,
        array
      ) {
        item.url = item.file_path_thumb
        return item
      })
      this.relatedBusinessInfo = {
        contacts: this.action.data.contactsList,
        customer: this.action.data.customerList,
        business: this.action.data.businessList,
        contract: this.action.data.contractList
      } // 关联业务信息
    },
    // 根据自定义字段获取自定义字段规则
    getcrmRulesAndModel(list) {
      let showStyleIndex = -1
      for (let index = 0; index < list.length; index++) {
        const item = list[index]
        showStyleIndex += 1
        /**
         *
         *
         *
         *
         *
         *
         *
         *
         *
         * 规则数据
         */
        var tempList = []
        // 验证必填
        if (item.is_null == 1) {
          if (item.form_type == 'business_cause') {
            var validateFunction = (rule, value, callback) => {
              if (!value.list) {
                this.$message.error('请完善明细')
                callback(new Error('请完善明细'))
              } else {
                var hasError = false
                for (let index = 0; index < value.list.length; index++) {
                  const item = value.list[index]
                  for (var i in item) {
                    // 备注非必填
                    if (i != 'description' && !item[i]) {
                      hasError = true
                      this.$message.error('请完善明细')
                      callback(new Error('请完善明细'))
                      break
                    }
                  }
                }
                if (!hasError) {
                  callback()
                }
              }
            }

            tempList.push({
              validator: validateFunction,
              trigger: []
            })
          } else if (item.form_type == 'examine_cause') {
            var validateFunction = (rule, value, callback) => {
              if (!value.list) {
                this.$message.error('请完善明细')
                callback(new Error('请完善明细'))
              } else {
                var hasError = false
                for (let index = 0; index < value.list.length; index++) {
                  const item = value.list[index]
                  for (var i in item) {
                    // 备注非必填
                    if (
                      i != 'description' &&
                      i != 'vehicle' &&
                      i != 'trip' &&
                      !item[i]
                    ) {
                      hasError = true
                      this.$message.error('请完善明细')
                      callback(new Error('请完善明细'))
                      break
                    }
                  }
                }
                if (!hasError) {
                  callback()
                }
              }
            }

            tempList.push({
              validator: validateFunction,
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

        // 验证唯一
        if (item.is_unique == 1) {
          var validateUnique = (rule, value, callback) => {
            if ((isArray(value) && value.length == 0) || !value) {
              callback()
            } else {
              var validatesParams = {}
              validatesParams.field = rule.item.field
              validatesParams.val = value
              validatesParams.types = 'oa_examine'
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
            trigger:
              item.form_type == 'checkbox' || item.form_type == 'select'
                ? ['change']
                : ['blur']
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

        this.crmRules[item.field] = tempList

        /**
         *
         *
         *
         *
         *
         *
         *
         *
         * 表单数据
         */
        if (item.form_type == 'datetime') {
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
        } else if (
          item.form_type == 'examine_cause' ||
          item.form_type == 'business_cause'
        ) {
          // 报销事项
          var params = {}

          if (this.action.type == 'update') {
            const list = item.value.map(function(element, index, array) {
              element.start_time =
                element.start_time && element.start_time !== 0
                  ? timestampToFormatTime(
                    element.start_time,
                    item.form_type == 'examine_cause'
                      ? 'YYYY-MM-DD'
                      : 'YYYY-MM-DD HH:mm:ss'
                  )
                  : ''
              element.end_time =
                element.end_time && element.end_time !== 0
                  ? timestampToFormatTime(
                    element.end_time,
                    item.form_type == 'examine_cause'
                      ? 'YYYY-MM-DD'
                      : 'YYYY-MM-DD HH:mm:ss'
                  )
                  : ''
              element.imgList = element.imgList.map(function(
                file,
                index,
                array
              ) {
                file.path = file.file_path_thumb
                return file
              })
              return element
            })
            params['value'] = { list: list } // 编辑的值 在value字段
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
        } else if (
          // 出差审批 差旅报销
          (item.field == 'duration' && this.category_id == 3) ||
          (item.field == 'money' && this.category_id == 5)
        ) {
          // 报销事项
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
          params['disabled'] = true // 是否可交互
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
    // 保存数据
    saveField() {
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
      /** 注入关联参数 */
      for (const key in this.relatedBusinessInfo) {
        const list = this.relatedBusinessInfo[key]
        params[key + '_ids'] = list.map(function(item, index, array) {
          return item[key + '_id']
        })
      }

      // 附件 图片
      var fileList = this.fileList.map(function(file, index, array) {
        if (file.response) {
          return file.response.data[0].file_id
        } else if (file.file_id) {
          return file.file_id
        }
        return ''
      })
      var imgFileList = this.imgFileList.map(function(file, index, array) {
        if (file.response) {
          return file.response.data[0].file_id
        } else if (file.file_id) {
          return file.file_id
        }
        return ''
      })
      params['file_id'] = fileList.concat(imgFileList)

      this.loading = true
      var crmRequest = this.getSubmiteRequest()
      if (this.action.type == 'update') {
        params.id = this.action.id
      }
      params['category_id'] = this.category_id
      crmRequest(params)
        .then(res => {
          this.loading = false
          this.hidenView()
          this.$message.success(res.data)
          // 回到保存成功
          this.$emit('save-success', {
            data: res.data
          })
        })
        .catch(() => {
          this.loading = false
        })
    },
    /** 获取上传url */
    getSubmiteRequest() {
      return this.action.type == 'update' ? oaExamineUpdate : oaExamineSave
    },
    /** 拼接上传传输 */
    getSubmiteParams(array) {
      var params = {}
      for (let index = 0; index < array.length; index++) {
        const element = array[index]
        if (element.key == 'cause') {
          if (element.data.form_type == 'business_cause') {
            var causeList = []
            for (let index = 0; index < element.value.list.length; index++) {
              const cause = element.value.list[index]
              var causeCopy = Object.assign({}, cause)

              causeCopy['start_time'] = causeCopy.start_time
                ? formatTimeToTimestamp(causeCopy.start_time)
                : causeCopy.start_time
              causeCopy['end_time'] = causeCopy.end_time
                ? formatTimeToTimestamp(causeCopy.end_time)
                : causeCopy.end_time
              causeList.push(causeCopy)
            }
            params[element.key] = causeList
            // params['duration'] = element.value.duration
          } else if (element.data.form_type == 'examine_cause') {
            var causeList = []
            for (let index = 0; index < element.value.list.length; index++) {
              const cause = element.value.list[index]
              var causeCopy = Object.assign({}, cause)

              causeCopy['start_time'] = causeCopy.start_time
                ? formatTimeToTimestamp(causeCopy.start_time)
                : causeCopy.start_time
              causeCopy['end_time'] = causeCopy.end_time
                ? formatTimeToTimestamp(causeCopy.end_time)
                : causeCopy.end_time

              var file_id = []
              if (causeCopy.imgList.length > 0) {
                file_id = causeCopy.imgList.map(function(item, index) {
                  return item.file_id
                })
              }

              delete causeCopy['imgList']

              causeCopy.file_id = file_id
              causeList.push(causeCopy)
            }
            params[element.key] = causeList
            // params['money'] = element.value.money
          }
        } else {
          const value = this.getRealParams(element)
          if (!(element.data.form_type == 'date' && !value)) {
            params[element.key] = value
          }
        }
      }
      return params
    },
    // 图片和附件
    // 上传图片
    imgFileUploadSuccess(response, file, fileList) {
      this.imgFileList = fileList
    },
    // 查看图片
    handleFilePreview(file) {
      if (file.response || file.file_id) {
        let perviewFile
        if (file.response) {
          perviewFile = {
            url: file.response.data[0].path,
            name: file.response.data[0].name
          }
        } else {
          perviewFile = {
            url: file.file_path,
            name: file.name
          }
        }
        this.$bus.emit('preview-image-bus', {
          index: 0,
          data: [perviewFile]
        })
      }
    },
    beforeRemove(file, fileList) {
      if (file.response || file.file_id) {
        let save_name
        if (file.response) {
          save_name = file.response.data[0].save_name
        } else {
          save_name = file.save_name
        }
        this.$confirm('您确定要删除该文件吗?', '提示', {
          confirmButtonText: '确定',
          cancelButtonText: '取消',
          type: 'warning'
        })
          .then(() => {
            crmFileDelete({
              save_name: save_name
            })
              .then(res => {
                this.$message.success(res.data)
                var removeIndex = this.getFileIndex(
                  this.$refs.imageUpload.uploadFiles,
                  save_name
                )
                if (removeIndex != -1) {
                  this.$refs.imageUpload.uploadFiles.splice(removeIndex, 1)
                }
                removeIndex = this.getFileIndex(this.imgFileList, save_name)
                if (removeIndex != -1) {
                  this.imgFileList.splice(removeIndex, 1)
                }
              })
              .catch(() => {})
          })
          .catch(() => {
            this.$message({
              type: 'info',
              message: '已取消操作'
            })
          })
        return false
      } else {
        return true
      }
    },
    // 附件索引
    getFileIndex(files, save_name) {
      var removeIndex = -1
      for (let index = 0; index < files.length; index++) {
        const item = files[index]
        let item_save_name
        if (item.response) {
          item_save_name = item.response.data[0].save_name
        } else {
          item_save_name = item.save_name
        }
        if (item_save_name == save_name) {
          removeIndex = index
          break
        }
      }
      return removeIndex
    },
    fileUploadSuccess(response, file, fileList) {
      this.fileList = fileList
    },
    handleFileRemove(file, fileList) {
      if (file.response || file.file_id) {
        let save_name
        if (file.response) {
          save_name = file.response.data[0].save_name
        } else {
          save_name = file.save_name
        }
        this.$confirm('您确定要删除该文件吗?', '提示', {
          confirmButtonText: '确定',
          cancelButtonText: '取消',
          type: 'warning'
        })
          .then(() => {
            crmFileDelete({
              save_name: save_name
            })
              .then(res => {
                this.$message.success(res.data)
                var removeIndex = this.getFileIndex(
                  this.$refs.fileUpload.uploadFiles,
                  save_name
                )
                if (removeIndex != -1) {
                  this.$refs.fileUpload.uploadFiles.splice(removeIndex, 1)
                }
                removeIndex = this.getFileIndex(this.fileList, save_name)
                if (removeIndex != -1) {
                  this.fileList.splice(removeIndex, 1)
                }
              })
              .catch(() => {})
          })
          .catch(() => {
            this.$message({
              type: 'info',
              message: '已取消操作'
            })
          })
        return false
      } else {
        return true
      }
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
      } else if (element.key == 'category_id') {
        if (element.value.length) {
          return element.value[element.value.length - 1]
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
      return this.action.type == 'update'
        ? '编辑' + this.category_title
        : '新建' + this.category_title
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

// 图片 附件
.img-accessory {
  padding: 0 20px;
  font-size: 12px;
  img {
    width: 16px;
    vertical-align: middle;
  }
  .img-box /deep/ .el-upload {
    width: 80px;
    height: 80px;
    line-height: 90px;
  }
  .img-box /deep/ .el-upload-list {
    .el-upload-list__item {
      width: 80px;
      height: 80px;
    }
  }
  .img-box {
    position: relative;
    margin-top: 40px;
    .add-img {
      position: absolute;
      left: 0;
      top: -30px;
      height: 20px;
      line-height: 20px;
      margin-bottom: 10px;
      color: #3e84e9;
    }
  }
  .add-accessory {
    margin-top: 25px;
    margin-bottom: 20px;
    color: #3e84e9;
  }
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

.related-business {
  padding: 0 20px;
}
</style>
