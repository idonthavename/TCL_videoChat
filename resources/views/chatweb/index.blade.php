<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title>TCL在线音视频服务</title>
    <link rel="stylesheet" type="text/css" href="css/chatweb/index.css?t={{time()}}">
    <link rel="stylesheet" href="css/remodal/remodal.css">
    <link rel="stylesheet" href="css/remodal/remodal-default-theme.css">
    <link rel="stylesheet" href="css/spop/spop.min.css">
    <script type="text/javascript">
        var csrf = "{{csrf_token()}}";
        var FetchSigCgi = "{{url('/chatweb/conf')}}";
        var checkUseridCgi = "{{url('/chatweb/checkQuitUserForRole')}}";
        var quitRoomCgi = "{{url('/chatweb/quitRoom')}}";
        var onlineWhoRoleIsCgi = "{{url('/chatweb/onlineWhoRoleIs')}}";
        var notifyQMTCgi = "{{url('/chatweb/notifyQMT')}}";
        var localGlobal = "";
        var roomGlobal = 0;
        var QMTtimer = '';
        if (/Android (\d+\.\d+)/.test(navigator.userAgent)) {
            var version = parseFloat(RegExp.$1);
            if (version > 2.3) {
                var phoneScale = parseInt(window.screen.width) / 640;
                document.write('<meta name="viewport" content="width=640, minimum-scale = ' + phoneScale + ', maximum-scale = ' + phoneScale + ', target-densitydpi=device-dpi">');
            } else {
                document.write('<meta name="viewport" content="width=640, target-densitydpi=device-dpi">');
            }
        } else {
            document.write('<meta name="viewport" content="width=640, user-scalable=no, target-densitydpi=device-dpi">');
        }
    </script>
    <script type="text/javascript" src="js/chatweb/easeljs-0.7.1.min.js"></script>
    <script type="text/javascript" src="js/chatweb/tweenjs-0.5.1.min.js"></script>
    <script type="text/javascript" src="js/chatweb/movieclip-0.7.1.min.js"></script>
    <script type="text/javascript" src="js/chatweb/Utils.js"></script>
    <script type="text/javascript" src="js/chatweb/gif.js"></script>
</head>
<body onLoad="init();">
<div class="mask_content" id="mask_content">
    <img src="images/chatweb/guide.png">
    <p>请点击右上角通过Safari浏览器打开页面</p>
    <div class="mask opacity"></div>
</div>

<div data-remodal-id="modal2" role="dialog" aria-labelledby="modal2Title" aria-describedby="modal2Desc">
    <div>
        <h2 id="modal2Title">提示</h2>
        <p id="modal2Desc" style="margin-top: 2rem;"></p>
    </div>
    <br>
    {{--<button data-remodal-action="confirm" class="remodal-confirm">Hello!</button>--}}
</div>

<div data-remodal-id="modal3" role="dialog" aria-labelledby="modal3Title" aria-describedby="modal3Desc">
    <div>
        <h2 id="modal3Title">提示</h2>
        <p id="modal3Desc" style="margin-top: 2rem;"></p>
    </div>
    <br>
    <button data-remodal-action="confirm" class="remodal-confirm">确定</button>
</div>

<div class="holder">
    <img src="images/chatweb/share.png" style="position: absolute; top: 0;left: 0;opacity: 0;">

    <!--page1-->
    <div class="page1 abs">
        <!--<img class="abs p1_pic01" src="images/chatweb/p1_pic01.png" />-->
        <div class="abs p1_pic01">
            <img src="images/chatweb/share.png" style="width: 128px;height: 128px;border-radius: 128px;">
            <div style="font-size: 1.6rem; margin-top: 2rem;">连接中...</div>
        </div>
        <!--<img class="abs p1_pic02" src="images/chatweb/p1_pic02.png" />-->
        <div class="abs p1_box" id="drag_box">
            <canvas id="canvas" width="189" height="47"></canvas>
            <img id="drag_bar" src="images/chatweb/p1_bar.png" />
        </div>
    </div>

    <!--page2-->
    <div class="vid abs">
        <p id="wattingForCompany" style="color: white;width: 100%;text-align: center;margin-top: 50%;">请稍后，等待客户接入中..........</p>
        <!--<img class="vidImg abs" src="images/chatweb/p01.jpg" width="640" />-->
        <div id="remote-video-wrap"></div>
        <!--        <video id="mid" src="" width="640" height="1136" preload="preload" poster="music/p01.jpg" x-webkit-airplay="true"-->
        <!--               webkit-playsinline="true">-->
        <!--        </video>-->
        <!--<div class="zaodian abs"></div>-->
        <img class="stopVid abs" src="images/chatweb/stopVid.png" />
        <img class="playVid abs" src="images/chatweb/p1_bar.png"/>
    </div>

    <!--page3-->
    <div class="page2 abs">
        <div class="abs p1_pic01">
            <div style="font-size: 2rem; margin-top: 22.3%;">已结束通话....</div>
            <img class="stopVid on" src="images/chatweb/stopVid.png" style="opacity: 0.4;position: absolute;"/>
        </div>
    </div>
</div>

<script type="text/javascript" src="js/chatweb/jquery-1.9.1.min.js"></script>
<script type="text/javascript" src="js/chatweb/jquery.touchSwipe.min.js"></script>
<script type="text/javascript" src="js/chatweb/TweenMax.min.js"></script>
<script type="text/javascript" src="js/chatweb/Draggable.min.js"></script>
{{--<script type="text/javascript" src="js/chatweb/cache-1.0.min.js"></script>--}}
<script type="text/javascript" src="js/remodal/remodal.js"></script>
{{--<script src="https://sqimg.qq.com/expert_qq/webrtc/latest/WebRTCAPI.min.js"></script>--}}
<script src="https://sqimg.qq.com/expert_qq/webrtc/2.2/WebRTCAPI.min.js"></script>
<script type="text/javascript" src="js/chatweb/im.js"></script>
<script type="text/javascript" src="js/chatweb/webim.js"></script>
<script type="text/javascript" src="js/spop/spop.min.js"></script>
<script type="text/javascript" src="js/chatweb/tx.js?t={{time()}}"></script>
<script type="text/javascript" src="js/chatweb/video2.js?t={{time()}}"></script>
@if(config('app.debug'))
    <script src="https://sxb.qcloud.com/webrtc-samples/assets/libs/vconsole.min.js"></script>
    <script>
        var vConsole = new VConsole();
    </script>
@endif
</body>
</html>
