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
        if ($token['roomid'] <= 0 || !$token['sign']) abort(404,'链接传递参数出错，请联系老哥');
        if ($token['sign'] !== md5(sha1('TCL_VIDEOCHATONTHEAIR').sha1($timestamp))) abort(404,'链接传递参数出错，请联系老哥');
        if ($request->routeIs('getConfig') || $request->routeIs('getHeader')){
            //if (Carbon::now()->timestamp < $timestamp || Carbon::now()->timestamp - $timestamp >= 300) abort(404,'已经过有效时间咯，让坐席再给你发一次吧');
        }else{
            //if (Carbon::now()->timestamp < $timestamp || Carbon::now()->timestamp - $timestamp >= 7200) abort(404,'已经过有效时间咯，让坐席再给你发一次吧');
        }
        $request->role = $token['role'];
        $request->roomid = $token['roomid'];
        $request->third_id = $token['third_id'];
        return $next($request);
    }
}
