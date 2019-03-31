<template>
  <div class="b-cont">
    <div>
      <sections class="b-cells"
                title="基本信息"
                m-color="#46CDCF"
                content-height="auto">
        <flexbox :gutter="0"
                 wrap="wrap"
                 style="padding: 10px 8px 0;">
          <flexbox-item :span="item.form_type === 'map_address' ? 12 : 0.5"
                        v-for="(item, index) in list"
                        v-if="item.form_type !== 'product'"
                        :key="index"
                        :class="{'b-cell' :item.form_type !== 'map_address'}">
            <flexbox v-if="item.form_type === 'map_address'"
                     :gutter="0"
                     wrap="wrap">
              <flexbox-item :span="0.5"
                            @click.native="checkMapView(item)"
                            class="b-cell">
                <flexbox class="b-cell-b"
                         align="stretch">
                  <div class="b-cell-name">定位</div>
                  <div class="b-cell-value"
                       style="color: #3E84E9;cursor: pointer;">{{item.value.location}}</div>
                </flexbox>
              </flexbox-item>
              <flexbox-item :span="0.5"
                            class="b-cell">
                <flexbox class="b-cell-b"
                         align="stretch">
                  <div class="b-cell-name">区域</div>
                  <div class="b-cell-value">{{item.value.address | addressShow}}</div>
                </flexbox>
              </flexbox-item>
              <flexbox-item :span="0.5"
                            class="b-cell">
                <flexbox class="b-cell-b"
                         align="stretch">
                  <div class="b-cell-name">详细地址</div>
                  <div class="b-cell-value">{{item.value.detail_address}}</div>
                </flexbox>
              </flexbox-item>
            </flexbox>

            <flexbox v-else-if="item.form_type === 'customer' || item.form_type === 'business' || item.form_type === 'contract' || item.form_type === 'contacts'"
                     align="stretch"
                     class="b-cell-b">
              <div class="b-cell-name">{{item.name}}</div>
              <div class="b-cell-value">{{item.value&&item.value.length > 0 ? (item.form_type === 'contract' ? item.value[0].num : item.value[0].name) : ''}}
              </div>
            </flexbox>

            <flexbox v-else-if="item.form_type === 'user'"
                     align="stretch"
                     class="b-cell-b">
              <div class="b-cell-name">{{item.name}}</div>
              <div class="b-cell-value">
                <flexbox :gutter="0"
                         wrap="wrap"
                         style="padding: 0px 10px 10px 0px;">
                  <div v-for="(item, index) in item.value"
                       :key="index">
                    {{item.realname}}&nbsp;&nbsp;
                  </div>
                </flexbox>
              </div>
            </flexbox>

            <flexbox v-else-if="item.form_type === 'structure'"
                     align="stretch"
                     class="b-cell-b">
              <div class="b-cell-name">{{item.name}}</div>
              <div class="b-cell-value">
                <flexbox :gutter="0"
                         wrap="wrap"
                         style="padding: 0px 10px 10px 0px;">
                  <div v-for="(item, index) in item.value"
                       :key="index">
                    {{item.name}}&nbsp;&nbsp;
                  </div>
                </flexbox>
              </div>
            </flexbox>

            <flexbox v-else-if="item.form_type === 'checkbox'"
                     align="stretch"
                     class="b-cell-b">
              <div class="b-cell-name">{{item.name}}</div>
              <div class="b-cell-value">
                <flexbox :gutter="0"
                         wrap="wrap"
                         style="padding: 0px 10px 10px 0px;">
                  <div v-for="(item, index) in item.value"
                       :key="index">
                    {{item}}&nbsp;&nbsp;
                  </div>
                </flexbox>
              </div>
            </flexbox>

            <flexbox v-else-if="item.form_type === 'file'"
                     align="stretch"
                     class="b-cell-b">
              <div class="b-cell-name">{{item.name}}</div>
              <div class="b-cell-value">
                <flexbox class="f-item"
                         v-for="(file, index) in item.value"
                         :key="index">
                  <img class="f-img"
                       src="@/assets/img/relevance_file.png" />
                  <div class="f-name">{{file.name.length > 15 ? (file.name.substring(0, 15) + '...'): file.name+'('+file.size+')'}}</div>
                  <el-button type="text"
                             @click.native="handleFile('preview', item, index)">预览</el-button>
                  <el-button type="text"
                             @click.native="handleFile('download', file, index)">下载</el-button>
                </flexbox>
              </div>
            </flexbox>

            <flexbox v-else
                     align="stretch"
                     class="b-cell-b">
              <div class="b-cell-name">{{item.name}}</div>
              <div class="b-cell-value">{{item.value}}</div>
            </flexbox>
          </flexbox-item>
        </flexbox>
      </sections>
    </div>
    <map-view v-if="showMapView"
              :title="mapViewInfo.title"
              :lat="mapViewInfo.lat"
              :lng="mapViewInfo.lng"
              @hidden="showMapView=false"></map-view>
  </div>
