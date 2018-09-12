<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('home');
    }

    public function test(){
        $test = 1;
        if ($test > 0){
            $timestamp = time();
            $url = route('videoChatPlaying');
            $data = [
                'test'=>$test,
                'url'=>$url,
                'token'=>urlencode(Crypt::encrypt([
                    'roomid'=>'123',
                    'role'=>'company',
                    'third_id'=>'',
                    'sign'=>md5(sha1('TCL_VIDEOCHATONTHEAIR').sha1($timestamp))
                ])),
                'timestamp'=>$timestamp,
            ];
        }else{
            $data = ['test'=>$test];
        }
        return response()->json(['status'=>200,'msg'=>'ok','data'=>$data]);
    }
}
