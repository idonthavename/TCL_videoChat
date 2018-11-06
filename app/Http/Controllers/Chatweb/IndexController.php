<?php

namespace App\Http\Controllers\Chatweb;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\WebRTCSigApi;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class IndexController extends Controller
{
    private $tx_config;
    private $redis;

    public function __construct(){
        $this->redis = app('redis.connection');
    }

    public function play(){
        return view('chatweb.index');
    }

    public function conf(Request $request){
        //配置项
        $this->tx_config = [
            'sdkappid'=>env('TX_SDKAPPID'),
            'accountType'=>env('TX_ACCOUNTTYPE'),
            'roomid'=>$request->roomid,
            'userid'=>md5($request->session()->getId().env('VIDEOCHAT_USER_SALT')),
            'original'=>$request->session()->getId()
        ];

        $anchorIsOnline = false;
        if ($request->role == 'company'){
            $anchorIsOnline = $this->redis->hexists('TCL_WEBRTCROOM_'.$request->roomid,'anchor');
            //if (!$anchorIsOnline) return response()->json(['status'=>-250,'msg'=>'亲，坐席还未进入房间哦，请稍等片刻']);
        }else{
            $anchorIsOnline = 'notAllow';
        }

        //redis判断角色权限，并限制入场人数
        $rolerData = $this->redis->hget('TCL_WEBRTCROOM_'.$request->roomid,$request->role);
        switch ($request->role){
            case 'supporter':
                $supporters = $rolerData ? explode(',',$rolerData) : [];
                $supporterNum = count($supporters);
                if (!in_array($this->tx_config['userid'],$supporters) && $supporterNum < 2){
                    $supporters[] = $this->tx_config['userid'];
                    $this->redis->hset('TCL_WEBRTCROOM_'.$request->roomid,$request->role,implode(',',$supporters));
                }elseif (!in_array($this->tx_config['userid'],$supporters) && $supporterNum >= 2){
                    return response()->json(['status'=>-200,'msg'=>'亲，您不在援助者名单上哦。']);
                }
                break;
            default:
                if (!isset($rolerData) || empty($rolerData) || $rolerData == $this->tx_config['userid']){
                    $this->redis->hset('TCL_WEBRTCROOM_'.$request->roomid,$request->role,$this->tx_config['userid']);
                }elseif ($rolerData && $rolerData != $this->tx_config['userid']){
                    $msg = $this->__isWeixin($request->header('user-agent')) ? 'outOfTime' : '亲请确定您是否在房间名单上哦';
                    return response()->json(['status'=>-200,'msg'=>$msg]);
                }
        }
        //每次有新成员进入房间刷新redis过期时间
        $this->redis->expireat('TCL_WEBRTCROOM_'.$request->roomid,time()+(3600*3));

        //生成txsdk  start
        $txsdk = new WebRTCSigApi();
        $txsdk->setSdkAppid($this->tx_config['sdkappid']);
        //读取公钥的内容
        $txsdk->setPublicKey(file_get_contents(CERTS_PATH.'public_key'));
        //读取私钥的内容
        $txsdk->setPrivateKey(file_get_contents(CERTS_PATH.'private_key'));
        //生成privMapEncrypt
        $privMapEncrypt = $txsdk->genPrivMapEncrypt($this->tx_config['userid'], $this->tx_config['roomid']);
        //生成userSig
        $userSig = $txsdk->genUserSig($this->tx_config['userid']);

        $ret = [
            'status'=>200,
            'msg'=>'Success',
            'data'=>[
                'userId'=>$this->tx_config['userid'],
                'sdkappid'=>$this->tx_config['sdkappid'],
                'accountType'=>$this->tx_config['accountType'],
                'roomid'=>$this->tx_config['roomid'],
                'original'=>$this->tx_config['original'],
                'userSig'=>$userSig,
                'privMapEncrypt'=>$privMapEncrypt,
                'anchorIsOnline'=>$anchorIsOnline,
            ]
        ];
        return response()->json($ret);
    }

    //退出房间，只记录五秒
    public function quitRoom(Request $request){
        $userid = $request->input('original') ? md5($request->input('original').env('VIDEOCHAT_USER_SALT')) : md5($request->session()->getId().env('VIDEOCHAT_USER_SALT'));
        $rolerData = $this->redis->hget('TCL_WEBRTCROOM_'.$request->roomid,$request->role);
        switch ($request->role){
            case 'supporter':
                $supporters = explode(',',$rolerData);
                if (in_array($userid,$supporters)){
                    $key = array_search($userid,$supporters);
                    unset($supporters[$key]);
                    $this->redis->hset('TCL_WEBRTCROOM_'.$request->roomid,$request->role,implode(',',$supporters));
                    $this->redis->setex('TCL_WEBRTCROOM_'.$request->roomid.'_'.$userid,60,$request->role);
                    return response()->json(['status'=>200,'msg'=>'退出房间成功']);
                }
                break;
            default:
                if (!isset($rolerData) || empty($rolerData)){
                    return response()->json(['status'=>200,'msg'=>'退出房间成功']);
                }elseif ($rolerData == $userid){
                    $this->redis->hdel('TCL_WEBRTCROOM_'.$request->roomid,$request->role);
                    $this->redis->setex('TCL_WEBRTCROOM_'.$request->roomid.'_'.$userid,60,$request->role);
                    return response()->json(['status'=>200,'msg'=>'退出房间成功']);
                }
        }
        $msg = $this->__isWeixin($request->header('user-agent')) ? 'outOfTime' : '退出房间异常';
        return response()->json(['status'=>-200,'msg'=>$msg]);
    }

