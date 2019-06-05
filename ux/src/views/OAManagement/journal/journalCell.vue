<template>
  <div class="list"
       :id="'journal-cell' + logIndex">
    <div class="list-content">
      <div class="header">
        <div v-photo="data.create_user_info"
             class="div-photo head-img header-circle"
             :key="data.create_user_info.thumb_img"
             v-lazy:background-image="$options.filters.filterUserLazyImg(data.create_user_info.thumb_img)">
        </div>
        <div class="row">
          <p class="row-title">
            <span class="name">{{data.create_user_info.realname}}</span>
            <span v-if="showWorkbench"
                  class="item-content">{{data.action_content}}</span>
            <span v-else
                  class="read"
                  :style="{'color': data.is_read == 0 ? '#3E84E9' : '#ccc'}">{{data.is_read == 0 ? '未读' : '已读'}}</span>
          </p>
          <span class="time">{{data.create_time | moment("YYYY-MM-DD HH:mm")}}</span>
          <el-tooltip :disabled="!(data.sendUserList.length > 0 || data.sendStructList.length > 0)"
                      placement="bottom"
                      effect="light"
                      popper-class="tooltip-change-border">
            <div slot="content">
              <div class="members-dep-title">
                <!-- hover员工 -->
                <span v-if="data.sendUserList">
                  <!-- 如果没有部门而且员工最后一个 - 不显示逗号 -->
                  <span v-for="(k, i) in data.sendUserList"
                        :key="i">
                    {{data.sendStructList.length == 0 && i == data.sendUserList.length-1 ? k.realname : k.realname + "，"}}
                  </span>
                </span>
                <!-- hover部门 -->
                <span v-for="(dep, depIndex) in data.sendStructList"
                      :key="depIndex"
                      v-if="data.sendStructList.legnth != 0">
                  {{ depIndex == data.sendStructList.length-1 ? dep.name : dep.name+"，"}}
                </span>
              </div>
            </div>
            <p class="row-title"
               style="display: inline-block;">
              <span v-if="data.sendStructList">{{data.sendStructList.length}} 个部门，</span>
              <span v-if="data.sendUserList">{{data.sendUserList.length}}个同事</span>
            </p>
          </el-tooltip>
        </div>
        <div class="rt-setting"
             v-if="!showWorkbench && (data.permission && (data.permission.is_update || data.permission.is_delete))">
          <el-dropdown @command="handleCommand"
                       trigger="click">
            <i style="color:#CDCDCD; cursor: pointer;"
               class="el-icon-arrow-down el-icon-more"></i>
            <el-dropdown-menu slot="dropdown">
              <el-dropdown-item v-if="data.permission.is_update"
                                command="edit">编辑</el-dropdown-item>
              <el-dropdown-item v-if="data.permission.is_delete"
                                command="delete">删除</el-dropdown-item>
            </el-dropdown-menu>
          </el-dropdown>
        </div>
      </div>
      <div class="text">
        <p class="row"
           v-if="data.content">
          <span class="title">{{data.category_id == 1 ? "今日工作内容" : data.category_id == 2 ? "本周工作内容" : "本月工作内容"}}：</span>{{data.content}}</p>
        <p class="row"
           v-if="data.tomorrow">
          <span class="title">{{data.category_id == 1 ? "明日工作内容" : data.category_id == 2 ? "下周工作内容" : "下月工作内容"}}：</span>{{data.tomorrow}}</p>
        <p class="row"
           v-if="data.question">
          <span class="title">遇到的问题：</span>{{data.question}}</p>
      </div>
      <div class="accessory">
        <div class="upload-img-box"
             v-if="data.imgList.length != 0">
          <div v-for="(imgItem, k) in data.imgList"
               :key="k"
               class="img-list"
               @click="imgZoom(data.imgList, k)">
            <img v-lazy="imgItem.file_path"
                 :key="imgItem.file_path">
          </div>
        </div>
        <div class="accessory-box"
             v-if="data.fileList.length != 0">
          <file-cell v-for="(file, fileIndex) in data.fileList"
                     :key="fileIndex"
                     :data="file"
                     :cellIndex="fileIndex"></file-cell>
        </div>
      </div>
      <!-- 关联业务 -->
      <related-business v-if="allDataShow"
                        :marginLeft="'0'"
                        :alterable="false"
                        :allData="allData"
                        @checkRelatedDetail="checkRelatedDetail">
      </related-business>
      <!-- 评论 -->
      <div class="discuss"
           v-if="data.replyList.length != 0">
        <div class="border"></div>
        <div class="discuss-list"
             v-for="(discussItem, k) in data.replyList"
             :key="k">
          <div v-photo="discussItem.userInfo"
               v-lazy:background-image="$options.filters.filterUserLazyImg(discussItem.userInfo.thumb_img)"
               :key="discussItem.userInfo.thumb_img"
               class="div-photo head-img header-circle"></div>
          <span class="name">{{discussItem.userInfo.realname}}</span>
          <span class="time">{{discussItem.create_time | moment("YYYY-MM-DD HH:mm")}}</span>

          <p class="reply-title">
            <span v-html="emoji(discussItem.content)"></span>
            <i @click="discussBtn(discussItem, -1)"
               class="wukong wukong-log-reply log-handle"></i>
            <i @click="discussDelete(discussItem, data.replyList, k)"
               class="wukong wukong-log-delete log-handle"></i>
          </p>

          <p class="discuss-content"
             v-html="emoji(discussItem.reply_content)"></p>

          <div class="children-reply"
               v-if="discussItem.replyList && discussItem.replyList.length > 0">
            <div class="discuss-list"
                 v-for="(childDiscussItem, k) in discussItem.replyList"
                 :key="k">
              <div v-photo="childDiscussItem.userInfo"
                   v-lazy:background-image="$options.filters.filterUserLazyImg(childDiscussItem.userInfo.thumb_img)"
                   :key="childDiscussItem.userInfo.thumb_img"
                   class="div-photo head-img header-circle"></div>
              <span class="name">{{childDiscussItem.userInfo.realname}}</span>
              <span class="time">{{childDiscussItem.create_time | moment("YYYY-MM-DD HH:mm")}}</span>
              <p class="reply-title">
                <template>
                  <span>回复</span>
                  <span class="reply">@{{childDiscussItem.replyuserInfo.realname}}：</span>
                </template>
                <span v-html="emoji(childDiscussItem.content)"></span>
                <i class="wukong wukong-log-reply log-handle"
                   @click="discussBtn(discussItem, k)"></i>
                <i class="wukong wukong-log-delete log-handle"
                   @click="discussDelete(childDiscussItem, discussItem.replyList, k)"></i>
              </p>
            </div>
          </div>

          <!-- 评论 -- 回复  -->
          <div class="comment-box"
               v-if="discussItem.show">
            <el-input type="textarea"
                      @blur="blurFun"
                      :rows="2"
                      placeholder="请输入内容"
                      v-model="childCommentsTextarea"></el-input>
            <div class="btn-group">
              <el-popover placement="top"
                          width="400"
                          v-model="childCommentsPopover"
                          trigger="click">
                <!-- 表情 -->
                <emoji @select="childSelectEmoji">
                </emoji>
                <img src="@/assets/img/smiling_face.png"
                     class="smiling-img"
                     slot="reference">
              </el-popover>
              <div class="btn-box">
                <el-button type="primary"
                           @click="childCommentSubmit()"
                           :loading="contentLoading">回复</el-button>
                <el-button @click="discussItem.show= false">取消</el-button>
              </div>
            </div>
          </div>
          <div class="border"></div>
        </div>
      </div>
    </div>
    <div class="footer">
      <el-button type="primary"
                 icon="el-icon-chat-line-round"
                 @click="commentBtn(data)">回复</el-button>
    </div>
    <!-- 底部评论 -->
    <div class="comment-box"
         v-if="data.showComment">
      <el-input v-model="commentsTextarea"
                @blur="blurFun"
                type="textarea"
                :rows="3"
                placeholder="请输入内容"></el-input>
      <div class="btn-group">
        <el-popover placement="top"
                    width="400"
                    v-model="commentsPopover"
                    trigger="click">
          <!-- 表情 -->
          <emoji @select="selectEmoji">
          </emoji>
          <img src="@/assets/img/smiling_face.png"
               class="smiling-img"
               slot="reference">
        </el-popover>
        <div class="btn-box">
          <el-button type="primary"
                     @click="commentSubmit(data)"
                     :loading="contentLoading">回复</el-button>
          <el-button @click="data.showComment = false">取消</el-button>
        </div>
      </div>
    </div>
  </div>
