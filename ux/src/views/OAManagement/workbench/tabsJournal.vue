<template>
  <div class="content">
    <div class="list-box">
      <journal-cell
        v-for="(item, index) in journalData"
        :key="index"
        :log-index="index"
        :data="item"
        :show-workbench="true"
        :style="{margin: marginDefaults ? '0' : '0 20px 20px'}"
        class="list-cell"/>
      <slot name="load"/>
    </div>
  </div>
</template>

<script>
import JournalCell from '@/views/OAManagement/journal/journalCell'

export default {
  components: {
    JournalCell
  },
  props: {
    // 数据
    journalData: Array,
    marginDefaults: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {}
  },
  computed: {},
  methods: {}
}
</script>

<style scoped lang="scss">
.content {
  flex: 1;
  display: flex;
  flex-direction: column;
  .list-box {
    flex: 1;
    overflow: auto;
  }
}

.list-cell {
  border: 1px solid #e6e6e6;
  margin-bottom: 20px;
  border-radius: 4px;
}
</style>
