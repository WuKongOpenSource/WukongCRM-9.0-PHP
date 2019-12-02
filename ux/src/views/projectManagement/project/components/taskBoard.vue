<template>
  <div
    v-loading="loading"
    class="content-box">
    <draggable
      v-scrollx="{ ignoreClass :['ignoreClass']}"
      id="task-board-body"
      :list="taskList"
      :options="{ group: 'mission', forceFallback: false, dragClass: 'sortable-parent-drag', filter: '.ignore-elements'}"
      :move="moveParentTask"
      handle=".board-column-wrapper"
      class="board-column-content-parent"
      @end="moveEndParentTask">
      <div
        v-for="(item, index) in taskList"
        :key="index"
        :class="{'ignore-elements': item.class_id == -1}"
        class="board-column">
        <flexbox
          orient="vertical"
          align="stretch"
          class="board-column-wrapper ignoreClass">
          <div class="board-column-header">
            <div>
              <span class="text"> {{ item.class_name }} </span>
              <span class="text-num">{{ item.checkedNum }} / {{ item.list.length }}</span>
              <el-popover
                v-if="canUpdateTaskClass && item.class_id != -1"
                v-model="item.taskHandleShow"
                placement="bottom-start"
                width="150"
                trigger="click">
                <div class="omit-popover-box">
                  <!-- 重命名 -->
                  <el-popover
                    v-model="item.renameShow"
                    :visible-arrow="false"
                    placement="bottom-start"
                    width="250"
                    popper-class="task-board-rechristen-popover"
                    trigger="click">
                    <div class="task-board-rechristen-box">
                      <div class="title">
                        <span>重命名</span>
                        <span
                          class="el-icon-close rt"
                          @click="item.renameShow = false"/>
                      </div>
                      <div class="content">
                        <el-input
                          :value="item.class_name"
                          v-model="renameInput"
                          size="mini"/>
                        <div class="btn-box">
                          <el-button
                            size="mini"
                            type="primary"
                            @click="renameSubmit(item)">保存</el-button>
                          <el-button
                            size="mini"
                            @click="item.renameShow = false">取消</el-button>
                        </div>
                      </div>
                    </div>
                    <p
                      slot="reference"
                      @click="renameTaskListClick(item)">重命名</p>
                  </el-popover>
                  <p
                    v-if="canCreateTask"
                    @click="createSubTaskClick(item)">新建任务</p>
                  <p
                    v-if="canUpdateTaskClass"
                    @click="archiveTaskListClick(item)">归档已完成任务</p>
                  <p
                    v-if="canDeleteTaskClass"
                    @click="delectTaskListClick(item, index)">删除列表</p>
                </div>
                <img
                  ref="imgPopoverSlot"
                  slot="reference"
                  class="img-gd"
                  src="@/assets/img/project/task_ellipsis.png">
              </el-popover>
            </div>
            <el-progress
              v-if="item.checkedNum == 0"
              :percentage="0"/>
            <el-progress
              v-else
              :percentage="item.checkedNum / item.list.length * 100"/>
          </div>
          <draggable
            :list="item.list"
            :options="{ group: {
              name: 'missionSon',
              put: item.class_id != -1
            }, forceFallback: false, dragClass: 'sortable-drag' }"
            :id="item.class_id"
            class="board-column-content"
            @end="moveEndSonTask">
            <div
              v-for="(element, i) in item.list"
              ref="taskRow"
              :key="i"
              :class="element.checked ? 'board-item board-item-active' : 'board-item'"
              :style="{'border-color': element.priority == 1 ? '#8bb5f0' : element.priority == 2 ? '#FF9668' : element.priority == 3 ? '#ED6363' : ''}"
              @click="showDetailView(element, index , i)">
              <div
                v-photo="element.main_user"
                v-lazy:background-image="$options.filters.filterUserLazyImg(element.main_user.thumb_img)"
                v-if="element.main_user"
                :key="element.main_user.thumb_img"
                class="head-png div-photo"/>
              <flexbox align="stretch">
                <div
                  style="display: inline-block;"
                  @click.stop>
                  <el-checkbox
                    v-model="element.checked"
                    @change="checkboxChange(element, item)"/>
                </div>
                <div class="element-label">{{ element.name }}</div>
              </flexbox>
              <div class="img-group">
                <div
                  v-if="element.stop_time"
                  class="img-box">
                  <i
                    :style="{'color': element.is_end == 1 && !element.checked ? 'red': '#999'}"
                    class="wukong wukong-time-task"/>
                  <span :style="{'color': element.is_end == 1 && !element.checked ? 'red': '#999'}">{{ element.stop_time | filterTimestampToFormatTime('MM-DD') }}截止</span>
                </div>
                <div
                  v-if="element.subcount || element.subdonecount"
                  class="img-box">
                  <i class="wukong wukong-sub-task"/>
                  <span>{{ element.subdonecount }}/{{ element.subcount + element.subdonecount }}</span>
                </div>
                <div
                  v-if="element.filecount"
                  class="img-box">
                  <i class="wukong wukong-file"/>
                  <span>{{ element.filecount }}</span>
                </div>
                <div
                  v-if="element.commentcount"
                  class="img-box">
                  <i class="wukong wukong-comment-task"/>
                  <span>{{ element.commentcount }}</span>
                </div>

                <template v-if="element.lableList.length <= 2">
                  <div
                    v-for="(k, j) in element.lableList"
                    :key="j"
                    :style="{'background': k.color}"
                    class="item-label">
                    {{ k.name }}
                  </div>
                </template>
                <template v-else>
                  <div
                    :style="{'background': element.lableList[0].color}"
                    class="item-label">{{ element.lableList[0].name }}</div>
                  <div
                    :style="{'background': element.lableList[1].color}"
                    class="item-label">{{ element.lableList[1].name }}</div>
                  <el-tooltip
                    placement="top"
                    effect="light"
                    popper-class="tooltip-change-border task-tooltip">
                    <div
                      slot="content"
                      style="margin: 10px 10px 10px 0;">
                      <div
                        v-for="(k, j) in element.lableList"
                        :key="j"
                        style="display: inline-block; margin-right: 10px;">
                        <span
                          v-if="j >= 2"
                          :style="{'background': k.color ? k.color: '#ccc'}"
                          class="k-name"
                          style="border-radius: 3px; color: #FFF; padding: 3px 10px;">{{ k.name }}</span>
                      </div>
                    </div>
                    <div class="color-label-more">
                      <i>...</i>
                    </div>
                  </el-tooltip>

                </template>
              </div>
            </div>
          </draggable>
          <!-- 新建任务 -->
          <div
            v-if="createSubTaskClassId == item.class_id"
            class="new-task-input">
            <el-input
              :rows="2"
              v-model="subTaskContent"
              type="textarea"
              placeholder="请输入内容"/>
            <div class="img-box">
              <span class="stop-time">
                <span
                  v-if="subTaskStopTimeDate"
                  class="bg-color">{{ subTaskStopTimeDate | moment('MM-DD') }}<i
                    class="el-icon-close"
                    @click="subTaskStopTimeDate = ''"/></span>
                <i
                  v-else
                  class="wukong wukong-time-task"/>
                <el-date-picker
                  v-model="subTaskStopTimeDate"
                  :style="{'width': subTaskStopTimeDate ? '54px' : '18px'}"
                  type="date"
                  placeholder="选择日期"/>
              </span>

              <el-popover
                v-model="item.ownerListShow"
                placement="bottom-start"
                width="250"
                style=""
                trigger="click">
                <div class="task-board-personnel-list-popover">
                  <el-input
                    size="mini"
                    placeholder="搜索成员"/>
                  <div class="list-box">
                    <div
                      v-for="(k, i) in ownerList"
                      :key="i"
                      :class="k.checked ? 'personnel-list personnel-list-active' : 'personnel-list'"
                      @click="selectOwnerList(item, k)">
                      <div
                        v-photo="k"
                        v-lazy:background-image="$options.filters.filterUserLazyImg(k.thumb_img)"
                        :key="k.thumb_img"
                        class="div-photo k-img"/>
                      <span>{{ k.realname }}</span>
                      <i class="el-icon-check"/>
                    </div>
                  </div>
                </div>
                <div
                  v-photo="selectMainUser"
                  v-lazy:background-image="$options.filters.filterUserLazyImg(selectMainUser.thumb_img)"
                  v-if="selectMainUser"
                  slot="reference"
                  :key="selectMainUser.thumb_img"
                  class="div-photo head-img"
                  @click="showOwnerListClick(item)"/>
                <i
                  v-else
                  slot="reference"
                  class="wukong wukong-user"
                  @click="showOwnerListClick(item)"/>
              </el-popover>
            </div>
            <div class="btn-box">
              <el-button
                size="mini"
                type="primary"
                @click="addSubTask(item)">确定</el-button>
              <el-button
                size="mini"
                @click="createSubTaskClassId = 'hidden'">取消</el-button>
            </div>
          </div>
          <div
            v-else-if="canCreateTask && item.class_id != -1"
            class="new-task"
            @click="createSubTaskClick(item)">
            <span class="el-icon-plus"/>
            <span>新建任务</span>
          </div>
        </flexbox>
      </div>

      <!-- 新建列表 -->
      <div
        v-if="canCreateTaskClass"
        class="board-column-new-list">
        <div
          v-if="!createTaskListShow && loading == false"
          class="new-list"
          @click="createTaskListShow = true">
          <span class="el-icon-plus"/>
          <span>新建列表</span>
        </div>
        <div
          v-else-if="createTaskListShow && loading == false"
          class="input-btn">
          <el-input
            v-model="taskListName"
            size="small"
            placeholder="列表名"/>
          <div class="button-box">
            <el-button
              size="mini"
              type="primary"
              @click="createTaskListSave">保存</el-button>
            <el-button
              size="mini"
              @click="createTaskListShow = false">取消</el-button>
          </div>
        </div>
      </div>
    </draggable>

    <!-- 详情 -->
    <particulars
      v-if="taskDetailShow"
      ref="particulars"
      :id="taskID"
      :detail-index="detailIndex"
      :detail-section="detailSection"
      @on-handle="detailHandle"
      @close="closeBtn"/>
  </div>