</template>
<script type="text/javascript">
import emoji from '@/components/emoji'
// API
import {
  journalCommentDelete,
  journalCommentSave,
  journalSetread
} from '@/api/oamanagement/journal'
// 关联业务 - 选中列表
import relatedBusiness from '@/components/relatedBusiness'

import { mapGetters } from 'vuex'
import FileCell from '@/views/OAManagement/components/fileCell'

export default {
  name: 'journal-cell', // 日志cell
  components: {
    emoji,
    relatedBusiness,
    FileCell
  },
  mixins: [],
  computed: {
    ...mapGetters(['userInfo']),
    allData() {
      let allData = {}
      allData.business = this.data.businessList
      allData.contacts = this.data.contactsList
      allData.contract = this.data.contractList
      allData.customer = this.data.customerList
      return allData
    },
    allDataShow() {
      // 工作台不展示
      if (this.showWorkbench) {
        return false
      }
      if (
        this.data.businessList.length != 0 ||
        this.data.contactsList.length != 0 ||
        this.data.contractList.length != 0 ||
        this.data.customerList.length != 0
      ) {
        return true
      } else {
        return false
      }
    }
  },
  watch: {},
  data() {
    return {
      // 评论
      commentsTextarea: '',
      // 回复数据
      childCommentsTextarea: '',
      // 评论 -- 表情
      commentsPopover: false,
      replyChildComment: null, // 被评论对象
      replyChildIndex: -1, // -1 是主评论 0以上为子评论
      childCommentsPopover: false,
      blurIndex: 0,
      contentLoading: false,
      // 父元素
      parentTarget: null,
      awaitMoment: false // 等客户浏览
    }
  },
  props: {
    data: Object,
    logIndex: {
      type: Number,
      default: 0
    },
    // 工作台操作动态展示
    showWorkbench: {
      type: Boolean,
      default: false
    }
  },
  mounted() {
    if (this.data.is_read == 0 && !this.showWorkbench) {
      this.$bus.on('journal-list-box-scroll', target => {
        this.observePreview(target)
      })
      this.observePreview(
        document.getElementById('journal-cell' + this.logIndex).parentNode
      )
    }
  },
  methods: {
    /**
     * 观察预览
     */
    observePreview(target) {
      if (this.data.is_read == 0) {
        if (target) {
          this.parentTarget = target
        }
        let ispreview = this.whetherPreview()
        if (!this.awaitMoment && ispreview) {
          this.awaitMoment = true
          setTimeout(() => {
            this.awaitMoment = false
            let ispreview = this.whetherPreview()
            if (ispreview) {
              this.submiteIsRead()
            }
          }, 3000)
        }
      }
    },
    /**
     * 是否预览
     */
    whetherPreview() {
      let dom = this.parentTarget.children[this.logIndex]
      if (this.parentTarget.getBoundingClientRect()) {
        let offsetTop =
          this.parentTarget.getBoundingClientRect().top -
          dom.getBoundingClientRect().top
        let ispreview = false
        if (
          offsetTop <= 0 &&
          Math.abs(offsetTop) < this.parentTarget.clientHeight
        ) {
          ispreview = true
        } else if (offsetTop > 0 && offsetTop < dom.clientHeight) {
          ispreview = true
        }
        return ispreview
      } else {
        return false
      }
    },
    submiteIsRead() {
      journalSetread({
        log_id: this.showWorkbench ? this.data.action_id : this.data.log_id
      })
        .then(res => {
          this.data.is_read = 1
          this.$store.dispatch('GetOAMessageNum', 'log')
        })
        .catch(err => {})
    },
    verifyAwaitInfo() {},
    // 编辑 删除
    handleCommand(command) {
      this.$emit('on-handle', { type: command, data: { item: this.data } })
    },
    checkRelatedDetail(type, data) {
      this.$emit('on-handle', {
        type: 'related-detail',
        data: { type: type, item: data }
      })
    },
    // 评论删除
    discussDelete(val, items, index) {
      this.$confirm('确定删除?', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      })
        .then(() => {
          journalCommentDelete({
            comment_id: val.comment_id,
            log_id: val.type_id
          }).then(res => {
            this.$message({
              type: 'success',
              message: '删除成功!'
            })
            items.splice(index, 1)
            this.$emit('on-handle', {
              type: 'discuss-delete',
              data: { item: this.data }
            })
          })
        })
        .catch(() => {
          this.$message({
            type: 'info',
            message: '已取消删除'
          })
        })
    },
    // 回复 -- 显示输入框
    discussBtn(item, index) {
      if (item.show) {
        this.$set(item, 'show', false)
        this.replyChildComment = null
      } else {
        this.$set(item, 'show', true)
        this.$set(item, 'showComment', false)
        this.replyChildComment = item
        this.replyChildIndex = index
      }
    },
    //子评论 回复 -- 提交
    childCommentSubmit() {
      if (this.replyChildComment && this.childCommentsTextarea) {
        var item =
          this.replyChildIndex == -1
            ? this.replyChildComment
            : this.replyChildComment.replyList[this.replyChildIndex]
        this.contentLoading = true
        journalCommentSave({
          reply_fid: this.replyChildComment.comment_id,
          log_id: item.type_id,
          content: this.childCommentsTextarea,
          reply_content: item.content,
          reply_comment_id: item.comment_id,
          reply_user_id: item.userInfo.id,
          reply_name: item.userInfo.realname
        })
          .then(res => {
            this.replyChildComment.replyList.push({
              comment_id: res.data,
              type_id: item.type_id,
              userInfo: this.userInfo,
              create_time: parseInt(new Date().getTime() / 1000),
              content: this.childCommentsTextarea,
              reply_content: item.content,
              replyuserInfo: item.userInfo
            })
            this.$message.success('回复成功')
            this.replyChildComment.show = false
            this.replyChildComment = null

            this.contentLoading = false
            this.childCommentsTextarea = ''
            this.$emit('on-handle', {
              type: 'discuss-submit',
              data: { item: this.data }
            })
          })
          .catch(err => {
            this.$message.error('回复失败')
            this.contentLoading = false
          })
      }
    },
    // 评论 -- 提交
    commentSubmit(val) {
      if (this.commentsTextarea) {
        this.contentLoading = true
        journalCommentSave({
          log_id: this.showWorkbench ? this.data.action_id : this.data.log_id,
          content: this.commentsTextarea
        })
          .then(res => {
            // 插入一条数据
            val.showComment = false
            val.replyList.push({
              comment_id: res.data,
              type_id: this.showWorkbench
                ? this.data.action_id
                : this.data.log_id,
              userInfo: this.userInfo,
              create_time: parseInt(new Date().getTime() / 1000),
              content: this.commentsTextarea,
              replyList: [],
              show: false
            })
            this.commentsTextarea = ''
            this.$message.success('回复成功')
            this.contentLoading = false
          })
          .catch(err => {
            this.$message.error('回复失败')
            this.contentLoading = false
          })
      }
    },
    // 评论
    commentBtn(item) {
      if (item.showComment) {
        this.$set(item, 'showComment', false)
      } else {
        if (this.replyChildComment) {
          this.replyChildComment.show = false
        }
        this.$set(item, 'showComment', true)
      }
    },
    // 评论选中功能
    selectEmoji(val) {
      let list = this.commentsTextarea.split('')
      list.splice(this.blurIndex, 0, val)
      this.commentsTextarea = list.join('')
      this.commentsPopover = false
    },
    // 回复选中功能
    childSelectEmoji(val) {
      let list = this.childCommentsTextarea.split('')
      list.splice(this.blurIndex, 0, val)
      this.childCommentsTextarea = list.join('')
      this.childCommentsPopover = false
    },
    blurFun(eve) {
      this.blurIndex = eve.target.selectionEnd
    },
    // 放大图片
    imgZoom(val, k) {
      this.$bus.emit('preview-image-bus', {
        index: k,
        data: val.map(function(item, index, array) {
          return {
            url: item.file_path,
            name: item.name
          }
        })
      })
    }
  },
  beforeDestroy() {
    this.$bus.off('journal-list-box-scroll')
  }
}
</script>
<style lang="scss" scoped>
@import '../styles/content.scss';

