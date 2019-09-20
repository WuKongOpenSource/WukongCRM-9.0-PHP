import {
  mapGetters
} from 'vuex'
export default {
  data() {
    return {
      hasRequestAuth: true // 请求是返回的权限
    }
  },
  props: {
    /** 是公海 默认是客户 */
    isSeas: {
      type: Boolean,
      default: false
    }
  },

  computed: {
    ...mapGetters(['crm']),
    // 能否查看详情
    canShowDetail() {
      if (!this.hasRequestAuth) {
        return false
      }
      return this.crm && this.crm[this.crmType] && this.crm[this.crmType].read
    }
  },

  watch: {
    id: function () {
      if (this.canShowDetail) {
        this.getDetial()
      }
    }
  },

  mounted() {
    if (this.canShowDetail) {
      this.getDetial()
    }
  },

  methods: {
    /** 顶部头 操作 */
    detailHeadHandle(data) {
      if (data.type === 'edit') {
        this.isCreate = true
      } else if (data.type === 'delete') {
        this.hideView()
      }
      this.$emit('handle', data)
    }
  },

  deactivated: function () { }

}
