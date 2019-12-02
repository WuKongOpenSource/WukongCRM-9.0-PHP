<template>
  <div class="rc-cont">
    <flexbox
      v-if="!isSeas"
      class="rc-head"
      direction="row-reverse">
      <el-button
        class="rc-head-item"
        type="primary"
        @click.native="addFile">上传附件</el-button>
      <input
        id="file"
        type="file"
        class="rc-head-file"
        accept="*/*"
        multiple
        @change="uploadFile">
    </flexbox>
    <el-table
      :data="list"
      :height="tableHeight"
      :header-cell-style="headerRowStyle"
      :cell-style="cellStyle"
      align="center"
      header-align="center"
      stripe
      style="width: 100%;border: 1px solid #E6E6E6;"
      @row-click="handleRowClick">
      <el-table-column
        v-for="(item, index) in fieldList"
        :key="index"
        :prop="item.prop"
        :formatter="fieldFormatter"
        :label="item.label"
        show-overflow-tooltip/>
      <el-table-column
        label="操作"
        width="150">
        <template slot-scope="scope">
          <flexbox justify="center">
            <el-button
              type="text"
              @click.native="handleFile('preview', scope)">预览</el-button>
            <el-button
              type="text"
              @click.native="handleFile('edit', scope)">重命名</el-button>
            <el-button
              type="text"
              @click.native="handleFile('delete', scope)">删除</el-button>
          </flexbox>
        </template>
      </el-table-column>
    </el-table>
    <el-dialog
      :append-to-body="true"
      :visible.sync="editDialog"
      title="编辑"
      width="30%">
      <el-form :model="editForm">
        <el-form-item
          label="新名称"
          label-width="100">
          <el-input
            v-model="editForm.name"
            autocomplete="off"/>
        </el-form-item>
      </el-form>
      <div
        slot="footer"
        class="dialog-footer">
        <el-button @click="editDialog = false">取 消</el-button>
        <el-button
          type="primary"
          @click="confirmEdit">确 定</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script type="text/javascript">
import loading from '../mixins/loading'
import {
  crmFileSave,
  crmFileIndex,
  crmFileDelete,
  crmFileUpdate
} from '@/api/common'

export default {
  name: 'RelativeFiles', // 相关附件  可能再很多地方展示 放到客户管理目录下
  components: {},
  mixins: [loading],
  props: {
    /** 模块ID */
    id: [String, Number],
    /** 联系人人下 新建商机 需要联系人里的客户信息  合同下需要客户和商机信息 */
    detail: {
      type: Object,
      default: () => {
        return {}
      }
    },
    /** 没有值就是全部类型 有值就是当个类型 */
    crmType: {
      type: String,
      default: ''
    },
    /** 是公海 默认是客户 */
    isSeas: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      list: [],
      fieldList: [],
      tableHeight: '400px',
      /** 格式化规则 */
      formatterRules: {},
      /** 重命名 弹窗 */
      editDialog: false,
      /** 编辑信息 */
      editForm: { name: '', data: {}}
    }
  },
  computed: {},
  watch: {
    id: function(val) {
      this.list = []
      this.getDetail()
    }
  },
  mounted() {
    this.fieldList.push({ prop: 'name', width: '200', label: '附件名称' })
    this.fieldList.push({ prop: 'size', width: '200', label: '附件大小' })
    this.fieldList.push({
      prop: 'create_user_id',
      width: '200',
      label: '上传人'
    })
    this.fieldList.push({
      prop: 'create_time',
      width: '200',
      label: '上传时间'
    })
    function fieldFormatter(info) {
      return info ? info.realname : ''
    }
    this.formatterRules['create_user_id'] = {
      type: 'crm',
      formatter: fieldFormatter
    }
    this.getDetail()
  },
  activated: function() {},
  deactivated: function() {},
  methods: {
    getDetail() {
      this.loading = true
      crmFileIndex({
        module: 'crm_' + this.crmType,
        module_id: this.id,
        by: 'all'
      })
        .then(res => {
          this.loading = false
          this.list = res.data.list
        })
        .catch(() => {
          this.loading = false
        })
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
    addFile() {
      document.getElementById('file').click()
    },
    /** 图片选择出发 */
    uploadFile(event) {
      var files = event.target.files
      for (let index = 0; index < files.length; index++) {
        const file = files[index]
        // if (file.type.indexOf('image') != -1) {
        var params = {}
        params.module_id = this.id
        params.module = 'crm_' + this.crmType
        params['file[]'] = file
        crmFileSave(params)
          .then(res => {
            this.getDetail()
          })
          .catch(() => {})
        // }
      }

      event.target.value = ''
    },
    // 当某一行被点击时会触发该事件
    handleRowClick(row, column, event) {},
    /** 编辑删除cell */
    handleFile(type, item) {
      if (type === 'preview') {
        var previewList = this.list.map(element => {
          element.url = element.file_path
          return element
        })
        this.$bus.emit('preview-image-bus', {
          index: item.$index,
          data: previewList
        })
      } else if (type === 'delete') {
        this.$confirm('您确定要删除该文件吗?', '提示', {
          confirmButtonText: '确定',
          cancelButtonText: '取消',
          type: 'warning'
        })
          .then(() => {
            crmFileDelete({
              save_name: item.row.save_name,
              module_id: this.id,
              module: 'crm_' + this.crmType
            })
              .then(res => {
                this.list.splice(item.$index, 1)
                this.$message.success(res.data)
              })
              .catch(() => {})
          })
          .catch(() => {
            this.$message({
              type: 'info',
              message: '已取消操作'
            })
          })
      } else {
        this.editForm.data = item
        this.editForm.name = item.row.name
        this.editDialog = true
      }
    },
    confirmEdit() {
      if (this.editForm.name) {
        crmFileUpdate({
          save_name: this.editForm.data.row.save_name,
          name: this.editForm.name
        })
          .then(res => {
            this.$message.success(res.data)
            this.editDialog = false
            var item = this.list[this.editForm.data.$index]
            item.name = this.editForm.name
          })
          .catch(() => {})
      }
    },
    /** 通过回调控制表头style */
    headerRowStyle({ row, column, rowIndex, columnIndex }) {
      return { textAlign: 'center' }
    },
    /** 通过回调控制style */
    cellStyle({ row, column, rowIndex, columnIndex }) {
      return { textAlign: 'center' }
    }
  }
}
</script>
<style lang="scss" scoped>
@import '../styles/relativecrm.scss';

.h-item {
  font-size: 13px;
  color: #409eff;
  margin: 0 5px;
  cursor: pointer;
}

.rc-head-file {
  position: absolute;
  top: 0;
  right: 0;
  height: 98px;
  width: 98px;
  opacity: 0;
  z-index: -1;
  cursor: pointer;
}
</style>

