<template>
  <div class="recycle">
    <div class="recycle-header">
      回收站
    </div>
    <div class="content"
         v-loading="loading">
      <task-cell v-for="(item, index) in list"
                 :key="index"
                 class="item-list"
                 :data="item"
                 :dataIndex="index"
                 @on-handle="taskCellHandle"></task-cell>
    </div>

    <!-- 详情 -->
    <particulars v-if="taskDetailShow"
                 ref="particulars"
                 :id="taskID"
                 :detailIndex="detailIndex"
                 @on-handle="detailHandle"
                 @close="closeBtn">
    </particulars>
  </div>
</template>

<script>
import { workTrashIndexAPI } from '@/api/projectManagement/recycle'
import TaskCell from '@/views/projectManagement/components/taskCell'
import particulars from '../components/particulars'

export default {
  components: {
    particulars,
    TaskCell
  },

  data() {
    return {
      loading: true,
      list: [],
      // 详情数据
      taskID: '',
      detailIndex: -1,
      taskDetailShow: false
    }
  },

  created() {
    this.getList()
  },

  mounted() {
    document
      .getElementById('project-main-container')
      .addEventListener('click', this.taskShowHandle, false)
  },

  methods: {
    /**
     * 获取数据
     */
    getList() {
      workTrashIndexAPI()
        .then(res => {
          this.list = res.data.list
          for (let item of this.list) {
            item.is_deleted = true
          }
          this.loading = false
        })
        .catch(err => {
          this.loading = true
        })
    },

    /**
     * 列表操作
     */
    taskCellHandle(data) {
      if (data.type == 'view') {
        let dataCell = data.data
        this.taskID = dataCell.item.task_id
        this.detailIndex = dataCell.index
        this.taskDetailShow = true
      }
    },

    /**
     * 详情操作
     */
    detailHandle(data) {
      if (data.index == 0 || data.index) {
        // 是否完成勾选
        if (data.type == 'title-check') {
          this.$set(this.list[data.index], 'checked', data.value)
        } else if (
          data.type == 'delete' ||
          data.type == 'activate-task' ||
          data.type == 'recover-task' ||
          data.type == 'thorough-delete-task'
        ) {
          this.list.splice(data.index, 1)
        } else if (data.type == 'change-stop-time') {
          let stopTime = parseInt(data.value) + 86399
          if (stopTime > new Date(new Date()).getTime() / 1000) {
            this.list[data.index].is_end = false
          } else {
            this.list[data.index].is_end = true
          }
          this.list[data.index].stop_time = data.value
        } else if (data.type == 'change-priority') {
          this.list[data.index].priority = data.value.id
        } else if (data.type == 'change-name') {
          this.list[data.index].task_name = data.value
        } else if (data.type == 'change-comments') {
          let commentcount = this.list[data.index].commentcount
          if (data.value == 'add') {
            this.list[data.index].commentcount = commentcount + 1
          } else {
            this.list[data.index].commentcount = commentcount - 1
          }
        } else if (data.type == 'change-sub-task') {
          this.list[data.index].subdonecount = data.value.subdonecount
          this.list[data.index].subcount =
            data.value.allcount - data.value.subdonecount
        }
      }
    },

    /**
     * 关闭详情页
     */
    closeBtn() {
      this.taskDetailShow = false
    },

    /**
     * 点击空白处关闭详情
     */
    taskShowHandle(e) {
      if (
        this.$refs.particulars &&
        !this.$refs.particulars.$el.contains(e.target)
      ) {
        let hidden = true
        let items = document.getElementsByClassName('item-list')
        for (let index = 0; index < items.length; index++) {
          const element = items[index]
          if (element.contains(e.target)) {
            hidden = false
            break
          }
        }
        this.taskDetailShow = !hidden
      }
    }
  }
}
</script>

<style scoped lang="scss">
.recycle {
  height: 100%;
  overflow: hidden;
  position: relative;
  .recycle-header {
    height: 60px;
    line-height: 60px;
    position: relative;
    z-index: 100;
    padding: 0 20px;
    font-size: 18px;
  }
  .content {
    background-color: white;
    position: absolute;
    top: 60px;
    right: 0;
    bottom: 0;
    left: 0;
    border-radius: 3px;
    overflow-y: auto;
    border: 1px solid #e6e6e6;
  }
}
</style>