.list {
  .list-content {
    padding: 20px;
    .header {
      margin-bottom: 15px;
      @include color9;
      font-size: 12px;
      .row {
        display: inline-block;
        vertical-align: middle;
        margin-left: 10px;
        .row-title {
          margin-bottom: 7px;
          @include v-align;
        }
        .el-tooltip {
          margin-bottom: 0;
        }
        .el-popover__reference {
          margin-bottom: 0;
          cursor: pointer;
          display: inline-block;
        }
      }
      .head-img {
        display: inline-block;
        width: 35px;
        height: 35px;
        border-radius: 17.5px;
        @include v-align;
      }
      .name,
      .time,
      .read,
      .head-img {
        @include v-align;
      }
      .read,
      .time {
        display: inline-block;
        margin-right: 10px;
      }
      .name {
        font-size: 15px;
        margin: 0 10px 0 0;
        color: #333333;
      }
      .rt-setting {
        float: right;
        line-height: 30px;
        .dep {
          color: #333333;
          margin-right: 20px;
        }
        img {
          width: 16px;
          @include cursor;
          @include v-align;
        }
      }
    }
    .text {
      .row {
        margin-bottom: 7px;
        line-height: 22px;
        font-size: 13px;
        white-space: pre-wrap;
        word-wrap: break-word;
        .title {
          width: 95px;
          text-align: left;
          display: inline-block;
          @include color9;
          font-size: 13px;
        }
      }
    }
    .accessory {
      .upload-img-box {
        margin: 10px 0;
        .img-list {
          display: inline-block;
          position: relative;
          margin-right: 10px;
          width: 80px;
          height: 60px;
          line-height: 60px;
          cursor: pointer;
          img {
            max-width: 80px;
            max-height: 60px;
          }
        }
      }
    }
    .discuss {
      margin-top: 20px;
      .border {
        height: 1px;
        background: #e6e6e6;
        margin: 15px -20px;
      }
      .discuss-list {
        .name,
        .time,
        .head-img {
          @include v-align;
        }
        .head-img {
          width: 25px;
          height: 25px;
          display: inline-block;
          border-radius: 12.5px;
        }
        .name {
          margin: 0 10px;
          font-size: 15px;
        }
        .time {
          color: #999;
          font-size: 12px;
          display: inline-block;
          margin-top: 3px;
        }

        .reply-title {
          color: #333;
          font-size: 13px;
          padding: 10px 10px 10px 40px;
          white-space: pre-wrap;
          word-wrap: break-word;
          span {
            letter-spacing: 0.5px;
            line-height: 18px;
          }
          .reply {
            color: #3e84e9;
          }
        }

        .reply-title:hover {
          .log-handle {
            display: inline;
          }
        }

        .children-reply {
          margin: 10px 0 10px 40px;
        }

        .log-handle {
          display: none;
          color: #999;
          font-size: 13px;
          margin-left: 5px;
        }

        .log-handle:hover {
          color: $xr-color-primary;
        }

        .discuss-content {
          background: #f5f7fa;
          color: #777;
          line-height: 36px;
          margin-left: 40px;
          padding-left: 15px;
        }
        .discuss-content /deep/ img {
          vertical-align: middle;
          margin: 0 3px;
        }
        .border {
          margin: 15px 0 15px 40px;
        }
        .comment-box {
          margin-left: 40px;
          padding: 0;
          background: transparent;
          margin-top: 15px;
        }
      }
      .discuss-list:last-child {
        .border {
          display: none;
        }
      }
    }
  }
  .footer {
    background: #f4f7fd;
    height: 40px;
    line-height: 40px;
    color: #ccc;
    text-align: right;
    padding-right: 20px;

    .log-handle {
      color: #999;
      font-size: 15px;
      margin-right: 20px;
    }

    .log-handle:hover {
      color: $xr-color-primary;
    }
  }
  .comment-box {
    margin: 20px;
    border: 1px solid #e6e6e6;
    .btn-group {
      padding: 0 20px 10px 10px;
      overflow: hidden;
      .btn-box {
        float: right;
      }
    }
    .btn-group /deep/ img {
      cursor: pointer;
    }
    .el-textarea /deep/ .el-textarea__inner {
      resize: none;
      border: 0;
    }
  }
}

.wukong {
  cursor: pointer;
}
</style>
