<template>
  <div class="cr-body-content">
    <flexbox class="content-header">
      <div v-if="!isRelationShow">场景：</div>
      <el-dropdown v-if="!isRelationShow"
                   trigger="click"
                   @command="handleTypeDrop">
        <flexbox>
          <div>{{sceneInfo ? sceneInfo.name : '请选择'}}</div>
          <i class="el-icon-arrow-down el-icon--right"
             style="color:#777;"></i>
        </flexbox>
        <el-dropdown-menu slot="dropdown">
          <el-dropdown-item v-for="(item, index) in scenesList"
                            :key="index"
                            :command="item ">{{item.name}}</el-dropdown-item>
        </el-dropdown-menu>
      </el-dropdown>
      <el-input class="search-container"
                v-model="searchContent">
        <el-button slot="append"
                   @click.native="searchInput"
                   icon="el-icon-search"></el-button>
      </el-input>
      <el-button class="create-button"
                 @click="isCreate=true"
                 type="primary">新建</el-button>
    </flexbox>
    <el-table class="cr-table"
              ref="relativeTable"
              :data="list"
              v-loading="loading"
              :height="250"
              stripe
              border
              highlight-current-row
              style="width: 100%"
              @select-all="selectAll"
              @selection-change="handleSelectionChange"
              @row-click="handleRowClick">
      <el-table-column show-overflow-tooltip
                       type="selection"
                       align="center"
                       width="55"></el-table-column>
      <el-table-column v-for="(item, index) in fieldList"
                       :key="index"
                       :fixed="index===0"
                       show-overflow-tooltip
                       :prop="item.prop"
                       :label="item.label"
                       :width="150"
                       :formatter="fieldFormatter"></el-table-column>
      <el-table-column></el-table-column>
    </el-table>
    <div class="table-footer">
      <el-button @click.native="changePage('up')"
                 :disabled="currentPage <= 1">上一页</el-button>
      <el-button @click.native="changePage('down')"
                 :disabled="currentPage >= totalPage">下一页</el-button>
    </div>
    <c-r-m-create-view v-if="isCreate"
                       :crm-type="crmType"
                       @save-success="getList"
                       @hiden-view="isCreate=false"></c-r-m-create-view>
  </div>
</template>
<script type="text/javascript">
import { crmLeadsIndex } from '@/api/customermanagement/clue'
import { crmCustomerIndex } from '@/api/customermanagement/customer'
import { crmContactsIndex } from '@/api/customermanagement/contacts'
import { crmBusinessIndex } from '@/api/customermanagement/business'
import { crmContractIndex } from '@/api/customermanagement/contract'
import { crmProductIndex } from '@/api/customermanagement/product'
import { crmSceneIndex } from '@/api/customermanagement/common'
import { getDateFromTimestamp } from '@/utils'
import moment from 'moment'

