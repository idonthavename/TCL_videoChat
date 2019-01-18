/**
 * Created by tt on 2018/6/8.
 */
/*
 写在前面：
 为了保持在功能演示方面的简洁， demo不会做任何合法性校验
 */

// 本demo用到的唯一一个CGI，获取usersig （什么是usersig? 请看 https://sxb.qcloud.com/webrtcapi/ )
// 如果您不了解非对称加密，可以这样简单理解：
// 你有公钥 和 私钥 两把钥匙，腾讯云有一把钥匙（公玥）
// 你把数据丢盒子里，并且用私钥上锁，然后把上了锁的盒子给到腾讯云
// 腾讯云可以用公钥这把钥匙来解开这把锁，拿到里面的数据。
// 所以你需要的是
// 去控制台把私钥下载下来，用TLS工具算一个签名（usersig)

//不要把您的sdkappid填进来就用这个cgi去测，测试demo的cgi没有您的私钥，臣妾做不到啊

//以下判断浏览器是否支持webrtc
navigator.getUserMedia ||
(navigator.getUserMedia = navigator.mozGetUserMedia ||  navigator.webkitGetUserMedia || navigator.msGetUserMedia);

if (!navigator.getUserMedia) {
    goingPlay = 0;
    $('#modal2Desc').text("抱歉，您的浏览器不支持WebRTC，请使用谷歌(推荐)、QQ浏览器、Safari(11.1.2以上)");
    $('[data-remodal-id=modal2]').remodal({
        modifier: 'with-red-theme',
        closeOnEscape: false,
        closeOnOutsideClick: false
    }).open();
}

window.onbeforeunload = function(){
    return true;
}

window.onunload = function () {
    checkLeave();
    onWebSocketClose();
}

function onKickout() {
    alert("on kick out!");
}

function onRelayTimeout(msg) {
    alert("onRelayTimeout!" + (msg ? JSON.stringify(msg) : ""));
}

function createVideoElement( id, isLocal, userid){
    whoIs(userid,function (json) {
        if (json.status == 200){
            console.log('local: '+isLocal+' role is:'+json.data.role);
            if (!isLocal){
                var roles = {'anchor':'坐席','supporter':'支援工程师','company':'客户'};
                spopNotify('success',roles[json.data.role]+'进入房间');
            }
            if (json.data.role == 'company') {
                $("#wattingForCompany").hide();
                $("#"+id).show();
                clearTimeout(companyInTimer);
            }
            if (isLocal){
                H5role = json.data.role;
                if (H5role == 'company'){
                    notifyCompanyQMT(1);
                    QMTtimer = window.setInterval("notifyCompanyQMT(1)", 60000);
                }else{
                    notifyQMT(1);
                    QMTtimer = window.setInterval("notifyQMT(1)",60000);
                }
            }
        }else {
            console.error(json.msg);
        }
    },function (error) {
        console.error(error);
        alert('视频初始化失败');
    });
    var videoDiv=document.createElement("div");
    videoDiv.innerHTML = '<video id="'+id+'" autoplay '+ (isLocal ? 'muted' : '') +' playsinline poster="images/chatweb/p1_bg.png" class="companyVideo" style="display: none;"></video>';
    document.querySelector("#remote-video-wrap").appendChild(videoDiv);
    return document.getElementById(id);
}

function onLocalStreamAdd(info) {
    if (info.stream && info.stream.active === true)
    {
        var id = "local";
        var video = document.getElementById(id);
        if(!video){
            createVideoElement(id, true, localGlobal);
        }
        var video = document.getElementById(id)
        video.srcObject = info.stream;
        video.muted = true
        video.autoplay = true
        video.playsinline = true
    }

    RTC.getVideoDevices( function(devices){
        //devices 是枚举当前设备的视频输入设备的数组(DeviceObject)
        // 例如 ：[device,device,device]
        // 这些device将在选择摄像头的时候使用
        console.log(devices);
        videoDevices = devices;

        //后置镜头
        //前置相机
        //FaceTime 高清摄像头
        //$.each(devices,function (key,val) {
        //    console.log(val.label);
        //})
    });
}

