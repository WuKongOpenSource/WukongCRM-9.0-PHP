<template>
  <div>
    <el-form
      v-if="examineInfo.config == 0"
      ref="form"
      :model="form"
      :rules="rules"
      label-position="top"
      class="crm-create-box">
      <el-form-item
        prop="name"
        class="crm-create-item">
        <div
          slot="label"
          style="display: inline-block;">
          <div style="margin:5px 0;font-size:12px;word-wrap:break-word;word-break:break-all;">
            审核人
            <span style="color:#999;"/>
          </div>
        </div>
        <xh-user-cell
          :info-type="types"
          :value="draftUser ? [draftUser] : []"
          @value-change="fieldValueChange"/>
      </el-form-item>
    </el-form>
    <flexbox
      v-else-if="examineInfo.config == 1"
      class="fixed-examine"
      wrap="wrap">
      <el-popover
        v-for="(item, index) in examineInfo.stepList"
        :key="index"
        :disabled="item.user_id_info.length==0"
        :content="item.user_id_info|contentFilters"
        placement="bottom"
        trigger="hover">
        <div
          slot="reference"
          class="fixed-examine-item">
          <img src="@/assets/img/examine_head.png" >
          <div class="detail">{{ item|detail }}</div>
          <div class="step">{{ (index+1)|step }}</div>
        </div>
      </el-popover>
    </flexbox>

  </div>
</template>
<script type="text/javascript">
import { crmExamineFlowStepList } from '@/api/customermanagement/common'
import { XhUserCell } from '@/components/CreateCom'
import Nzhcn from 'nzh/cn'

export default {
  name: 'CreateExamineInfo',
  components: {
    XhUserCell
  },
  filters: {
    detail: function(data) {
      if (data.status == 2) {
        return data.user_id_info.length + '人或签'
      } else if (data.status == 3) {
        return data.user_id_info.length + '人会签'
      } else if (data.status == 1) {
        return '负责人主管'
      } else if (data.status == 4) {
        return '上一级审批人主管'
      }
    },
    step: function(index) {
      return '第' + Nzhcn.encodeS(index) + '级'
    },
    contentFilters: function(array) {
      var content = ''
      for (let index = 0; index < array.length; index++) {
        const item = array[index]
        if (index == array.length - 1) {
          content = content + item.realname
        } else {
          content = content + item.realname + '、'
        }
      }
      return content
    }
  },
  props: {
    // CRM类型
    types: {
      type: String,
      default: ''
    },
    // 办公审批 传ID
    types_id: {
      type: [String, Number],
      default: ''
    }
  },
  data() {
    return {
      form: {
        name: ''
      },
      rules: {
        name: [{ required: true, message: '审批人不能为空', trigger: 'blur' }]
      },
      // 审核信息 config 1 固定 0 自选
      examineInfo: {},
      draftUser: null
    }
  },
  computed: {},
  mounted() {
    this.getDetail()
  },
  methods: {
    getDetail() {
      crmExamineFlowStepList({
        types: this.types,
        types_id: this.types_id,
        action: 'save'
      })
        .then(res => {
          this.examineInfo = res.data
          if (
            res.data.config == 0 &&
            res.data.stepList.length &&
            res.data.stepList[0].type == '5'
          ) {
            const item = res.data.stepList[0]
            this.draftUser = item.userInfo
            this.form.name = item.userInfo.id
            this.$emit('value-change', {
              config: res.data.config, // 审批类型
              value: [item.userInfo] // 审批信息
            })
          } else {
            this.form.name = ''
            this.draftUser = null
            this.$emit('value-change', {
              config: res.data.config, // 审批类型
              value: [] // 审批信息
            })
          }
        })
        .catch(() => {})
    },
    validateField(result) {
      if (this.examineInfo.config == 0) {
        // 授权审批人 需要验证关联人
        this.$refs.form.validate(valid => {
          if (valid) {
            result() // 成功回调
          } else {
            return false
          }
        })
      } else {
        result() // 成功回调
      }
    },
    // 字段的值更新
    fieldValueChange(data) {
      if (data.value.length) {
        this.draftUser = data.value[0]
        this.form.name = this.draftUser.id
      } else {
        this.draftUser = null
        this.form.name = ''
      }
      this.$emit('value-change', {
        config: this.examineInfo.config, // 审批类型
        value: data.value // 审批信息
      })
      this.$refs.form.validateField('name')
    }
  }
}
</script>
<style lang="scss" scoped>
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

// 固定审批信息
.fixed-examine {
  padding: 0px 10px;
  text-align: center;
  .fixed-examine-item {
    padding: 10px 20px;
    img {
      width: 40px;
      height: 40px;
    }
    .detail {
      color: #777777;
      font-size: 12px;
      padding: 2px 0;
      transform: scale(0.8, 0.8);
    }
    .step {
      color: #333333;
      font-size: 12px;
    }
  }
}
</style>
