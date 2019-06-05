<template>
  <div class="field-info-section">
    <div class="item-section">
      <div class="name">
        标识名
      </div>
      <el-input v-model="field.name"
                :disabled="disabled"></el-input>
      <div class="input-tips"><span>*</span>标识名不能为空</div>
    </div>
    <div class="item-section">
      <div class="name">
        提示文字
      </div>
      <el-input v-model="field.input_tips"
                type="textarea"
                resize="none"
                :rows="3"
                :disabled="disabled"></el-input>
      <div class="input-tips"><span>*</span>显示在标识名右侧的说明文字</div>
    </div>

    <div v-if="show_select"
         class="item-section">
      <div class="name">
        选项设置
      </div>
      <el-radio-group v-if="field.form_type == 'select'"
                      v-model="field.default_value"
                      :disabled="disabled">
        <draggable :list="field.showSetting">
          <div class="radio"
               v-for="(item, index) in field.showSetting"
               :key="index">
            <el-radio @click.native.prevent="radioChange(item.value)"
                      :label="item.value">
              <el-input class="input"
                        v-model="item.value"
                        :disabled="disabled"></el-input>
            </el-radio>
            <i @click="handleRadio('add', item, index)"
               class="el-icon-circle-plus handle"></i>
            <i v-if="field.showSetting.length > 1"
               @click="handleRadio('remove', item, index)"
               class="el-icon-remove handle"></i>
          </div>
        </draggable>
      </el-radio-group>
      <el-checkbox-group v-if="field.form_type == 'checkbox'"
                         v-model="field.default_value"
                         :disabled="disabled">
        <draggable :list="field.showSetting">
          <div v-for="(item, index) in field.showSetting"
               :key="index"
               class="checkbox">
            <el-checkbox :label="item.value">
            </el-checkbox>
            <el-input class="input"
                      v-model="item.value"
                      :disabled="disabled"></el-input>
            <i @click.stop="handleCheckbox('add', item, index)"
               class="el-icon-circle-plus handle"></i>
            <i v-if="field.showSetting.length > 1"
               @click.stop="handleCheckbox('remove', item, index)"
               class="el-icon-remove handle"></i>
          </div>

        </draggable>
      </el-checkbox-group>
    </div>

    <div v-if="show_default_value&&!is_userstructure"
         class="item-section">
      <div class="name">
        默认值
      </div>
      <el-input v-if="!show_datepicker"
                @blur="inputBlur"
                v-model="field.default_value"
                :disabled="disabled"></el-input>
      <el-date-picker v-if="show_datepicker"
                      v-model="field.default_value"
                      :disabled="disabled"
                      :type="field.form_type == 'date' ? 'date' : 'datetime'"
                      :value-format="field.form_type == 'date' ? 'yyyy-MM-dd' : 'yyyy-MM-dd HH:mm:ss'"
                      placeholder="选择日期">
      </el-date-picker>
      <div v-if="default_tips"
           class="input-tips"><span>*</span>{{default_tips}}</div>
    </div>

    <div v-if="show_max_input"
         class="item-section">
      <div class="name">
        字数上限
      </div>
      <el-input v-model="field.max_length"
                :maxlength="4"
                :disabled="disabled"></el-input>
      <div class="input-tips"><span>*</span>上限为2000字</div>
    </div>

    <div class="item-check-section">
      <el-checkbox v-model="field.is_null"
                   :disabled="disabled">设为必填</el-checkbox>
    </div>
    <div class="item-check-section">
      <el-checkbox v-model="field.is_unique"
                   :disabled="disabled">设为唯一</el-checkbox>
    </div>
  </div>
</template>
<script type="text/javascript">
import draggable from 'vuedraggable'
import { regexIsCRMMobile, regexIsCRMEmail } from '@/utils'