function onRemoteStreamUpdate( info ) {
    console.debug( info )
    if (info.stream && info.stream.active === true)
    {
        var id = info.videoId;
        var video = document.getElementById(id);
        if(!video){
            video = createVideoElement(id, false, info.userId);
        }
        video.srcObject = info.stream;
    } else{
        console.log('欢迎用户'+ info.userId+ '加入房间');
    }
}


function onRemoteStreamRemove( info ) {
    console.log( info.userId+ ' 断开了连接');
    var videoNode = document.getElementById( info.videoId );
    if( videoNode ){
        videoNode.srcObject = null;
        document.getElementById(info.videoId).parentElement.removeChild(videoNode);
    }
    setTimeout(function () {
        checkQuitUserForRole(info.userId,
            function (json) {
                if(json && json.status === 200 ){
                    if (json.data.role == "company"){
                        $('#modal3Desc').text(json.msg);
                        $('[data-remodal-id=modal3]').remodal({
                            modifier: 'with-red-theme',
                            closeOnEscape: false,
                            closeOnOutsideClick: true
                        }).open();
                        $("#wattingForCompany").show();
                    }else{
                        spopNotify('error',json.msg);
                    }
                }else{
                    console.error(json.msg);
                }
            },function (err){
                console.error(err);
            }
        );
    },1000);
}

function onWebSocketClose() {
    RTC.quit();
}

function initRTC(opts){
    // 初始化
    window.RTC = new WebRTCAPI({
        //"useCloud":1,
        "userId": opts.userId,
        "userSig": opts.userSig,
        "sdkAppId": opts.sdkappid,
        "accountType": opts.accountType,
        "closeLocalMedia": opts.closeLocalMedia
    },function(){
        RTC.createRoom({
            roomid : opts.roomid,
            privateMapKey: opts.privateMapKey,
            role : "user",
            /*pstnBizType: parseInt($("#pstnBizType").val() || 0),
             pstnPhoneNumber:  $("#pstnPhoneNumber").val()*/
        });
        if (opts.anchorIsOnline == 0 && opts.anchorIsOnline != "notAllow"){
            $("#modal3Desc").text("坐席还未进入房间，请稍后");
            $('[data-remodal-id=modal3]').remodal({
                modifier: 'with-red-theme',
                closeOnEscape: false,
                closeOnOutsideClick: true
            }).open();
        }
    },function( error ){
        console.error("init error", error)
    });

    // 远端流新增/更新
    RTC.on("onRemoteStreamUpdate",onRemoteStreamUpdate)
    // 本地流新增
    RTC.on("onLocalStreamAdd",onLocalStreamAdd)
    // 远端流断开
    RTC.on("onRemoteStreamRemove",onRemoteStreamRemove)
    // 重复登录被T
    RTC.on("onKickout",onKickout)
    // 服务器超时
    RTC.on("onRelayTimeout",onRelayTimeout)
    // just for debugging
    // RTC.on("*",function(e){
    //     console.debug(e)
    // });

    RTC.on("onErrorNotify", function( info ){
        console.error( info )
        /* info {
         errorCode: xxxx,
         errorMsg: "xxxxx"
         } */
    });

    var loginInfo = {
        sdkAppID: opts.sdkappid,
        appIDAt3rd: opts.sdkappid,
        identifier: opts.userId,
        identifierNick: '主播',
        accountType: opts.accountType,
        userSig: opts.userSig
    };
    console.debug('initIM');
    IM.login(loginInfo, {
            "onBigGroupMsgNotify": onBigGroupMsgNotify,
            "onMsgNotify": onMsgNotify
        },
        function (resp) {
            IM.joinGroup(""+opts.roomid+"", opts.userId)
        },
        function (err) {
            alert(err.ErrorInfo);
        }
    );
}
$("#userId").val("video_"+ parseInt(Math.random()*100000000));

