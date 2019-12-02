<template>
  <div class="cr-contianer">
    <div class="title">关联审批流</div>
    <div style="height: 100%;position: relative;">
      <div class="cr-body-content">
        <flexbox class="content-header">
          <el-input
            v-model="searchContent"
            class="search-container">
            <el-button
              slot="append"
              icon="el-icon-search"
              @click.native="searchInput"/>
          </el-input>
          <el-button
            class="create-button"
            type="primary"
            @click="isCreate=true">新建</el-button>
        </flexbox>
        <el-table
          v-loading="loading"
          ref="relativeTable"
          :data="list"
          :height="250"
          class="cr-table"
          stripe
          border
          highlight-current-row
          style="width: 100%"
          @select-all="selectAll"
          @selection-change="handleSelectionChange"
          @row-click="handleRowClick">
          <el-table-column
            show-overflow-tooltip
            type="selection"
            align="center"
            width="55"/>
          <el-table-column
            v-for="(item, index) in fieldList"
            :key="index"
            :prop="item.prop"
            :label="item.label"
            :width="150"
            :formatter="fieldFormatter"
            show-overflow-tooltip/>
          <el-table-column/>
        </el-table>
        <div class="table-footer">
          <el-button
            :disabled="currentPage <= 1"
            @click.native="changePage('up')">上一页</el-button>
          <el-button
            :disabled="currentPage >= totalPage"
            @click.native="changePage('down')">下一页</el-button>
        </div>
      </div>
    </div>
    <div class="handle-bar">
      <el-button @click.native="closeView">取消</el-button>
      <el-button
        type="primary"
        @click.native="confirmClick">确定</el-button>
    </div>
    <create-system-examine
      v-if="isCreate"
      @save="getList"
      @hiden-view="isCreate=false"/>
  </div>
</template>

<script type="text/javascript">
import { examineFlowIndex } from '@/api/systemManagement/examineflow'
import CreateSystemExamine from '../../SystemExamine/CreateSystemExamine'
import { timestampToFormatTime } from '@/utils'


export default {
  name: 'ExamineFlowRelatieve', // 相关
  components: {
    CreateSystemExamine
  },
  props: {
    /** 多选框 只能选一个 */
    radio: {
      type: Boolean,
      default: true
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
  data() {
    return {
      loading: false, // 加载进度
      searchContent: '', // 输入内容
      isCreate: false, // 控制新建

      list: [], // 表数据
      fieldList: [
        {
          prop: 'name',
          label: '审批流名称'
        },
        {
          prop: 'types',
          label: '关联对象'
        },
        {
          prop: 'user_ids',
          label: '适用范围'
        },
        {
          prop: 'realname',
          label: '最后修改人'
        },
        {
          prop: 'update_time',
          label: '最后修改时间'
        },
        {
          prop: 'status',
          label: '状态'
        }
      ], // 表头数据
      currentPage: 1, // 当前页数
      totalPage: 1, // 总页数
      selectedItem: [] // 勾选的数据 点击确定 传递给父组件
    }
  },
  computed: {
    // 展示相关效果 去除场景
    isRelationShow() {
      return this.action.type === 'condition'
    }
  },
  mounted() {
    this.getList()
  },
  methods: {
    /** 格式化字段 */
    fieldFormatter(row, column) {
      // 如果需要格式化
      if (column.property === 'types') {
        return { crm_contract: '合同', crm_receivables: '回款' }[
          row[column.property]
        ]
      } else if (column.property === 'update_time') {
        return timestampToFormatTime(row[column.property], 'YYYY-MM-DD')
      } else if (column.property === 'user_ids') {
        var name = ''
        var structures = row['structure_ids_info']
        for (let index = 0; index < structures.length; index++) {
          const element = structures[index]
          name = name + element.name + '、'
        }
        var users = row['user_ids_info']
        for (let index = 0; index < users.length; index++) {
          const element = users[index]
          name =
            name + element.realname + (index === users.length - 1 ? '' : '、')
        }
        return name
      } else if (column.property === 'status') {
        if (row[column.property] === 0) {
          return '停用'
        }
        return '启用'
      }
      return row[column.property]
    },
    /** 获取列表数据 */
    getList() {
      this.loading = true

      var params = {
        page: this.currentPage,
        limit: 10,
        search: this.searchContent
      }
      examineFlowIndex(params)
        .then(res => {
          this.list = res.data.list
          /**
           *  如果已选择的有数据
           */
          if (
            Object.keys(this.selectedData).length > 0 &&
            this.selectedData[this.crmType]
          ) {
            var selectedArray = this.selectedData[this.crmType]
            var selectedRows = []

            this.list.forEach((item, index) => {
              selectedArray.forEach((selectedItem, selectedIndex) => {
                if (item.flow_id == selectedItem.flow_id) {
                  selectedRows.push(item)
                }
              })
            })

            this.$nextTick(() => {
              selectedRows.forEach(row => {
                this.$refs.relativeTable.toggleRowSelection(row, true)
              })
            })
          }
          this.totalPage = Math.ceil(res.data.dataCount / 10)

          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    /** 列表操作 */
    // 当某一行被点击时会触发该事件
    handleRowClick(row, column, event) {},
    // 当选择项发生变化时会触发该事件
    handleSelectionChange(val) {
      if (this.radio) {
        // this.$refs.relativeTable.clearSelection();
        val.forEach((row, index) => {
          if (index === val.length - 1) return
          this.$refs.relativeTable.toggleRowSelection(row, false)
        })
        this.selectedItem = val.length === 1 ? val : [val[val.length - 1]]
      } else {
        this.selectedItem = val
      }
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
    },
    // 关闭操作
    closeView() {
      this.$emit('close')
    },
    // 确定选择
    confirmClick() {
      this.$emit('changeCheckout', { data: this.selectedItem })
      this.$emit('close')
    }
  }
}
</script>
<style rel="stylesheet/scss" lang="scss" scoped>
.cr-contianer {
  height: 450px;
  position: relative;
  padding: 50px 0 50px 0;
}

.title {
  padding: 0 20px;
  font-size: 16px;
  line-height: 50px;
  height: 50px;
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  z-index: 3;
  border-bottom: 1px solid $xr-border-line-color;
}

.handle-bar {
  height: 50px;
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 2;
  button {
    float: right;
    margin-top: 10px;
    margin-right: 10px;
  }
}

.cr-body-side {
  flex-shrink: 0;
  z-index: 3;
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 150px;
  font-size: 12px;
  background-color: white;
  height: 100%;
  border-right: 1px solid $xr-border-line-color;
  .side-item {
    height: 35px;
    line-height: 35px;
    padding: 0 20px;
    cursor: pointer;
  }
}
.cr-body-content {
  position: relative;
  background-color: white;
  border-bottom: 1px solid $xr-border-line-color;
}

.side-item-default {
  color: #333;
}

.side-item-select {
  color: #409eff;
  background-color: #ecf5ff;
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
