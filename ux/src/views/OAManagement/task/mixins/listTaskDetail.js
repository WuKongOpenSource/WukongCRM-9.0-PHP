export default {
  components: {},
  data() {
    return {
      // 详情数据
      taskID: '',
      detailIndex: -1,
      taskDetailShow: false
    }
  },

  mounted() {},

  methods: {
    // 关闭详情页
    closeBtn() {
      this.taskDetailShow = false
    },
    // 点击显示详情
    showDetailView(val, index, result) {
      this.taskID = val.task_id
      this.detailIndex = index
      this.taskDetailShow = true
      if (result) {
        result()
      }
    },
    detailHandle(data) {
      if (data.index == 0 || data.index) {
        // 是否完成勾选
        if (data.type == 'title-check') {
          this.$set(this.list[data.index], 'checked', data.value)
        } else if (data.type == 'delete') {
          this.list.splice(data.index, 1)
        } else if (data.type == 'change-stop-time') {
          const stopTime = parseInt(data.value) + 86399
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
          const commentcount = this.list[data.index].commentcount
          if (data.value == 'add') {
            this.list[data.index].commentcount = commentcount + 1
          } else {
            this.list[data.index].commentcount = commentcount - 1
          }
        } else if (data.type == 'change-sub-task') {
          this.list[data.index].subdonecount = data.value.subdonecount
          this.list[data.index].subcount = data.value.allcount - data.value.subdonecount
        }
      }
    }
  },

  deactivated: function() {}

}