function push(){
    //推流
    login( false );
}

function audience(){
    //不推流
    login( true );
}

function stopRTC(){
    RTC.stopRTC(0 , function( info ){
        console.debug( info )
    },function( info ){
        console.debug( info )
    });
}
function startRTC(){
    RTC.startRTC(0 , function( info ){
        console.debug( info )
    },function( info ){
        console.debug( info )
    });
}

function muted(yes){
    if (yes){
        RTC.closeAudio();
    }else{
        RTC.openAudio();
    }
}

function chooseVideo(device){
    RTC.chooseVideoDevice(device);
}

function safariChooseVideo(videoIndex){
    //采集音视频流
    RTC.getLocalStream({
        video:true,
        audio:true,
        videoDevice: {
            facingMode: {
                ideal: videoIndex === 0 ? 'user': 'environment'
            }
        }
    },function(info){
        //更新音视频流
        RTC.updateStream( {
            stream: info.stream
        })
    });
}

Bom = {
    /**
     * @description 读取location.search
     *
     * @param {String} n 名称
     * @return {String} search值
     * @example
     * 		$.bom.query('mod');
     */
    query:function(n){
        var m = window.location.search.match(new RegExp( "(\\?|&)"+n+"=([^&]*)(&|$)"));
        return !m ? "":decodeURIComponent(m[2]);
    },
    getHash:function(n){
        var m = window.location.hash.match(new RegExp( "(#|&)"+n+"=([^&]*)(&|$)"));
        return !m ? "":decodeURIComponent(m[2]);
    }
};

function login( closeLocalMedia ){
    //请使用英文半角/数字作为用户名
    $.ajax({
        type: "POST",
        url: FetchSigCgi,
        dataType: 'json',
        headers: {'X-CSRF-TOKEN': csrf},
        data:{
            token: GetQueryString('token'),
            timestamp: GetQueryString('timestamp'),
        },
        success: function (json) {
            if(json && json.status === 200 ){
                //一会儿进入房间要用到
                var userId = localGlobal = json.data.userId;
                var sdkappid = json.data.sdkappid;
                var accountType = json.data.accountType;
                var roomid = roomGlobal = json.data.roomid;
                var userSig = json.data.userSig;
                var privateMapKey = json.data.privMapEncrypt;
                var anchorIsOnline = json.data.anchorIsOnline;
                // 页面处理，显示视频流页面
                $("#video-section").show();
                $("#input-container").hide();

                initRTC({
                    "userId": userId,
                    "userSig": userSig,
                    "privateMapKey": privateMapKey,
                    "sdkappid": sdkappid,
                    "accountType": accountType,
                    "closeLocalMedia": closeLocalMedia,
                    "roomid": roomid,
                    "anchorIsOnline": anchorIsOnline
                });
            }else{
                console.error(json);
                spopNotify(json.msg);
            }
        },
        error: function (err){
            var data = JSON.parse(err.responseText)
            $('#modal2Desc').text(err);
            $('[data-remodal-id=modal2]').remodal({
                modifier: 'with-red-theme',
                closeOnEscape: false,
                closeOnOutsideClick: false,

            }).open();
        }
    })
}

function onMsgNotify(msgs) {
    if (msgs) console.log(msgs);
    var self = this;
    if (msgs && msgs.length > 0) {
        var msgsObj = IM.parseMsgs(msgs)
        msgsObj.textMsgs.forEach((msg) => {
            var content = JSON.parse(msg.content);
        if (content.cmd === 'sketchpad') {
            var body = JSON.parse(content.data.msg);
            if (body.type == 'request' && body.action == 'currentBoard') {
                if (this.$refs.sketchpadCom) {
                    var currentBoard = this.$refs.sketchpadCom.getCurrentBoard();
                    var boardBg = this.$refs.sketchpadCom.getBoardBg() || {};
                    IM.sendBoardMsg({
                        groupId: this.courseId,
                        msg: JSON.stringify({
                            action: body.action,
                            currentBoard: currentBoard
                            //,boardBg: JSON.stringify(boardBg)
                        }),
                        nickName: this.selfName,
                        identifier: this.userID
                    });

                    // 如果有图片则补发图片
                    var bgUrl = boardBg[currentBoard] && boardBg[currentBoard].url;
                    if (bgUrl) {
                        this.sendBoardBgPicMsg(currentBoard, bgUrl);
                        setTimeout(() => {
                            this.sendSwitchBoardMsg(currentBoard);
                    }, 500);
                    }
                }
            }
        }
    })
    }
}

