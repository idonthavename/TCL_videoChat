<?php

namespace App\Http\Controllers\Chatweb;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\WebRTCSigApi;

class IndexController extends Controller
{
    private $tx_config;

    public function play(){
        return view('chatweb.index');
    }

    public function conf(Request $request){
        $this->tx_config = [
            'sdkappid'=>env('TX_SDKAPPID'),
            'accountType'=>env('TX_ACCOUNTTYPE'),
            'roomid'=>$request->roomid,
            'userid'=>$request->session()->getId()
        ];
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

        return response()->json([
           'status'=>200,
            'msg'=>'Success',
            'data'=>[
                'userId'=>$this->tx_config['userid'],
                'sdkappid'=>$this->tx_config['sdkappid'],
                'accountType'=>$this->tx_config['accountType'],
                'roomid'=>$this->tx_config['roomid'],
                'userSig'=>$userSig,
                'privMapEncrypt'=>$privMapEncrypt,
            ]
        ]);
    }
}
