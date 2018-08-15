<?php

namespace App\Http\Middleware;

use Closure;

class CrossHttp
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
        if ($request->routeIs('qiniuUpload') || $request->routeIs('qiniuFile')){
            $timestamp = $request->input('timestamp','');
            $token = $request->input('token','');
            if (md5(sha1('TCSM_UPLOADFILE_TO_QINIU').sha1($timestamp)) !== $token) return response()->json(['status'=>-199,'msg'=>'token校验失败']);
        }
        $response = $next($request);
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Cookie, X-CSRF-TOKEN, Accept, Authorization, X-XSRF-TOKEN');
        $response->header('Access-Control-Expose-Headers', 'Authorization, authenticated');
        $response->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, OPTIONS');
        $response->header('Access-Control-Allow-Credentials', 'true');
        return $response;
    }
}