function onBigGroupMsgNotify(newMsgList) {
    if (newMsgList) console.log(newMsgList);
    var self = this;
    if (newMsgList && newMsgList.length > 0) {
        var msgsObj = IM.parseMsgs(newMsgList)
        this.msgs = [];
        this.msgs = this.msgs.concat(msgsObj.textMsgs);
        if (!this.isRoomCreator) {
            var whiteBoardMsgs = msgsObj.whiteBoardMsgs || [];
            whiteBoardMsgs.forEach((item, index) => {
            (function (index, item) {
                setTimeout(() => {
                    self.inputSketchpadData = item;
            }, index * 50);
            })(index, item);
        });
        }
    }
}

function GetQueryString(name) {
    var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
    var r = window.location.search.substr(1).match(reg);
    if(r!=null)return  unescape(r[2]); return null;
}

function checkLeave(){
    window.clearTimeout(QMTtimer);
    if (H5role == 'company'){
        notifyCompanyQMT(2);
    }else{
        notifyQMT(2);
    }
    $.ajax({
        type: "POST",
        url: quitRoomCgi,
        dataType: 'json',
        headers: {'X-CSRF-TOKEN': csrf},
        data:{
            token: GetQueryString('token'),
            timestamp: GetQueryString('timestamp')
        },
        success: function (json) {
            if(json.status !== 200 ){
                console.log(json.msg);
            }
            IM.quitGroup(""+roomGlobal+"");
            IM.logout();
        },
        error: function (err){
            console.error(err);
        }
    });
}

function checkQuitUserForRole(userid,success,error) {
    $.ajax({
        type: "POST",
        url: checkUseridCgi,
        dataType: 'json',
        headers: {'X-CSRF-TOKEN': csrf},
        data:{
            token: GetQueryString('token'),
            timestamp: GetQueryString('timestamp'),
            userid: userid
        },
        success: success,
        error: error
    });
}

function whoIs(userid,success,error){
    $.ajax({
        type: "POST",
        url: onlineWhoRoleIsCgi,
        dataType: 'json',
        headers: {'X-CSRF-TOKEN': csrf},
        data:{
            token: GetQueryString('token'),
            timestamp: GetQueryString('timestamp'),
            userid: userid,
        },
        success: success,
        error: error
    });
}

function spopNotify(type, msg){
    //type(default/success/error/warning)
    spop({
        template: msg,
        group: 'tcl-videochat',
        style: type,
        autoclose: 5000,
        position  : 'top-left',
    });
}

function notifyQMT(type){
    $.ajax({
        type: "POST",
        url: notifyQMTCgi,
        dataType: 'json',
        headers: {'X-CSRF-TOKEN': csrf},
        data:{
            token: GetQueryString('token'),
            timestamp: GetQueryString('timestamp'),
            status: type,
        },
        success: function (res) {
            console.log(res.msg);
        },
        error: function (error) {
            console.debug('心跳失败：'+error);
        }
    });
}

function notifyCompanyQMT(type){
    $.ajax({
        type: "POST",
        url: notifyCompanyQMTCgi,
        dataType: 'json',
        headers: {'X-CSRF-TOKEN': csrf},
        data:{
            token: GetQueryString('token'),
            timestamp: GetQueryString('timestamp'),
            status: type,
        },
        success: function (res) {
            console.log(res.msg);
        },
        error: function (error) {
            console.debug('心跳失败：'+error);
        }
    });
}