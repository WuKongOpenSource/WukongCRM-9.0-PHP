<template>
  <el-dialog
    :title="edit_id ? '编辑场景' : '新建场景'"
    :visible.sync="visible"
    :append-to-body="true"
    width="800px"
    @close="handleCancel">
    <div class="scene-name-container">
      <div class="scene-name">场景名称</div>
      <el-input
        v-model.trim="saveName"
        :maxlength="10"
        class="scene-input"
        placeholder="请输入场景名称，最多10个字符"/>
    </div>
    <div class="scene-name">筛选条件</div>
    <el-form
      id="scene-filter-container"
      class="filter-container">
      <el-form-item>
        <template v-for="(formItem, index) in form">
          <el-row :key="index">
            <el-col :span="8">
              <el-select
                v-model="formItem.field"
                placeholder="请选择要筛选的字段名"
                @change="fieldChange(formItem)">
                <el-option
                  v-for="item in fieldList"
                  :key="item.field"
                  :label="item.name"
                  :value="item.field"/>
              </el-select>
            </el-col>

            <el-col
              v-if="formItem.form_type !== 'date' && formItem.form_type !== 'datetime' && formItem.form_type !== 'business_type'"
              :span="1">&nbsp;</el-col>
            <el-col
              v-if="formItem.form_type !== 'date' && formItem.form_type !== 'datetime' && formItem.form_type !== 'business_type'"
              :span="4">
              <el-select
                v-model="formItem.condition"
                placeholder="请选择范围">
                <el-option
                  v-for="item in calConditionOptions(formItem.form_type, formItem)"
                  :key="item.value"
                  :label="item.label"
                  :value="item.value"/>
              </el-select>
            </el-col>

            <!-- 商机组 -->
            <el-col
              v-if="formItem.form_type == 'business_type'"
              :span="1">&nbsp;</el-col>
            <el-col
              v-if="formItem.form_type == 'business_type'"
              :span="4">
              <el-select
                v-model="formItem.type_id"
                placeholder="请选择"
                @change="typeOptionsChange(formItem)">
                <el-option
                  v-for="item in formItem.typeOption"
                  :key="item.type_id"
                  :label="item.name"
                  :value="item.type_id"/>
              </el-select>
            </el-col>

            <el-col :span="1">&nbsp;</el-col>
            <el-col :span="formItem.form_type === 'datetime' || formItem.form_type === 'date' ? 13 : 8">
              <el-select
                v-if="formItem.form_type === 'select'"
                v-model="formItem.value"
                placeholder="请选择筛选条件">
                <el-option
                  v-for="item in formItem.setting"
                  :key="item"
                  :label="item"
                  :value="item"/>
              </el-select>
              <el-date-picker
                v-else-if="formItem.form_type === 'date' || formItem.form_type === 'datetime'"
                v-model="formItem.value"
                :value-format="formItem.form_type === 'date' ? 'yyyy-MM-dd' : 'yyyy-MM-dd HH:mm:ss'"
                :type="formItem.form_type === 'date' ? 'daterange' : 'datetimerange'"
                style="padding: 0px 10px;"
                range-separator="-"
                start-placeholder="开始日期"
                end-placeholder="结束日期"/>
              <el-select
                v-else-if="formItem.form_type === 'business_type'"
                v-model="formItem.status_id"
                placeholder="请选择">
                <el-option
                  v-for="item in formItem.statusOption"
                  :key="item.status_id"
                  :label="item.name"
                  :value="item.status_id"/>
              </el-select>
              <xh-user-cell
                v-else-if="formItem.form_type === 'user'"
                :item="formItem"
                :info-params="{m	:'crm',c: crmType,a: 'index' }"
                :value="formItem.value"
                @value-change="userValueChange"/>
              <el-input
                v-else
                v-model="formItem.value"
                placeholder="请输入筛选条件"/>
            </el-col>
            <el-col
              :span="1"
              class="delete">
              <i
                class="el-icon-error delete-btn"
                @click="handleDelete(index)"/>
            </el-col>
          </el-row>
        </template>
      </el-form-item>
    </el-form>
    <p
      v-show="showErrors"
      class="el-icon-warning warning-info">
      <span class="desc">筛选条件中有重复项！</span>
    </p>
    <el-button
      type="text"
      @click="handleAdd">+ 添加筛选条件</el-button>
    <div class="save">
      <div class="save-setting">
        <el-checkbox v-model="saveDefault">设置为默认</el-checkbox>
      </div>
    </div>
    <div
      slot="footer"
      class="dialog-footer">
      <el-button @click="handleCancel">取 消</el-button>
      <el-button
        type="primary"
        @click="handleConfirm">确 定</el-button>
    </div>
  </el-dialog>