    //检测退出房间的人是谁，向其他用户发送消息（ajax向）
    public function checkQuitUserForRole(Request $request){
        $userid = strip_tags($request->input('userid',''));
        $ret = ['status'=>-200,'msg'=>'用户不存在'];
        if (!$userid) return response()->json($ret);
        $role = $this->redis->get('TCL_WEBRTCROOM_'.$request->roomid.'_'.$userid);
        if (isset($role) && !empty($role)){
            $roleAllow = ['anchor'=>'坐席','supporter'=>'支援工程师','company'=>'客户'];
            $ret['status'] = 200;
            $ret['msg'] = $roleAllow[$role].'已离开房间';
            $ret['data'] = ['role'=>$role];
            return response()->json($ret);
        }else{
            return response()->json($ret);
        }
    }

    //查找在线角色
    public function onlineWhoRoleIs(Request $request){
        $userid = strip_tags($request->input('userid',''));
        $ret = ['status'=>-200,'msg'=>'用户不存在'];
        $returnRole = '';
        if (!$userid) return response()->json($ret);
        $rolerData = $this->redis->hgetall('TCL_WEBRTCROOM_'.$request->roomid);
        if (!is_array($rolerData) || empty($rolerData) || count($rolerData) < 1) return response()->json($ret);

        foreach ($rolerData as $key=>$val){
            switch ($key){
                case 'supporter':
                    $supporters = explode(',',$val);
                    if (in_array($userid,$supporters)){
                        $returnRole = $key;
                    }
                break;
                default:
                    if ($val == $userid){
                        $returnRole = $key;
                    }
            }
        }
        if ($returnRole){
            $ret['status'] = 200;
            $ret['msg'] = 'Is found';
            $ret['data'] = [
                'role'=>$returnRole
            ];
        }
        return response()->json($ret);
    }

    public function notifyQMT(Request $request){
        $status = intval($request->input('status',0));
        $third_id = $request->third_id;
        $roomid = $request->roomid;
        if (is_numeric($status) && $status > 0){
            $client = new Client();
            $sendData = ['form_params' => ['userId'=>$request->third_id,'chatId'=>$request->roomid,'status'=>$status]];
            if (env('APP_DEBUG')){
                $returnQMT = $client->request('POST','http://10.4.62.41:8080/weChatAdapter/videochat/keepStatus',$sendData);
            }else{
                $returnQMT = $client->request('POST','http://10.4.28.68:8081/weChatAdapter/videochat/keepStatus',$sendData);
            }
            $body = json_decode($returnQMT->getBody(),true);
            Log::info($body);
            if ($returnQMT->getStatusCode() === 200 && $body['success'] === true){
                return response()->json(['status'=>200,'msg'=>'心跳检测正常']);
            }else{
                return response()->json(['status'=>-200,'msg'=>'QMT请求异常']);
            }
        }else{
            return response()->json(['status'=>-200,'msg'=>'参数异常']);
        }
    }

    public function anchorIsOnline(Request $request){
        $anchorIsOnline = false;
        if ($request->role == 'company'){
            $anchorIsOnline = $this->redis->hexists('TCL_WEBRTCROOM_'.$request->roomid,'anchor');
            //if (!$anchorIsOnline) return response()->json(['status'=>-250,'msg'=>'亲，坐席还未进入房间哦，请稍等片刻']);
        }else{
            $anchorIsOnline = 'notAllow';
        }
        $ret = [
            'status'=>200,
            'msg'=>'Success',
            'data'=>[
                'anchorIsOnline'=>$anchorIsOnline,
            ]
        ];
        return response()->json($ret);
    }

    //专供给客户小程序进入房间前判断合法
    public function confForCompany(Request $request){
        if ($request->role == 'company') {
            //配置项
            $this->tx_config = ['userid' => md5($request->session()->getId() . env('VIDEOCHAT_USER_SALT'))];

            $anchorIsOnline = false;
            $anchorIsOnline = $this->redis->hexists('TCL_WEBRTCROOM_' . $request->roomid, 'anchor');
            if (!$anchorIsOnline) return response()->json(['status' => -250, 'msg' => 'outOfTime']);

            //redis判断角色权限，并限制入场人数
            $rolerData = $this->redis->hget('TCL_WEBRTCROOM_' . $request->roomid, $request->role);
            if ($rolerData && $rolerData != $this->tx_config['userid']) return response()->json(['status' => -200, 'msg' => 'outOfTime']);
        }
        $ret = ['status'=>200, 'msg'=>'Success'];
        return response()->json($ret);
    }

    //判断header头
    private function __isWeixin($userAgent){
        if ( strpos($userAgent, 'MicroMessenger') !== false ) {
            return true;
        }
        return false;
    }

}