export default {
  name: 'crm-relative-table', // 相关模块CRMCell
  components: {
    CRMCreateView: () =>
      import('@/views/customermanagement/components/CRMCreateView')
  },
  computed: {
    // 展示相关效果 去除场景
    isRelationShow() {
      return this.action.type === 'condition'
    }
  },
  watch: {
    crmType: function(newValue, oldValue) {
      if (newValue != oldValue) {
        this.fieldList = []
        this.getFieldList()
      }
    },
    action: function(val) {
      if (this.action != val) {
        this.sceneInfo = null
        this.list = [] // 表数据
        this.fieldList = [] // 表头数据
        this.currentPage = 1 // 当前页数
        this.totalPage = 1 //总页数
        if (!this.isRelationShow) {
          this.getSceneList()
        } else {
          this.getFieldList()
        }
      }
    },
    show: {
      handler(val) {
        if (val && this.fieldList.length == 0) {
          // 相关列表展示时不需要场景 直接获取展示字段
          if (!this.isRelationShow) {
            this.getSceneList()
          } else {
            this.getFieldList()
          }
        }
      },
      deep: true,
      immediate: true
    },
    // 选择
    selectedData: function() {
      this.checkItemsWithSelectedData()
    }
  },
  data() {
    return {
      loading: false, // 加载进度
      searchContent: '', // 输入内容
      isCreate: false, // 控制新建
      scenesList: [], // 场景信息
      sceneInfo: null,

      list: [], // 表数据
      fieldList: [], // 表头数据
      currentPage: 1, // 当前页数
      totalPage: 1, //总页数

      selectedItem: [], // 勾选的数据 点击确定 传递给父组件
      /** 格式化规则 */
      formatterRules: {}
    }
  },
  props: {
    show: {
      type: Boolean,
      default: false
    },
    /** 多选框 只能选一个 */
    radio: {
      type: Boolean,
      default: true
    },
    /** 没有值就是全部类型 有值就是当个类型 */
    crmType: {
      type: String,
      default: ''
    },
    /** 已选信息 */
    selectedData: {
      type: Object,
      default: () => {
        return {}
      }
    },
    /**
     * default 默认  condition 固定条件筛选
     * relative: 相关 添加
     */
    action: {
      type: Object,
      default: () => {
        return {
          type: 'default',
          data: {}
        }
      }
    }
  },
  mounted() {},
  methods: {
    getSceneList() {
      this.loading = true
      crmSceneIndex({
        types: 'crm_' + this.crmType
      })
        .then(res => {
          var defaultScene = res.data.list.filter(function(item, index) {
            return item.is_default === 1
          })
          this.scenesList = res.data.list
          if (defaultScene && defaultScene.length > 0) {
            this.sceneInfo = defaultScene[0]
          }
          if (this.scenesList.length == 0) {
            this.scenesList.push({ scene_id: '', name: '全部' })
            this.sceneInfo = this.scenesList[0]
          }
          this.getFieldList()
        })
        .catch(() => {
          this.loading = false
        })
    },
    /** 获取字段 */
    getFieldList() {
      if (this.fieldList.length == 0) {
        var defaultFields = this.getDefaultField()
        for (let index = 0; index < defaultFields.length; index++) {
          const element = defaultFields[index]
          /** 获取需要格式化的字段 和格式化的规则 */
          if (element.form_type === 'datetime') {
            function foramtterDatetime(time) {
              if (!time || time == 0) {
                return ''
              }
              return moment(getDateFromTimestamp(time)).format(
                'YYYY-MM-DD HH:mm:ss'
              )
            }
            this.formatterRules[element.field] = {
              formatter: foramtterDatetime
            }
          } else if (element.field === 'create_user_id') {
            function fieldFormatter(info) {
              return info ? info.realname : ''
            }
            this.formatterRules[element.field] = {
              type: 'crm',
              formatter: fieldFormatter
            }
            /** 联系人 客户 商机 */
          } else if (
            element.field === 'contacts_id' ||
            element.field === 'customer_id' ||
            element.field === 'business_id'
          ) {
            function fieldFormatter(info) {
              return info ? info.name : ''
            }
            this.formatterRules[element.field] = {
              type: 'crm',
              formatter: fieldFormatter
            }
          } else if (
            element.field === 'status_id' ||
            element.field === 'type_id' ||
            element.field === 'category_id'
          ) {
            function fieldFormatter(info) {
              return info ? info : ''
            }
            this.formatterRules[element.field] = {
              type: 'crm',
              formatter: fieldFormatter
            }
          }

          this.fieldList.push({
            prop: element.field,
            label: element.name
          })
        }
      }
      // 获取好字段开始请求数据
      this.getList()
    },
    /** 获取列表请求 */
    getDefaultField() {
      if (this.crmType === 'leads') {
        return [
          { name: '线索名称', field: 'name', form_type: 'leads' },
          { name: '下次联系时间', field: 'next_time', form_type: 'datetime' },
          { name: '最后跟进时间', field: 'update_time', form_type: 'datetime' },
          { name: '创建时间 ', field: 'create_time', form_type: 'datetime' }
        ]
      } else if (this.crmType === 'customer') {
        return [
          { name: '客户名称', field: 'name', form_type: 'customer' },
          { name: '下次联系时间', field: 'next_time', form_type: 'datetime' },
          { name: '最后跟进时间', field: 'update_time', form_type: 'datetime' },
          { name: '创建时间 ', field: 'create_time', form_type: 'datetime' }
        ]
      } else if (this.crmType === 'contacts') {
        return [
          { name: '姓名', field: 'name', form_type: 'contacts' },
          { name: '手机', field: 'mobile', form_type: 'mobile' },
          { name: '电话', field: 'telephone', form_type: 'text' },
          { name: '是否关键决策人', field: 'decision', form_type: 'text' },
          { name: '职务', field: 'post', form_type: 'text' }
        ]
      } else if (this.crmType === 'business') {
        return [
          { name: '商机名称', field: 'name', form_type: 'text' },
          { name: '商机金额', field: 'money', form_type: 'text' },
          { name: '客户名称', field: 'customer_id', form_type: 'text' },
          { name: '商机状态组 ', field: 'type_id', form_type: 'text' },
          { name: '状态 ', field: 'status_id', form_type: 'text' }
        ]
      } else if (this.crmType === 'contract') {
        return [
          { name: '合同编号', field: 'num', form_type: 'text' },
          { name: '合同名称', field: 'name', form_type: 'text' },
          { name: '客户名称', field: 'customer_id', form_type: 'text' },
          { name: '合同金额', field: 'money', form_type: 'text' },
          { name: '开始日期', field: 'start_time', form_type: 'text' },
          { name: '结束日期', field: 'end_time', form_type: 'text' }
        ]
      } else if (this.crmType === 'product') {
        return [
          { name: '产品名称', field: 'name', form_type: 'text' },
          { name: '单位', field: 'unit', form_type: 'text' },
          { name: '价格', field: 'price', form_type: 'text' },
          { name: '产品类别', field: 'category_id', form_type: 'text' },
          { name: '状态 ', field: 'status', form_type: 'text' }
        ]
      }
    },
    /** 格式化字段 */
    fieldFormatter(row, column) {
      // 如果需要格式化
      var aRules = this.formatterRules[column.property]
      if (aRules) {
        if (aRules.type === 'crm') {
          if (column.property) {
            return aRules.formatter(row[column.property + '_info'])
          } else {
            return ''
          }
        } else {
          return aRules.formatter(row[column.property])
        }
      }
      return row[column.property]
    },
    /** 获取列表数据 */
    getList() {
      this.loading = true
      var crmIndexRequest = this.getIndexRequest()

      var params = {
        page: this.currentPage,
        limit: 10,
        search: this.searchContent
      }
      // 注入场景
      if (this.sceneInfo) {
        params.scene_id = this.sceneInfo.scene_id
      }
      // 注入关联ID
      if (this.isRelationShow) {
        // 客户下相关展示
        if (this.action.data.form_type === 'customer') {
          // 是什么类型下的数据 传入什么类型的ID
          params.customer_id = this.action.data.customer_id
          if (this.action.data.params) {
            for (let field in this.action.data.params) {
              params[field] = this.action.data.params[field]
            }
          }
        }
      }

      crmIndexRequest(params)
        .then(res => {
          this.list = res.data.list
          /**
           *  如果已选择的有数据
           */
          if (this.selectedData[this.crmType]) {
            this.checkItemsWithSelectedData()
          } else {
            this.list = res.data.list
          }

          this.totalPage = Math.ceil(res.data.dataCount / 10)
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 标记选择数据
    checkItemsWithSelectedData() {
      let selectedArray = this.selectedData[this.crmType]
      let selectedRows = []
      this.list.forEach((item, index) => {
        selectedArray.forEach((selectedItem, selectedIndex) => {
          if (
            item[this.crmType + '_id'] == selectedItem[this.crmType + '_id']
          ) {
            selectedRows.push(item)
          }
        })
      })

      this.$nextTick(() => {
        this.$refs.relativeTable.clearSelection()
        selectedRows.forEach(row => {
          this.$refs.relativeTable.toggleRowSelection(row, true)
        })
      })
    },
    /** 获取列表请求 */
    getIndexRequest() {
      if (this.crmType === 'leads') {
        return crmLeadsIndex
      } else if (this.crmType === 'customer') {
        return crmCustomerIndex
      } else if (this.crmType === 'contacts') {
        return crmContactsIndex
      } else if (this.crmType === 'business') {
        return crmBusinessIndex
      } else if (this.crmType === 'contract') {
        return crmContractIndex
      } else if (this.crmType === 'product') {
        return crmProductIndex
      }
    },
    // 场景选择
    handleTypeDrop(command) {
      this.sceneInfo = command
      this.getList()
    },
    /** 列表操作 */
    // 当某一行被点击时会触发该事件
    handleRowClick(row, column, event) {},
    //当选择项发生变化时会触发该事件
    handleSelectionChange(val) {
      if (this.radio) {
        // this.$refs.relativeTable.clearSelection();
        val.forEach((row, index) => {
          if (index === val.length - 1) return
          this.$refs.relativeTable.toggleRowSelection(row, false)
        })
        if (val.length === 0) {
          this.selectedItem = []
        } else {
          this.selectedItem = val.length === 1 ? val : [val[val.length - 1]]
        }
      } else {
        this.selectedItem = val
      }
      this.$emit('changeCheckout', {
        data: this.selectedItem,
        type: this.crmType
      })
    },
    clearAll() {
      this.$refs.relativeTable.clearSelection()
    },
    // 	当用户手动勾选全选 Checkbox 时触发的事件
    selectAll() {},
    // 进行搜索操作
    searchInput() {
      this.currentPage = 1
      this.totalPage = 1
      this.getList()
    },
    changePage(type) {
      if (type == 'up') {
        this.currentPage = this.currentPage - 1
      } else if (type == 'down') {
        this.currentPage = this.currentPage + 1
      }
      if (this.currentPage <= this.totalPage && this.currentPage >= 1) {
        this.getList()
      }
    }
  }
}
</script>
<style lang="scss" scoped>
.cr-body-content {
  position: relative;
  background-color: white;
  border-bottom: 1px solid $xr-border-line-color;
}

.content-header {
  position: relative;
  padding: 10px 20px;
  .search-container {
    margin: 0 20px;
    width: 200px;
  }
  .create-button {
    position: absolute;
    right: 10px;
    top: 15px;
  }
}

//表尾 上一页按钮
.table-footer {
  padding: 8px 20px;
}

.el-table /deep/ thead th {
  font-weight: 400;
  font-size: 12px;
}

.el-table /deep/ tbody tr td {
  font-size: 12px;
}

.el-table /deep/ thead .el-checkbox {
  display: none;
}
</style>
