<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class CheckVideoChatToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = urldecode($request->input('token',null));
        $timestamp = $request->input('timestamp',0);
        if (!$token || !$timestamp) abort(404,'抱歉你的页面去了外太空');
        $token = Crypt::decrypt($token);
        $token['roomid'] = intval($token['roomid']);
        $token['third_id'] = intval($token['third_id']);
        $roleAllow = ['anchor','supporter','company'];
        if (!in_array($token['role'],$roleAllow)) abort(404,'抱歉，您没有权限进去该房间');
        if ($token['roomid'] <= 0 || !$token['sign']) abort(404,'链接传递参数出错，请联系客服');
        if ($token['sign'] !== md5(sha1('TCL_VIDEOCHATONTHEAIR').sha1($timestamp))) abort(404,'链接传递参数出错，请联系客服');
        $error404 = 'outOfTime';
        $error404_pc = '视频房间已失效或过期，请联系客服人员重新发起视频邀请，谢谢！';
        if ($request->routeIs('getConfig') || $request->routeIs('getConfigCompany')){
            if (Carbon::now()->timestamp < $timestamp || Carbon::now()->timestamp - $timestamp >= 300) return response()->json(['status'=>-100,'msg'=>$error404]);
        }elseif ($request->routeIs('videoChatPlaying')){
            if (Carbon::now()->timestamp < $timestamp || Carbon::now()->timestamp - $timestamp >= 300) abort(404,$error404_pc);
        }else{
            if (Carbon::now()->timestamp < $timestamp || Carbon::now()->timestamp - $timestamp >= 7200) return response()->json(['status'=>-100,'msg'=>$error404]);
        }
        $request->role = $token['role'];
        $request->roomid = $token['roomid'];
        $request->third_id = $token['third_id'];
        return $next($request);
    }
}