</template>
<script>
import newDialog from '@/views/projectManagement/components/newDialog'
import particulars from '@/views/projectManagement/components/particulars'
import draggable from 'vuedraggable'
import scrollx from '@/directives/scrollx'

import {
  workTaskSaveAPI,
  workTaskTaskOverAPI
} from '@/api/projectManagement/task'
import {
  workTaskclassSaveAPI,
  workTaskclassRenameAPI,
  workTaskclassDeleteAPI,
  workTaskIndexAPI,
  workTaskArchiveTaskAPI,
  workTaskUpdateOrderAPI,
  workTaskUpdateClassOrderAPI,
  workWorkOwnerListAPI
} from '@/api/projectManagement/project'


export default {
  components: {
    draggable,
    newDialog,
    particulars
  },

  directives: {
    scrollx
  },

  props: {
    workId: String,
    permission: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },

  data() {
    return {
      loading: false,
      // 新建任务弹出框
      createTaskListShow: false,
      createSubTaskClassId: 'hidden',
      subTaskContent: '',
      // 选中的人员数据
      selectMainUser: null,
      // 选择截止的时间
      subTaskStopTimeDate: '',
      // 人员列表
      ownerList: [],
      // 新建列表
      taskListName: '',
      // 重命名
      renameInput: '',
      // 主数据
      taskList: [],
      // 详情对应的任务对象数据 -- 用于更新数据
      // 详情数据
      taskID: '',
      detailIndex: -1,
      detailSection: -1,
      taskDetailShow: false
    }
  },

  computed: {
    /**
     * 可以新建任务
     */
    canCreateTask() {
      return this.permission.task && this.permission.task.save
    },

    /**
     * 可以创建任务列表
     */
    canCreateTaskClass() {
      return this.permission.taskclass && this.permission.taskclass.save
    },

    /**
     * 可以编辑任务列表
     */
    canUpdateTaskClass() {
      return this.permission.taskclass && this.permission.taskclass.update
    },

    /**
     * 可以删除任务列表
     */
    canDeleteTaskClass() {
      return this.permission.taskclass && this.permission.taskclass.delete
    }
  },

  watch: {
    workId() {
      this.createTaskListShow = false
      this.taskDetailShow = false
      this.taskList = []
      this.getList()
    }
  },

  created() {
    this.getList()
    // 筛选
    this.$bus.$on('search', (userIds, timeId, tagIds) => {
      this.getList({
        main_user_id: userIds,
        stop_time_type: timeId,
        lable_id: tagIds
      })
    })

    // 成员列表
    this.$bus.$on('members-update', userList => {
      this.ownerList = userList
    })
  },

  mounted() {
    // 为了防止火狐浏览器拖拽的时候以新标签打开，此代码真实有效
    document.body.ondrop = function(event) {
      event.preventDefault()
      event.stopPropagation()
    }

    document
      .getElementById('project-main-container')
      .addEventListener('click', this.taskShowHandle, false)
  },

  beforeDestroy() {
    this.$bus.$off('search')
    this.$bus.$off('members-update')
  },

  methods: {
    /**
     * 获取所有数据
     */
    getList(params) {
      if (params) {
        params.work_id = this.workId
      } else {
        params = { work_id: this.workId }
      }
      this.loading = true
      workTaskIndexAPI(params)
        .then(res => {
          this.loading = false
          this.taskList = res.data
          for (const item of this.taskList) {
            item.checkedNum = 0
            for (const i of item.list) {
              if (i.checked) {
                item.checkedNum += 1
              }
            }
          }
        })
        .catch(() => {
          this.loading = false
        })
    },

    /**
     * 列表拖拽
     */
    moveEndParentTask(evt) {
      if (evt && evt.oldIndex != evt.newIndex) {
        workTaskUpdateClassOrderAPI({
          work_id: this.workId,
          class_ids: this.taskList.map(item => {
            return item.class_id
          })
        })
          .then(res => {})
          .catch(() => {})
      }
    },

    moveParentTask(evt) {
      if (
        evt.draggedContext.futureIndex == 0 &&
        this.taskList[0].class_id == -1
      ) {
        return false
      }
    },

    /**
     * 任务拖拽
     */
    moveEndSonTask(evt) {
      if (evt) {
        const fromId = evt.from.id
        const toId = evt.to.id

        // 如果没有进行移动 不做处理
        if (fromId == toId && evt.oldIndex == evt.newIndex) {
          return
        }

        const fromList = this.taskList.filter(item => {
          return item.class_id == fromId
        })[0].list

        const toList = this.taskList.filter(item => {
          return item.class_id == toId
        })[0].list

        let params = {}
        if (fromId == toId) {
          params = {
            tolist: toList.map(item => {
              return item.task_id
            }),
            toid: toId
          }
        } else {
          params = {
            fromlist: fromList.map(item => {
              return item.task_id
            }),
            fromid: fromId,
            tolist: toList.map(item => {
              return item.task_id
            }),
            toid: toId
          }
        }
        workTaskUpdateOrderAPI(params)
          .then(res => {})
          .catch(() => {})
      }
    },

    /**
     * 勾选
     */
    checkboxChange(element, value) {
      if (element.checked) {
        value.checkedNum++
      } else {
        value.checkedNum--
      }
      workTaskTaskOverAPI({
        task_id: element.task_id,
        type: element.checked ? 1 : 2
      })
        .then(res => {})
        .catch(() => {
          if (element.checked) {
            value.checkedNum--
          } else {
            value.checkedNum++
          }
          element.checked = !element.checked
        })
    },

    /**
     * 删除列表
     */
    delectTaskListClick(val, index) {
      this.$confirm('您确定要删除?', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      })
        .then(() => {
          workTaskclassDeleteAPI({
            class_id: val.class_id,
            work_id: this.workId
          })
            .then(res => {
              this.taskList.splice(index, 1)
              this.$message.success('删除成功')
              val.taskHandleShow = false // 隐藏弹出框
            })
            .catch(() => {})
        })
        .catch(() => {
          this.$message({
            type: 'info',
            message: '已取消删除'
          })
        })
    },

    /**
     * 归档已完成任务
     */
    archiveTaskListClick(val) {
      workTaskArchiveTaskAPI({
        class_id: val.class_id
      }).then(res => {
        this.$message.success('归档成功')
        val.taskHandleShow = false
        this.getList()
      })
    },

    /**
     * 重命名
     */
    renameTaskListClick(val) {
      this.renameInput = val.class_name
      val.taskHandleShow = false
    },

    /**
     * 重命名 -- 提交
     */
    renameSubmit(val) {
      workTaskclassRenameAPI({
        name: this.renameInput,
        class_id: val.class_id
      })
        .then(res => {
          val.class_name = this.renameInput
        })
        .catch(() => {})
      val.renameShow = false
    },

    /**
     * 新建列表提交
     */
    createTaskListSave() {
      workTaskclassSaveAPI({
        name: this.taskListName,
        work_id: this.workId
      })
        .then(res => {
          this.getList()
        })
        .catch(() => {})
      this.createTaskListShow = false
    },

    /**
     * 新建任务
     */
    addSubTask(val) {
      this.loading = true
      workTaskSaveAPI({
        name: this.subTaskContent,
        stop_time: this.subTaskStopTimeDate
          ? new Date(this.subTaskStopTimeDate).getTime() / 1000
          : '',
        main_user_id: this.selectMainUser.id,
        work_id: this.workId,
        class_id: val.class_id
      })
        .then(res => {
          this.createSubTaskClassId = 'hidden'
          this.loading = false
          this.getList()
        })
        .catch(() => {
          this.loading = false
        })
    },

    /**
     * 展示成员列表
     */
    showOwnerListClick(item) {
      item.ownerListShow = true
      if (this.ownerList.length == 0) {
        this.getOwnerList()
      }
    },

    /**
     * 获取成员
     */
    getOwnerList() {
      workWorkOwnerListAPI({
        work_id: this.workId
      }).then(res => {
        this.ownerList = res.data
      })
    },

    /**
     * 创建新任务
     */
    createSubTaskClick(val) {
      this.subTaskContent = ''
      this.subTaskStopTimeDate = ''
      this.selectMainUser = ''

      this.createSubTaskClassId = val.class_id

      if (val.taskHandleShow) {
        val.taskHandleShow = false
      }
    },

    /**
     * 新建任务 -- 选择人员
     */
    selectOwnerList(item, val) {
      this.selectMainUser = val
      for (const item of this.ownerList) {
        if (item.id == val.id) {
          item.checked = true
        } else {
          item.checked = false
        }
      }
      // 关闭弹出框
      item.ownerListShow = false
    },

    /**
     * 点击显示详情
     */
    showDetailView(val, section, index) {
      this.taskID = val.task_id
      this.detailIndex = index
      this.detailSection = section
      this.taskDetailShow = true
    },

    /**
     * 详情操作
     */
    detailHandle(data) {
      if (data.index == 0 || data.index) {
        // 是否完成勾选
        if (data.type == 'title-check') {
          const sectionItem = this.taskList[data.section]
          this.$set(sectionItem.list[data.index], 'checked', data.value)
          if (data.value) {
            sectionItem.checkedNum++
          } else {
            sectionItem.checkedNum--
          }
          this.$set(sectionItem, 'checkedNum', sectionItem.checkedNum)
        } else if (data.type == 'delete') {
          this.taskList[data.section].list.splice(data.index, 1)
        } else if (data.type == 'change-stop-time') {
          const stopTime = parseInt(data.value) + 86399
          if (stopTime > new Date(new Date()).getTime() / 1000) {
            this.taskList[data.section].list[data.index].is_end = false
          } else {
            this.taskList[data.section].list[data.index].is_end = true
          }
          this.taskList[data.section].list[data.index].stop_time = data.value
        } else if (data.type == 'change-priority') {
          this.taskList[data.section].list[data.index].priority = data.value.id
        } else if (data.type == 'change-name') {
          this.taskList[data.section].list[data.index].name = data.value
        } else if (data.type == 'change-comments') {
          const commentcount = this.taskList[data.section].list[data.index]
            .commentcount
          if (data.value == 'add') {
            this.taskList[data.section].list[data.index].commentcount =
              commentcount + 1
          } else {
            this.taskList[data.section].list[data.index].commentcount =
              commentcount - 1
          }
        } else if (data.type == 'change-sub-task') {
          this.taskList[data.section].list[data.index].subdonecount =
            data.value.subdonecount
          this.taskList[data.section].list[data.index].subcount =
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
        const items = document.getElementsByClassName('board-item')
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
.content-box {
  height: 100%;
  overflow-y: hidden;
  overflow-x: auto;
  position: relative;
  white-space: nowrap;
  .board-column-content-parent {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    overflow-y: hidden;
    overflow-x: auto;
  }
}

.omit-popover-box {
  margin-left: -12px;
  margin-right: -12px;
  p {
    font-size: 13px;
    color: #333;
    height: 26px;
    line-height: 26px;
    padding-left: 20px;
    cursor: pointer;
  }
  p:hover {
    background: #f7f8fa;
    color: #3e84e9;
  }
}
.task-board-personnel-list-popover {
  .list-box {
    margin-top: 10px;
    height: 300px;
    overflow-y: auto;
    .personnel-list {
      height: 30px;
      line-height: 30px;
      font-size: 13px;
      padding: 0 10px;
      cursor: pointer;
      img {
        vertical-align: middle;
      }
      .el-icon-check {
        opacity: 0;
        float: right;
        color: #ccc;
        font-size: 16px;
        margin-top: 6px;
      }
    }
    .personnel-list-active {
      background: #f3f3f3;
      .el-icon-check {
        opacity: 1;
      }
    }
    .k-img {
      width: 24px;
      height: 24px;
      border-radius: 12px;
      vertical-align: middle;
      margin-right: 5px;
    }
  }
}
.task-board-rechristen-popover {
  padding: 0;
  .task-board-rechristen-box {
    .title {
      border-bottom: 1px solid #e6e6e6;
      padding: 15px;
      .el-icon-close {
        margin-right: 0px;
      }
    }
    .content {
      padding: 0 15px;
      .el-input {
        margin: 15px 0;
      }
      .btn-box {
        text-align: right;
        margin-bottom: 15px;
      }
    }
  }
}

.board-column {
  width: 280px;
  height: 100%;
  display: inline-block;
  margin: 0 7px;
  overflow: hidden;

  .board-column-wrapper {
    max-height: 100%;
    padding: 10px;
    vertical-align: top;
    border-radius: 4px;
    background: #fff;
    margin-right: 14px;
    position: relative;
    .board-column-header {
      padding: 10px 3px 17px 3px;
      color: #333;
      .text {
        font-size: 15px;
        display: inline-block;
        padding-bottom: 5px;
      }
      .el-progress /deep/ .el-progress-bar {
        padding-right: 0;
      }
      .el-progress /deep/ .el-progress__text {
        display: none;
      }
      .text-num {
        margin-left: 10px;
        color: #999;
      }
      .img-gd {
        float: right;
        width: 15px;
        cursor: pointer;
      }
    }
    .board-column-content {
      margin-right: -10px;
      padding-right: 14px;
      padding-left: 7px;
      flex: 1;
      overflow: auto;
      .board-item {
        box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
        padding: 10px 35px 0 10px;
        margin-bottom: 10px;
        margin-top: 1px;
        margin-left: 1px;
        border-radius: 3px;
        border-left: 2px solid transparent;
        cursor: pointer;
        overflow: hidden;
        position: relative;
        .img-group {
          padding: 10px 0 0 20px;
          white-space: normal;
          word-wrap: break-word;
          overflow: auto;
          font-size: 12px;
          .img-box {
            display: inline-block;
            margin-right: 5px;
            margin-bottom: 7px;
            color: #999;
            font-size: 12px;
            img {
              width: 15px;
              vertical-align: middle;
            }
          }
        }
        .head-png {
          vertical-align: middle;
          position: absolute;
          top: 8px;
          right: 5px;
          width: 24px;
          height: 24px;
          border-radius: 50%;
        }
        .element-label {
          width: 180px;
          padding-left: 8px;
          white-space: pre-wrap;
          word-wrap: break-word;
          line-height: 18px;
          cursor: pointer;
        }
      }
      .board-item-active {
        box-shadow: none;
        border: 0;
        background: #f3f3f3;
        color: #8f8f8f;
        .element-label {
          text-decoration: line-through;
        }
      }
    }
    .new-task {
      cursor: pointer;
      color: #999999;
      padding-top: 10px;
      font-size: 13px;
      padding-left: 4px;
      .el-icon-plus {
        color: #3e84e9;
        font-weight: 700;
      }
    }
    .new-task-input {
      // position: absolute;
      // bottom: 0;
      .img-box {
        text-align: right;
        margin: 15px 0 10px;
        position: relative;
        color: #999;
        min-height: 24px;
        i {
          cursor: pointer;
          vertical-align: middle;
        }

        .stop-time {
          position: relative;
          vertical-align: middle;
        }

        .el-date-editor {
          position: absolute;
          top: 0;
          right: 0;
          bottom: 0;
          left: 0;
          opacity: 0;
        }
        .el-date-editor /deep/ .el-input__inner {
          padding: 0;
          height: 18px;
          .el-input__icon {
            line-height: 18px;
          }
        }
        .el-date-editor /deep/ .el-input__prefix {
          left: 0;
          right: 0;
          .el-input__icon {
            width: 100%;
          }
        }
        .el-date-editor /deep/ .el-input__suffix {
          display: none;
        }
        .bg-color {
          background: #e6e6e6;
          padding: 3px 10px;
          border-radius: 14px;
          .el-icon-close {
            font-size: 14px;
            color: #ccc;
          }
        }
        .head-img {
          vertical-align: middle;
          width: 24px;
          height: 24px;
          border-radius: 12px;
          margin-left: 7px;
        }
      }
      .btn-box {
        text-align: right;
      }
      .el-textarea /deep/ .el-textarea__inner {
        resize: none;
      }
    }
  }
}

.board-column-new-list {
  width: 280px;
  background: #fff;
  vertical-align: top;
  display: inline-block;
  border-radius: 4px;
  .new-list {
    height: 50px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: #999;
    cursor: pointer;
    .el-icon-plus {
      color: #3e84e9;
      font-size: 12px;
      font-weight: 700;
      margin-right: 10px;
    }
  }
  .input-btn {
    height: 100px;
    padding: 20px;
    .button-box {
      float: right;
      margin-top: 10px;
    }
  }
}

.sortable-drag {
  background-color: white;
}

.sortable-parent-drag {
  background-color: transparent;
}

.wukong {
  font-size: 14px;
}

.item-label {
  display: inline-block;
  display: inline-block;
  height: 20px;
  line-height: 20px;
  padding: 0 10px;
  border-radius: 3px;
  color: #fff;
  margin-right: 6px;
  font-size: 12px;
  margin-bottom: 6px;
}

.color-label-more {
  background: #eee;
  width: 34px;
  height: 20px;
  line-height: 20px;
  text-align: center;
  display: inline-block;
  font-size: inherit;
  font-weight: 700;
  border-radius: 3px;
  vertical-align: middle;
  position: relative;
  margin-bottom: 6px;
  i {
    position: absolute;
    left: 50%;
    line-height: 36px;
    top: 0%;
    height: 20px;
    transform: translate(-50%, -50%);
  }
}
</style>

