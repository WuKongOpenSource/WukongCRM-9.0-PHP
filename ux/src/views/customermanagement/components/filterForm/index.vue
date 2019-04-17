<template>
  <el-dialog title="高级筛选"
             :visible.sync="visible"
             @close="handleCancel"
             width="800px">
    <div style="margin-bottom: 10px;">筛选条件</div>
    <el-form class="filter-container">
      <el-form-item>
        <template v-for="(formItem, index) in form">
          <el-row :key="index">
            <el-col :span="8">
              <el-select v-model="formItem.field"
                         @change="fieldChange(formItem)"
                         placeholder="请选择要筛选的字段名">
                <el-option v-for="item in fieldList"
                           :key="item.field"
                           :label="item.name"
                           :value="item.field">
                </el-option>
              </el-select>
            </el-col>

            <el-col :span="1"
                    v-if="formItem.form_type !== 'date' && formItem.form_type !== 'datetime' && formItem.form_type !== 'business_type'">&nbsp;</el-col>
            <el-col :span="4"
                    v-if="formItem.form_type !== 'date' && formItem.form_type !== 'datetime' && formItem.form_type !== 'business_type'">
              <el-select v-model="formItem.condition"
                         placeholder="请选择范围">
                <el-option v-for="item in calConditionOptions(formItem.form_type, formItem)"
                           :key="item.value"
                           :label="item.label"
                           :value="item.value">
                </el-option>
              </el-select>
            </el-col>

            <!-- 商机组 -->
            <el-col :span="1"
                    v-if="formItem.form_type == 'business_type'">&nbsp;</el-col>
            <el-col :span="4"
                    v-if="formItem.form_type == 'business_type'">
              <el-select v-model="formItem.type_id"
                         @change="typeOptionsChange(formItem)"
                         placeholder="请选择">
                <el-option v-for="item in formItem.typeOption"
                           :key="item.type_id"
                           :label="item.name"
                           :value="item.type_id">
                </el-option>
              </el-select>
            </el-col>

            <el-col :span="1">&nbsp;</el-col>
            <el-col :span="formItem.form_type === 'datetime' || formItem.form_type === 'date' ? 13 : 8">
              <el-select v-if="formItem.form_type === 'select'"
                         v-model="formItem.value"
                         placeholder="请选择筛选条件">
                <el-option v-for="item in formItem.setting"
                           :key="item"
                           :label="item"
                           :value="item">
                </el-option>
              </el-select>
              <el-date-picker v-else-if="formItem.form_type === 'date' || formItem.form_type === 'datetime'"
                              v-model="formItem.value"
                              :value-format="formItem.form_type === 'date' ? 'yyyy-MM-dd' : 'yyyy-MM-dd HH:mm:ss'"
                              :type="formItem.form_type === 'date' ? 'daterange' : 'datetimerange'"
                              style="padding: 0px 10px;"
                              range-separator="-"
                              start-placeholder="开始日期"
                              end-placeholder="结束日期">
              </el-date-picker>
              <el-select v-else-if="formItem.form_type === 'business_type'"
                         v-model="formItem.status_id"
                         placeholder="请选择">
                <el-option v-for="item in formItem.statusOption"
                           :key="item.status_id"
                           :label="item.name"
                           :value="item.status_id">
                </el-option>
              </el-select>
              <xh-user-cell v-else-if="formItem.form_type === 'user'"
                            :item="formItem"
                            :infoParams="{m	:'crm',c: crmType,a: 'index' }"
                            @value-change="userValueChange"></xh-user-cell>
              <el-input v-else
                        v-model="formItem.value"
                        placeholder="请输入筛选条件"></el-input>
            </el-col>
            <el-col :span="1"
                    class="delete">
              <i class="el-icon-error delete-btn"
                 @click="handleDelete(index)"></i>
            </el-col>
          </el-row>
        </template>
      </el-form-item>
    </el-form>
    <p class="el-icon-warning warning-info"
       v-show="showErrors">
      <span class="desc">筛选条件中有重复项！</span>
    </p>
    <el-button type="text"
               @click="handleAdd">+ 添加筛选条件</el-button>
    <div class="save" v-if="!isSeas">
      <el-checkbox v-model="saveChecked">保存为场景</el-checkbox>
      <el-input class="name"
                v-show="saveChecked"
                v-model.trim="saveName"
                :maxlength="10"
                placeholder="请输入场景名称，最多10个字符">
      </el-input>
      <div class="save-setting"
           v-show="saveChecked">
        <el-checkbox v-model="saveDefault">设置为默认</el-checkbox>
      </div>
    </div>
    <div slot="footer"
         class="dialog-footer">
      <el-button @click="handleCancel">取 消</el-button>
      <el-button type="primary"
                 @click="handleConfirm">确 定</el-button>
    </div>
  </el-dialog>
</template>

<script>
import { formatTimeToTimestamp, objDeepCopy } from '@/utils'
import { XhUserCell } from '@/components/CreateCom'
/**
 * fieldList: 高级筛选的字段
 *     type:  date || datetime || select || 其他 input
 */
export default {
  name: 'index',
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
      default: []
    },
    obj: {
      default: {},
      required: true
    },
    // 辅助 使用 公海没有场景
    isSeas: {
      type: Boolean,
      default: false
    },
    /** 获取客户管理下列表权限内的员工列表 针对 usersList */
    crmType: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      form: [],
      visible: false,
      showErrors: false,
      saveChecked: false, // 展示场景
      saveDefault: false, // 设置为默认场景
      saveName: null // 场景名称
    }
  },
  watch: {
    dialogVisible: {
      handler(val) {
        if (val) {
          this.form = objDeepCopy(this.obj.form)
          if (this.form.length == 0) {
            this.form.push({
              field: '',
              name: '',
              form_type: '',
              condition: 'is',
              value: '',
              typeOption: [],
              statusOption: [],
              type_id: '',
              status_id: ''
            })
          }
          this.saveChecked = false
          this.saveDefault = false
          this.saveName = null
        }
        this.visible = this.dialogVisible
      },
      deep: true,
      immediate: true
    }
  },
  methods: {
    /**
     * 商机组状态
     */
    typeOptionsChange(formItem) {
      if (formItem.type_id) {
        let obj = formItem.typeOption.find(item => {
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
      let obj = this.fieldList.find(item => {
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

      let arr = this.form.filter(item => {
        return item.field === formItem.field
      })
      if (arr.length > 1) this.showErrors = true
      else this.showErrors = false
    },
    /**
     * 取消选择
     */
    handleCancel() {
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
      if (this.saveChecked) {
        if (!this.saveName || this.saveName === '') {
          this.$message.error('场景名称不能为空！')
          return
        }
      }
      for (let i = 0; i < this.form.length; i++) {
        let o = this.form[i]
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
      let obj = {}
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
      let data = {
        obj: obj,
        form: this.form,
        saveChecked: this.saveChecked,
        saveDefault: this.saveDefault,
        saveName: this.saveName
      }
      this.$emit('filter', data)
    },
    /**
     * 添加筛选条件
     */
    handleAdd() {
      this.form.push({
        field: '',
        condition: 'is',
        value: '',
        form_type: '',
        setting: [],
        typeOption: [],
        statusOption: [],
        type_id: '',
        status_id: ''
      })
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
  max-height: 300px;
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
</style>
