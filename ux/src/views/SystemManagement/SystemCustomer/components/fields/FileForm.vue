<template>
  <div class="box-wrapper">
    <div class="title">
      <span>{{attr.is_null ? '*' : ''}}</span>{{attr.name}}<span v-if="attr.input_tips">{{'（'+attr.input_tips+'）'}}</span>
    </div>
    <div class="box">
      请选择文件
    </div>
    <span class="el-icon-delete control"
          @click="handleDelete"
          v-if="isShow"></span>
  </div>
</template>

<script>
/**
 * 文件
 */
import mixins from './mixin'

export default {
  name: 'FileForm',
  data() {
    return {}
  },
  mixins: [mixins]
}
</script>

<style scoped lang="scss">
@import '@/styles/mixin.scss';
@import 'form.scss';

.box {
  width: 100px;
  font-size: 14px;
  padding: 10px 0;
  border: 1px solid #e1e1e1;
  border-radius: 3px;
  background: white;
  text-align: center;
}
</style>
