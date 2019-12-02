<template>
  <create-view :body-style="{height: '100%'}">
    <div
      v-loading="loading"
      class="details-box">
      <div
        slot="header"
        class="header">
        <span class="text">编辑公告</span>
        <img
          class="el-icon-close rt"
          src="@/assets/img/task_close.png"
          alt=""
          @click="close">
      </div>
      <div class="content">
        <el-form
          ref="form"
          :model="formData"
          :rules="rules">
          <el-form-item
            v-for="(item, index) in formList"
            :label="item.label"
            :class="'el-form-item' + item.field"
            :prop="item.field"
            :key="index">
            <template v-if="item.type == 'date'">
              <el-date-picker
                v-model="formData[item.field]"
                type="date"
                placeholder="选择日期"/>
            </template>
            <template v-else-if="item.type == 'textarea'">
              <el-input
                v-model="formData[item.field]"
                type="textarea"
                autosize
                placeholder="请输入内容"/>
            </template>
            <el-input
              v-else
              v-model="formData[item.field]"/>
          </el-form-item>
        </el-form>
      </div>
      <div class="btn-box">
        <el-button
          type="primary"
          @click="onSubmit">提交</el-button>
        <el-button @click="close">取消</el-button>
      </div>
    </div>
  </create-view>
</template>

<script>
import CreateView from '@/components/CreateView'
export default {
  components: {
    CreateView
  },
  props: {
    formData: Object,
    loading: Boolean
  },
  data() {
    return {
      formList: [
        { label: '公告标题', field: 'title' },
        { label: '开始时间', field: 'start_time', type: 'date' },
        { label: '结束时间', field: 'end_time', type: 'date' },
        { label: '公告正文', field: 'content', type: 'textarea' }
      ],
      rules: {
        title: [
          { required: true, message: '公告标题不能为空', trigger: 'blur' },
          { max: 50, message: '公告标题长度最多为50个字符', trigger: 'blur' }
        ],
        content: [
          { required: true, message: '公告正文不能为空', trigger: 'blur' }
        ]
      }
    }
  },
  methods: {
    onSubmit() {
      this.$emit('editSubmit')
    },
    close() {
      this.$emit('editClose')
    },
    inputChange() {
      this.popoverVisible = true
    }
  }
}
</script>

<style scoped lang="scss">
$size16: 16px;
.details-box {
  display: flex;
  flex-direction: column;
  height: 100%;
  .header {
    line-height: 40px;
    height: 40px;
    padding: 0 0 0 10px;
    .text {
      font-size: 17px;
    }
    .el-icon-close {
      margin-right: 0;
      width: 40px;
      line-height: 40px;
      padding: 10px;
      cursor: pointer;
    }
  }
  .content {
    padding: 15px 18px;
    flex: 1;
    overflow: auto;
    padding-right: 20px;
    .el-form /deep/ {
      .el-form-item {
        margin-bottom: 10px;
        padding-bottom: 10px;
        float: left;
        width: 50%;
        .el-form-item__label {
          float: none;
          font-size: 12px;
        }
        .el-input {
          // width: 45%;
          .el-input__inner {
            vertical-align: bottom;
          }
        }
        .el-date-editor {
          vertical-align: bottom;
          width: 100%;
          .el-range-separator {
            width: 7%;
          }
        }
      }
      .el-form-itemtitle,
      .el-form-itemend_time {
        padding-right: 25px;
      }
      .el-form-itemstart_time {
        padding-left: 25px;
      }
      .el-form-itemstart_time {
        margin-bottom: 11px;
      }
      .el-form-itemcontent {
        width: 100%;
      }
    }
  }
  .btn-box {
    text-align: right;
    padding-right: 20px;
  }
}
</style>
