var config = require('../config.js');

var webrtcroom = {
  serverDomain: config.webrtcServerUrl,
  requestNum: 0,
  heart: '', // 判断心跳变量
  heartBeatReq: null,
  requestSeq: 0, // 请求id
  requestTask: [], // 请求task

  /**
   * [request 封装request请求]
   * @param {options}
   *   url: 请求接口url
   *   data: 请求参数
   *   success: 成功回调
   *   fail: 失败回调
   *   complete: 完成回调
   */
  request: function (options) {
    var self = this;
    self.requestNum++;
    var cookie = options.data.noNeedDomain ? wx.getStorageSync('tcl_videochat_cookie') : '';
    var req = wx.request({
      url: !options.data.noNeedDomain ? self.serverDomain : "" + options.url,
      data: options.data || {},
      method: "post",
      header: {
        'content-type': !options.data.noNeedDomain ? 'application/json' : 'application/x-www-form-urlencoded', // 默认值
        'Cookie': cookie,
      },
      // dataType: 'json',
      success: function (res) {
        if ((!options.data.noNeedDomain && res.data.code) || (options.data.noNeedDomain && res.data.status !== 200)) {
          console.error('服务器请求失败' + ', url=' + options.url + ', params = ' + (options.data ? JSON.stringify(options.data) : '') + ', 错误信息=' + JSON.stringify(res));
          options.fail && options.fail({
            errCode: res.data.status,
            errMsg: res.data.msg
          })
          wx.redirectTo({
            url: '/pages/error/error?content=' + (res.data.msg ? res.data.msg : '服务器请求失败')
          })
          return;
        }
        options.success && options.success(res);
      },
      fail: function (res) {
        console.error('请求失败' + ', url=' + options.url + ', 错误信息=' + JSON.stringify(res));
        options.fail && options.fail(res);
      },
      complete: options.complete || function () {
        self.requestNum--;
        // console.log('complete requestNum: ',requestNum);
      }
    });
    self.requestTask[self.requestSeq++] = req;
    return req;
  },

  /**
   * [clearRequest 中断请求]
   * @param {options}
   */
  clearRequest: function () {
    var self = this;
    for (var i = 0; i < self.requestSeq; i++) {
      self.requestTask[i].abort();
    }
    self.requestTask = [];
    self.requestSeq = 0;
  },


  getLoginInfo: function (userID, token, timestamp, success, fail) {
    var self = this;
    var data = {};
    if (userID) {
      data.userID = userID;
    }
    wx.login({
      success: function(login){
        if(login.code){
          wx.getUserInfo({
            lang: 'zh_CN',
            withCredentials: false,
            success: function(res){
              success(res.userInfo)
            },
            fail: function(error){
              wx.showModal({
                title: '提示',
                content: '请先授权',
                showCancel: false,
                complete: function(){
                  wx.redirectTo({
                    url: '/pages/main/main?token=' + token + '&timestamp=' + timestamp
                  });
                }
              })
            }
          })
        }else{
          wx.showModal({
            title: '提示',
            content: '登录信息还未获取到，请稍后再试',
          })
        }
      },
      fail: function(error){
        wx.showModal({
          title: '提示',
          content: '当前网络信号不稳定，获取用户信息失败',
        })
      }
    })
  },

  getRoomList: function (index, count, success, fail) {
    var self = this;
    self.request({
      url: '/get_room_list',
      data: {
        index: index,
        count: count
      },
      success: success,
      fail: fail
    })
  },

  createRoom: function (userID, roomInfo, success, fail) {
    var self = this;
    self.request({
      url: '/create_room',
      data: {
        userID: userID,
        roomInfo: roomInfo
      },
      success: function (res) {
        success && success(res);
      },
      fail: fail
    });
  },

  enterRoom: function (userID, roomID, token, timestamp, success, fail) {
    var self = this;
    self.request({
      // url: 'http://10.68.213.239/videochat/public/chatweb/conf',
      url: config.tclServiceUrl +'/conf',
      data: {
        userID: userID,
        roomID: roomID,
        token: token,
        timestamp: timestamp,
        noNeedDomain : 1
      },
      success: function (res) {
        success && success(res.data);
      },
      fail: fail
    })
  },

  quitRoom: function (userID, roomID, original, token, timestamp, success, fail) {
    var self = this;
    if(!original){
      console.error('退出异常，请联系TCL官方服务号客服');
    }else{
      self.request({
        // url: 'http://10.68.213.239/videochat/public/chatweb/quitRoom',
        url: config.tclServiceUrl +'/quitRoom',
        data: {
          original: original,
          token: token,
          timestamp: timestamp,
          noNeedDomain: 1
        },
        success: function(re){
          console.log(re.data.msg)
        },
        fail: fail
      });
    }
    self.stopHeartBeat();
  },

  imInTclRoomNotify: function (userID, token, timestamp, success, fail) {
    var self = this;
    self.request({
      // url: 'http://10.68.213.239/videochat/public/chatweb/onlineWhoRoleIs',
      url: config.tclServiceUrl +'/onlineWhoRoleIs',
      data: {
        userid: userID,
        token: token,
        timestamp: timestamp,
        noNeedDomain: 1
      },
      success: function (res) {
        success && success(res.data);
      },
      fail: fail
    })
  },

  imQuitTclRoomNotify: function (userID, token, timestamp, success, fail) {
    var self = this;
    self.request({
      //url: 'http://10.68.213.239/videochat/public/chatweb/checkQuitUserForRole',
      url: config.tclServiceUrl +'/checkQuitUserForRole',
      data: {
        userid: userID,
        token: token,
        timestamp: timestamp,
        noNeedDomain: 1
      },
      success: function (res) {
        success && success(res.data);
      },
      fail: fail
    })
  },

  anchorIsOnline: function (token, timestamp, success, fail) {
    var self = this;
    self.request({
      url: config.tclServiceUrl + '/anchorIsOnline',
      data: {
        token: token,
        timestamp: timestamp,
        noNeedDomain: 1
      },
      success: function (res) {
        success && success(res.data);
      },
      fail: fail
    })
  },

  startHeartBeat: function (userID, roomID, success, fail) {
    var self = this;
    self.heart = '1';
    self.heartBeat(userID, roomID, success, fail);
  },

  stopHeartBeat: function () {
    var self = this;
    self.heart = '';
    if (self.heartBeatReq) {
      self.heartBeatReq.abort();
      self.heartBeatReq = null;
    }
  },

  heartBeat: function (userID, roomID, success, fail) {
    var self = this;
    if (!self.heart) {
      self.clearRequest();
      return;
    }
    self.heartBeatReq = self.request({
      url: '/heartbeat',
      data: {
        userID: userID,
        roomID: roomID
      },
      success: function (res) {
        if (self.heart) {
          console.log('心跳成功');
          success && success(res);
          setTimeout(() => {
            self.heartBeat(userID, roomID, success, fail);
          }, 7000);
        }
      },
      fail: function (res) {
        fail && fail(res);
        if (self.heart) {
          setTimeout(() => {
            self.heartBeat(userID, roomID, success, fail);
          }, 7000);
        }
      }
    })
  }
}

module.exports = webrtcroom