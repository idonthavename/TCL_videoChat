// pages/main/main.js
var config = require('../../config.js')
var webrtcroom = require('../../utils/webrtcroom.js')
Page({

  /**
   * 页面的初始数据
   */
  data: {
    canShow: 0,
    tapTime: '',		// 防止两次点击操作间隔太快
    entryInfos: [
      { icon: "../Resources/edu_mini.png", title: "tcl", desc: "<webrtc-room>", navigateTo: "../videochat/videochat" }
    ]
  },

  onEntryTap: function (e) {
    if (this.data.canShow) {
      // if(1) {
      // 防止两次点击操作间隔太快
      var nowTime = new Date();
      if (nowTime - this.data.tapTime < 1000) {
        return;
      }
      var toUrl = this.data.entryInfos[e.currentTarget.id].navigateTo;
      console.log(toUrl);
      wx.navigateTo({
        url: toUrl,
      });
      this.setData({ 'tapTime': nowTime });
    } else {
      wx.showModal({
        title: '提示',
        content: '当前微信版本过低，无法使用该功能，请升级到最新微信版本后再试。',
        showCancel: false
      });
    }
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    console.log("onLoad");
    wx.hideShareMenu();
    if (!wx.createLivePlayerContext) {
      wx.showModal({
        title: '提示',
        content: '当前微信版本过低，无法使用该功能，请升级到最新微信版本后再试。',
        showCancel: false
      });
    }else{
      if (options.token && options.timestamp) {
        this.setData({
          token: options.token,
          timestamp: options.timestamp
        });
        var self = this;
        //查看是否授权
        wx.getSetting({
          success: function (res) {
            if (res.authSetting['scope.userInfo']) {
              wx.getUserInfo({
                success: function (res) {
                  //用户已经授权过
                  console.log(res.userInfo)

                  //检测房间是否合法、过期等
                  webrtcroom.confForCompany(self.data.token, self.data.timestamp,
                    function (res) {
                      wx.navigateTo({
                        url: '/pages/videochat/videochat?token=' + self.data.token + '&timestamp=' + self.data.timestamp
                      })
                    }, function () {
                      console.log("房间检测失败");
                    })
                }
              })
            }
          }
        })
      } else {
        wx.reLaunch({
          url: '/pages/error/error'
        })
      }
    }
  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {
    console.log("onReady");
    if(wx.createLivePlayerContext) {
      // 版本正确，允许进入
      this.data.canShow = 1;
    }
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    console.log("onShow");

  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {
    console.log("onHide");

  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {
    console.log("onUnload");

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {
    console.log("onPullDownRefresh");

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
    console.log("onReachBottom");

  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
    console.log("onShareAppMessage");
  },

  onGotUserInfo: function (e) {
    console.log(e.detail.userInfo)
    if (e.detail.userInfo) {
      console.log('ok');
      wx.navigateTo({
        url: '/pages/videochat/videochat?token=' + this.data.token + '&timestamp=' + this.data.timestamp
      });
    } else {
      wx.showToast({
        icon: 'none',
        title: '请勿拒绝授权！'
      });
    }
  }
})