export default {
  name: 'field-info', // 自定义字段 字段详情
  components: {
    draggable
  },
  computed: {
    default_tips() {
      if (this.field.form_type == 'floatnumber') {
        return '货币的整数部分须少于10位，小数部分须少于2位'
      } else if (this.field.form_type == 'number') {
        return '数字的整数部分须少于12位，小数部分须少于4位'
      }
      return ''
    },
    /** 展示最大输入 */
    show_max_input() {
      if (this.field.form_type == 'textarea') {
        return true
      }
      return false
    },
    /** 展示默认值块 */
    show_default_value() {
      if (
        this.field.form_type == 'select' ||
        this.field.form_type == 'checkbox'
      ) {
        return false
      }
      return true
    },
    /** 展示单选多选 */
    show_select() {
      if (
        this.field.form_type == 'select' ||
        this.field.form_type == 'checkbox'
      ) {
        return true
      }
      return false
    },
    /** 展示时间选择 */
    show_datepicker() {
      if (
        this.field.form_type == 'date' ||
        this.field.form_type == 'datetime'
      ) {
        return true
      }
      return false
    },
    /** 控制人员和部分不展示默认值 */
    is_userstructure() {
      if (
        this.field.form_type == 'user' ||
        this.field.form_type == 'structure'
      ) {
        return true
      }
      return false
    },
    /** 只读 */
    disabled() {
      // operating 0 改删 1改 2删 3无
      if (this.field.operating == 2 || this.field.operating == 3) {
        return true
      }
      return false
    }
  },
  watch: {
    field() {
      if (this.show_select && this.field.showSetting.length == 0) {
        this.field.showSetting = [
          { value: '选1' },
          { value: '选2' },
          { value: '选3' }
        ]
      }
    }
  },
  data() {
    return {}
  },
  props: {
    // 单个字段详情
    field: {
      type: Object,
      default: () => {
        return {
          name: '', //  标识名
          form_type: '', // 字段类型
          is_unique: false, // 是否唯一
          is_null: false, // 是否必填
          input_tips: '', // 输入提示
          max_length: '', // textarea 多行文本有最大数量
          default_value: '', // 默认值
          setting: '', // 接口返回setting数据
          showSetting: '' // 单选选项
        }
      }
    }
  },
  mounted() {
    if (this.show_select && this.field.showSetting.length == 0) {
      this.field.showSetting = [
        { value: '选1' },
        { value: '选2' },
        { value: '选3' }
      ]
    }
  },
  methods: {
    //当选的操作
    handleRadio(type, item, index) {
      if (this.disabled) {
        // 不能点击
        return
      }
      if (type == 'add') {
        this.field.showSetting.push({
          value: '选' + (this.field.showSetting.length + 1)
        })
      } else if (type == 'remove') {
        if (item.value == this.field.default_value) {
          this.field.default_value = ''
        }
        this.field.showSetting.splice(index, 1)
      }
    },
    radioChange(val) {
      this.field.default_value == val
        ? (this.field.default_value = '')
        : (this.field.default_value = val)
    },
    /**
     * 多选
     */
    handleCheckbox(type, item, index) {
      if (this.disabled) {
        // 不能点击
        return
      }
      if (type == 'add') {
        this.field.showSetting.push({
          value: '选' + (this.field.showSetting.length + 1)
        })
      } else if (type == 'remove') {
        let removeIndex = this.field.default_value.indexOf(item.value)
        if (removeIndex != -1) {
          this.field.default_value.splice(removeIndex, 1)
        }
        this.field.showSetting.splice(index, 1)
      }
    },

    /*** 输入默认值触发 */
    inputBlur(e) {
      if (this.field.form_type == 'mobile') {
        if (!regexIsCRMMobile(this.field.default_value)) {
          this.$message({
            message: '输入的手机格式有误',
            type: 'error'
          })
        }
      } else if (this.field.form_type == 'email') {
        if (!regexIsCRMEmail(this.field.default_value)) {
          this.$message({
            message: '输入的邮箱格式有误',
            type: 'error'
          })
        }
      }
    }
  }
}
</script>
<style lang="scss" scoped>
.field-info-section {
  padding: 0 20px;
}

.item-section {
  padding: 10px 0 12px 0;
  border-bottom: 1px solid #e6e6e6;
  .name {
    font-size: 13px;
    font-size: 500;
    color: #333;
    margin: 10px 0;
  }
}

.input-tips {
  font-size: 12px;
  margin-top: 10px;
  color: #999;
  span {
    color: red;
  }
}

.el-checkbox /deep/ .el-checkbox__label {
  font-size: 13px;
  color: #333333;
}

.item-check-section {
  margin-top: 15px;
}

.radio {
  margin-top: 5px;
  margin-left: 0;
  /deep/.el-radio {
    margin-right: 10px;
  }
  .input {
    display: inline-block;
    width: 180px;
  }
  .handle {
    color: #ccc;
    font-size: 20px;
  }
}

.checkbox {
  display: block;
  margin-left: 0;
  margin-top: 5px;
  /deep/.el-checkbox {
    margin-right: 10px;
    .el-checkbox__label {
      display: none;
    }
  }
  .input {
    display: inline-block;
    width: 180px;
  }
  .handle {
    color: #ccc;
    font-size: 20px;
  }
}
</style>