</template>

<script>
import loading from '../mixins/loading'
import Sections from '../components/Sections'
import { filedGetField } from '@/api/customermanagement/common'
import { getDateFromTimestamp } from '@/utils'
import moment from 'moment'
import MapView from '@/components/MapView' // 地图详情
import { downloadFile } from '@/utils'

export default {
  /** 客户管理 的 基本信息*/
  name: 'c-r-m-base-info',
  components: {
    Sections,
    MapView
  },
  mixins: [loading],
  filters: {
    addressShow: function(list) {
      return list ? list.join(' ') : ''
    }
  },
  props: {
    /** 模块ID */
    id: [String, Number],
    /** 没有值就是全部类型 有值就是当个类型 */
    crmType: {
      type: String,
      default: ''
    }
  },
  watch: {
    id: function(val) {
      this.getBaseInfo()
    }
  },
  data() {
    return {
      list: [],
      showMapView: false, // 控制展示地图详情
      mapViewInfo: {} // 地图详情信息
    }
  },
  computed: {},
  mounted() {
    this.getBaseInfo()
  },
  activated: function() {},
  deactivated: function() {},
  methods: {
    // 获取基础信息
    getBaseInfo() {
      this.loading = true
      filedGetField({
        types: 'crm_' + this.crmType,
        module: 'crm',
        controller: this.crmType,
        action: 'read',
        action_id: this.id
      })
        .then(res => {
          var self = this
          this.list = res.data.map(function(item, index) {
            return self.handleShowInfo(item)
          })
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    handleShowInfo(item) {
      if (item.form_type === 'date' && item.value == '0000-00-00') {
        item.value = ''
      } else if (item.form_type === 'datetime') {
        if (item.value == 0 || !item.value) {
          item.value = ''
        } else {
          item.value = moment(getDateFromTimestamp(item.value)).format(
            'YYYY-MM-DD HH:mm:ss'
          )
        }
      }
      return item
    },
    /**
     * 查看地图详情
     */
    checkMapView(item) {
      if (item.value && item.value !== '') {
        this.mapViewInfo = {
          title: item.value.location,
          lat: item.value.lat,
          lng: item.value.lng
        }
        this.showMapView = true
      }
    },
    /**
     * 附件查看
     */
    handleFile(type, item, index) {
      if (type === 'preview') {
        var previewList = item.value.map(element => {
          element.url = element.file_path
          return element
        })
        this.$bus.emit('preview-image-bus', {
          index: index,
          data: previewList
        })
      } else if (type === 'download') {
        downloadFile({ path: item.file_path, name: item.name })
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.b-cont {
  position: relative;
  padding: 0 50px 20px 20px;
}

.b-cells {
  margin-top: 25px;
}

.b-cell {
  padding: 0 10px;
  .b-cell-b {
    width: auto;
    padding: 8px;
    .b-cell-name {
      width: 100px;
      margin-right: 10px;
      font-size: 13px;
      flex-shrink: 0;
      color: #777;
    }
    .b-cell-value {
      font-size: 13px;
      color: #333;
    }
    .b-cell-foot {
      flex-shrink: 0;
      display: block;
      width: 15px;
      height: 15px;
      margin-left: 8px;
    }
  }
}

.f-item {
  padding: 3px 0;
  height: 25px;
  .f-img {
    position: block;
    width: 15px;
    height: 15px;
    padding: 0 1px;
    margin-right: 8px;
  }
  .f-name {
    color: #666;
    font-size: 12px;
    margin-right: 10px;
  }
}
</style>
