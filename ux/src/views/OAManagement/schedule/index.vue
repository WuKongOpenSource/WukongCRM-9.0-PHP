<template>
  <div class="task-calendars">
    <div class="add-btn">
      <el-button
        type="primary"
        @click="newTask">
        创建日程
      </el-button>
    </div>
    <div
      ref="hoverDialog"
      class="hover-dialog">
      <div class="img-content">
        <span>{{ hoverDialogList.start_time | filterTimestampToFormatTime("YYYY-MM-DD") }}</span>
        <span v-if="hoverDialogList.end_time"> - {{ hoverDialogList.end_time | filterTimestampToFormatTime("YYYY-MM-DD") }}</span>
      </div>
      <div>
        {{ hoverDialogList.text }}
      </div>
    </div>
    <!-- 日历 -->
    <div
      v-loading="loading"
      id="calendar"/>
    <!-- 新建日程 -->
    <create-schedule
      v-if="showDialog"
      :text="newText"
      :form-data="formData"
      @onSubmit="onSubmit"
      @closeDialog="closeDialog"/>
    <!-- 详情 -->
    <v-details
      v-if="dialogVisible"
      :dialog-visible="dialogVisible"
      :list-data="listData"
      @editBtn="editBtn"
      @deleteClose="deleteClose"
      @handleClose="handleClose"/>
  </div>
</template>

<script>
import $ from 'jquery'
import 'fullcalendar/dist/locale/zh-cn.js'
import createSchedule from './components/createSchedule'
import VDetails from './components/details'
// API
import {
  scheduleList
} from '@/api/oamanagement/schedule'

import { timestampToFormatTime, getDateFromTimestamp } from '@/utils'

export default {
  components: {
    createSchedule,
    VDetails
  },
  data() {
    return {
      showDialog: false,
      hoverDialogList: {},
      dialogVisible: false,
      formData: {
        checkList: []
      },
      newtext: '',
      loading: true,
      // 详情数据
      listData: {}
    }
  },
  watch: {
    $route(to, from) {
      this.$router.go(0)
    }
  },
  created() {
    $(() => {
      this.listFun()
    })
  },
  methods: {
    // 初始化日历任务
    listFun() {
      $('#calendar').fullCalendar({
        height: document.documentElement.clientHeight - 101,
        nextDayThreshold: '00:00:00',
        dayClick: (date, jsEvent, view) => {
          this.newText = '创建日程'
          this.showDialog = true
          this.formData = {
            start_time: date.toDate(),
            end_time: date.toDate(),
            checkList: []
          }
        },
        // 点击显示详情
        eventClick: (val, key) => {
          this.showParticulars(val)
        },
        header: {
          left: 'today,   agendaDay,agendaWeek,month',
          center: 'prevYear,prev, title, next,nextYear',
          right: ''
        },
        eventMouseover: (event, jsEvent, view) => {
          this.$refs.hoverDialog.style.display = 'block'
          this.$refs.hoverDialog.style.width =
            document.getElementsByClassName('fc-day')[0].offsetWidth + 'px'
          this.$refs.hoverDialog.style.left =
            jsEvent.currentTarget.offsetLeft - 5 + 'px'
          this.$refs.hoverDialog.style.top =
            jsEvent.clientY - jsEvent.offsetY - 60 + 'px'
          this.hoverDialogList = {
            start_time: event.start_time,
            end_time: event.end_time,
            text: event.title,
            color: event.color,
            priority: event.priority
          }
        },
        eventMouseout: (event, jsEvent, view) => {
          this.$refs.hoverDialog.style.display = 'none'
        },
        events: (start, end, timezone, callback) => {
          scheduleList({
            start_time: start.unix(),
            end_time: end.unix()
          })
            .then(res => {
              const list = res.data.map(item => {
                item.start = timestampToFormatTime(
                  item.start_time,
                  'YYYY-MM-DD HH:mm:ss'
                )
                item.end = timestampToFormatTime(
                  item.end_time,
                  'YYYY-MM-DD HH:mm:ss'
                )
                item.textColor = '#333'
                return item
              })
              callback(list)
              this.loading = false
            })
            .catch(() => {
              this.loading = false
            })
        }
      })
    },
    // 详情数据
    showParticulars(val) {
      this.listData = val
      this.dialogVisible = true
    },
    // 详情关闭
    handleClose() {
      this.dialogVisible = false
    },
    // 详情删除
    deleteClose(val) {
      $('#calendar').fullCalendar('refetchEvents')
      this.handleClose()
    },
    // 详情编辑
    editBtn(val) {
      this.newText = '编辑日程'
      val.start_time = getDateFromTimestamp(val.start_time)
      val.end_time = getDateFromTimestamp(val.end_time)
      this.formData = val
      this.handleClose()
      this.showDialog = true
    },
    // 新建按钮
    newTask() {
      this.newText = '创建日程'
      this.showDialog = true
      this.formData = {
        checkList: []
      }
    },
    // 新建日程关闭按钮
    closeDialog() {
      this.showDialog = false
    },
    // 新建提交
    onSubmit(data, file) {
      if (this.newText == '创建日程') {
        this.$message.success('新建成功')
        $('#calendar').fullCalendar('refetchEvents')
        this.closeDialog()
      } else {
        this.$message.success('编辑成功')
        $('#calendar').fullCalendar('refetchEvents')
        this.closeDialog()
      }
    }
  }
}
</script>

<style>
@import 'fullcalendar/dist/fullcalendar.css';
</style>

<style lang="scss" scoped>
@import '@/styles/calendars.scss';

.task-calendars {
  position: relative;
  border: 1px solid #e6e6e6;
  border-radius: 4px;
  .hover-dialog {
    display: none;
    padding: 15px;
    z-index: 99;
    position: absolute;
    background: #fff;
    box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
    border-left: 2px solid transparent;
    .img-content {
      color: #999;
      margin-bottom: 10px;
      font-size: 12px;
      img {
        vertical-align: middle;
      }
    }
  }
  .add-btn {
    position: absolute;
    top: 14px;
    right: 40px;
  }
}
</style>

