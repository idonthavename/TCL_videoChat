Page({

  /**
   * 页面的初始数据
   */
  data: {
    roomID: '10086',
    userID: '',
    userSig: '',
    sdkAppID: '',
    beauty: 5,
    muted: true,
    debug: true,
    enableIM: false
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var self = this;
    wx.request({
      url: 'http://10.68.213.239/videochat/public/chatweb/conf?token=eyJpdiI6Ik11UjNYUWNwNENDZXVDYXhNT0YwSWc9PSIsInZhbHVlIjoiSG9sR0xsVVZaS1NyUlNHNGx3RjYwUWh2UVVpNlJUemNnWWlNMzl1T1JxZEs0WEg1V0Zzd3F3VEttWEw1ZDQxUW4xVkR6ZUdtRGVRT1BhN2JjTm9cL2VCRHd6K1dRM1FwbkxqQjlKcE5ITmVUQVwvWDgyQ3p3UWY1UWJQSmdPbFpYRWVTMlk0eW9RVDdJdElBdjFRT0d5MGc9PSIsIm1hYyI6ImJhNGU3MzczYzI4YjNkYWUyZTdlMjMzOGVlNTNjYmE5Y2M3YjhlMWVmMjVkNmQwNmNlNzFlOTNlNjk4MzljZTUifQ%3D%3D&timestamp=1530504743',
      method: "post",
      data: {},
      success: function(res){
        console.log(res)
        res.data = res.data.data
        self.setData({
          userID: res.data.userId,
          userSig: res.data.userSig,
          sdkAppID: res.data.sdkappid,
          roomID: res.data.roomid,
          privateMapKey: res.data.privMapEncrypt
        }, function () {
          var webrtcroomCom = this.selectComponent('#webrtcroom');
          if (webrtcroomCom) {
            webrtcroomCom.start();
          }
        })
      }
    })
  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {
    
  },


  onRoomEvent: function (e) {
    switch (e.detail.tag) {
      case 'error': {
        //发生错误
        var code = e.detail.code;
        var detail = e.detail.detail;
        break;
      }
    }
  },
  onIMEvent: function (e) {
    switch (e.detail.tag) {
      case 'big_group_msg_notify':
        //收到群组消息
        console.debug(e.detail.detail)
        break;
      case 'login_event':
        //登录事件通知
        console.debug(e.detail.detail)
        break;
      case 'connection_event':
        //连接状态事件
        console.debug(e.detail.detail)
        break;
      case 'join_group_event':
        //进群事件通知
        console.debug(e.detail.detail)
        break;
    }
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {
    
  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {
    
  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {
    
  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
    
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
    
  }
})