</template>

<script>
import { crmSceneSave, crmSceneUpdate } from '@/api/customermanagement/common'
import {
  formatTimeToTimestamp,
  timestampToFormatTime,
  objDeepCopy
} from '@/utils'
import { XhUserCell } from '@/components/CreateCom'

/**
 * fieldList: 高级筛选的字段
 *     type:  date || datetime || select || 其他 input
 */
export default {
  name: 'SceneCreate', // 新建场景
  components: {
    XhUserCell
  },
  props: {
    dialogVisible: {
      type: Boolean,
      required: true,
      default: false
    },
    fieldList: {
      type: Array,
      required: true,
      default: () => {
        return []
      }
    },
    obj: {
      type: Object,
      default: () => {
        return {}
      },
      required: true
    },
    /** 没有值就是全部类型 有值就是当个类型 */
    crmType: {
      type: String,
      default: ''
    },
    /** 名字和 默认 id 编辑的时候需要 */
    name: {
      type: String,
      default: ''
    },
    isDefault: {
      type: Boolean,
      default: false
    },
    edit_id: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      form: [],
      visible: false, // 控制展示
      showErrors: false,
      saveDefault: false, // 设置为默认场景
      saveName: null // 场景名称
    }
  },
  watch: {
    dialogVisible: {
      handler(val) {
        if (val) {
          // 处理编辑数据
          if (this.edit_id) {
            this.form = []
            for (const field in this.obj.obj) {
              const element = this.obj.obj[field]
              const item = this.getItem()
              item.name = element.name
              item.field = field
              item.condition = element.condition
              item.form_type = element.form_type
              if (element.form_type == 'date') {
                item.value = [element.start_date, element.end_date]
              } else if (element.form_type == 'datetime') {
                item.value = [
                  timestampToFormatTime(element.start, 'YYYY-MM-DD HH:mm:ss'),
                  timestampToFormatTime(element.end, 'YYYY-MM-DD HH:mm:ss')
                ]
              } else if (element.form_type == 'business_type') {
                item.type_id = element.type_id
                item.status_id = element.status_id
                item.typeOption = element.setting
                if (element.type_id) {
                  const obj = element.setting.find(typeItem => {
                    return typeItem.type_id === element.type_id
                  })
                  if (obj) {
                    item.statusOption = obj.statusList
                  } else {
                    item.statusOption = []
                  }
                }
              } else if (element.form_type == 'user') {
                item.value = element.setting ? [element.setting] : []
              } else {
                item.setting = element.setting
                item.value = element.value
              }
              this.form.push(item)
            }
          } else {
            this.form = objDeepCopy(this.obj.form)
            if (this.form.length == 0) {
              this.form.push(this.getItem())
            }
          }

          /** 只有编辑会牵扯到这两个字段赋值 */
          if (this.name) {
            this.saveName = this.name
          } else {
            this.saveName = ''
          }
          if (this.isDefault) {
            this.saveDefault = this.isDefault
          } else {
            this.saveDefault = false
          }
        }
        this.visible = this.dialogVisible
      },
      deep: true,
      immediate: true
    },

    form() {
      this.$nextTick(() => {
        var container = document.getElementById('scene-filter-container')
        container.scrollTop = container.scrollHeight
      })
    }
  },
  methods: {
    getItem() {
      return {
        field: '',
        name: '',
        form_type: '',
        condition: 'is',
        value: '',
        typeOption: [],
        statusOption: [],
        type_id: '',
        status_id: ''
      }
    },
    /**
     * 商机组状态
     */
    typeOptionsChange(formItem) {
      if (formItem.type_id) {
        const obj = formItem.typeOption.find(item => {
          return item.type_id === formItem.type_id
        })
        formItem.statusOption = obj.statusList || []
      } else {
        formItem.statusOption = []
      }
      formItem.status_id = ''
    },
    /**
     * 用户创建人
     */
    userValueChange(data) {
      if (data.value.length > 0) {
        data.item.value = data.value
      } else {
        data.item.value = []
      }
    },
    /** 条件数据源 */
    calConditionOptions(form_type, item) {
      if (
        form_type == 'select' ||
        form_type == 'checkbox' ||
        form_type == 'user'
      ) {
        return [
          { value: 'is', label: '等于', disabled: false },
          { value: 'isnot', label: '不等于', disabled: false }
        ]
      } else if (
        form_type == 'module' ||
        form_type == 'text' ||
        form_type == 'textarea'
      ) {
        return [
          { value: 'is', label: '等于', disabled: false },
          { value: 'isnot', label: '不等于', disabled: false },
          { value: 'contains', label: '包含', disabled: false },
          { value: 'not_contain', label: '不包含', disabled: false }
        ]
      } else if (form_type == 'floatnumber' || form_type == 'number') {
        return [
          { value: 'is', label: '等于', disabled: false },
          { value: 'isnot', label: '不等于', disabled: false },
          { value: 'contains', label: '包含', disabled: false },
          { value: 'not_contain', label: '不包含', disabled: false },
          { value: 'is_empty', label: '为空', disabled: false },
          { value: 'is_not_empty', label: '不为空', disabled: false },
          { value: 'gt', label: '大于', disabled: false },
          { value: 'egt', label: '大于等于', disabled: false },
          { value: 'lt', label: '小于', disabled: false },
          { value: 'elt', label: '小于等于', disabled: false }
        ]
      } else if (form_type == 'category') {
        return [
          { value: 'is', label: '等于', disabled: false },
          { value: 'isnot', label: '不等于', disabled: false },
          { value: 'contains', label: '包含', disabled: false },
          { value: 'not_contain', label: '不包含', disabled: false }
        ]
      } else {
        return [
          { value: 'is', label: '等于', disabled: false },
          { value: 'isnot', label: '不等于', disabled: false },
          { value: 'contains', label: '包含', disabled: false },
          { value: 'not_contain', label: '不包含', disabled: false },
          { value: 'start_with', label: '开始于', disabled: false },
          { value: 'end_with', label: '结束于', disabled: false },
          { value: 'is_empty', label: '为空', disabled: false },
          { value: 'is_not_empty', label: '不为空', disabled: false },
          { value: 'gt', label: '大于', disabled: false },
          { value: 'egt', label: '大于等于', disabled: false },
          { value: 'lt', label: '小于', disabled: false },
          { value: 'elt', label: '小于等于', disabled: false }
        ]
      }
    },
    /**
     * 当前选择的字段名改变，判断是否有重复
     * @param formItem
     */
    fieldChange(formItem) {
      const obj = this.fieldList.find(item => {
        return item.field === formItem.field
      })
      if (obj) {
        formItem.form_type = obj.form_type
        formItem.name = obj.name
        if (formItem.form_type == 'business_type') {
          formItem.typeOption = obj.setting
          formItem.statusOption = []
          formItem.type_id = ''
          formItem.status_id = ''
        } else if (formItem.form_type == 'select') {
          formItem.setting = obj.setting || []
        } else if (
          formItem.form_type === 'date' ||
          formItem.form_type === 'datetime' ||
          formItem.form_type === 'user'
        ) {
          formItem.value = []
        }
      }

      const arr = this.form.filter(item => {
        return item.field === formItem.field
      })
      if (arr.length > 1) this.showErrors = true
      else this.showErrors = false
    },
    /**
     * 取消选择
     */
    handleCancel() {
      this.visible = false
      this.$emit('update:dialogVisible', false)
    },
    /**
     * 确定选择
     */
    handleConfirm() {
      if (this.showErrors) {
        this.$message.error('筛选条件中有重复项！')
        return
      }
      if (!this.saveName || this.saveName === '') {
        this.$message.error('场景名称不能为空！')
        return
      }
      for (let i = 0; i < this.form.length; i++) {
        const o = this.form[i]
        if (!o.field || o.field === '') {
          this.$message.error('要筛选的字段名称不能为空！')
          return
        }

        if (o.form_type == 'business_type') {
          if (!o.type_id && !o.status_id) {
            this.$message.error('请输入筛选条件的值！')
            return
          }
        } else if (
          o.form_type == 'date' ||
          o.form_type == 'datetime' ||
          o.form_type == 'user'
        ) {
          if (!o.value || o.value.length === 0) {
            this.$message.error('请输入筛选条件的值！')
            return
          }
        } else if (!o.value) {
          this.$message.error('请输入筛选条件的值！')
          return
        }
      }
      const obj = {}
      this.form.forEach(o => {
        if (o.form_type == 'date') {
          obj[o.field] = {
            start_date: o.value[0],
            end_date: o.value[1],
            form_type: o.form_type,
            name: o.name
          }
        } else if (o.form_type == 'datetime') {
          obj[o.field] = {
            start: formatTimeToTimestamp(o.value[0]),
            end: formatTimeToTimestamp(o.value[1]),
            form_type: o.form_type,
            name: o.name
          }
        } else if (o.form_type == 'business_type') {
          obj[o.field] = {
            type_id: o.type_id,
            status_id: o.status_id,
            form_type: o.form_type,
            name: o.name
          }
        } else if (o.form_type == 'user') {
          obj[o.field] = {
            condition: o.condition,
            value: o.value[0].id,
            form_type: o.form_type,
            name: o.name
          }
        } else {
          obj[o.field] = {
            condition: o.condition,
            value: o.value,
            form_type: o.form_type,
            name: o.name
          }
        }
      })
      const data = {
        obj: obj,
        form: this.form,
        saveDefault: this.saveDefault,
        saveName: this.saveName
      }
      this.requestCreateScene(data)
    },
    // 创建场景
    requestCreateScene(data) {
      /** 编辑操作 */
      if (this.edit_id) {
        crmSceneUpdate({
          types: 'crm_' + this.crmType,
          is_default: data.saveDefault ? 1 : 0,
          name: data.saveName,
          id: this.edit_id,
          data: data.obj
        })
          .then(res => {
            this.$message({
              type: 'success',
              message: '编辑成功'
            })
            // 新建成功
            this.$emit('saveSuccess')
            this.handleCancel()
          })
          .catch(() => {})
      } else {
        crmSceneSave({
          types: 'crm_' + this.crmType,
          is_default: data.saveDefault ? 1 : 0,
          name: data.saveName,
          data: data.obj
        })
          .then(res => {
            this.$message({
              type: 'success',
              message: '创建成功'
            })
            // 新建成功
            this.$emit('saveSuccess')
            this.handleCancel()
          })
          .catch(() => {})
      }
    },
    /**
     * 添加筛选条件
     */
    handleAdd() {
      this.form.push(this.getItem())
    },
    /**
     * 删除筛选条件
     * @param index
     */
    handleDelete(index) {
      this.$confirm('您确定要删除这一条数据吗?', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      })
        .then(() => {
          this.form.splice(index, 1)
        })
        .catch(() => {
          this.$message({
            type: 'info',
            message: '已取消删除'
          })
        })
    }
  }
}
</script>

<style lang="scss" scoped>
/deep/ .el-dialog__body {
  padding: 10px 20px;
}

/deep/ .el-form-item__label {
  width: 100%;
  text-align: left;
}
.filter-container {
  max-height: 200px;
  overflow-y: auto;
}

.save {
  margin-top: 10px;
  .name {
    width: 300px;
    margin-left: 10px;
    /deep/ .el-input__inner {
      height: 32px;
    }
  }
  .save-setting {
    margin-top: 20px;
  }
}

.el-form-item {
  margin-bottom: 0;
}

.el-row {
  margin-bottom: 20px;
  .delete-btn {
    margin-left: 15px;
    color: #bbb;
    cursor: pointer;
  }
  .el-select,
  .el-date-editor {
    width: 100%;
  }
}

.warning-info {
  width: 100%;
  font-size: 14px;
  color: #f56c6c;
  margin-top: 10px;
  .desc {
    padding-left: 8px;
  }
}

.scene-name-container {
  padding-bottom: 15px;
  .scene-input {
    width: 300px;
  }
}
.scene-name {
  margin-bottom: 10px;
}
</style>
