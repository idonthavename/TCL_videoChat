<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::group(['prefix'=>'qiniu'],function (){
    Route::get('/','Qiniu\IndexController@index');
    Route::post('/upload','Qiniu\IndexController@upload');
    Route::get('/play/{mediaName}','Qiniu\IndexController@play');
});

Route::group(['prefix'=>'chatweb','namespace'=>'Chatweb','middleware'=>'checkVideoChatToken'],function (){
    Route::get('/','IndexController@play')->name('videoChatPlaying');
    Route::post('/conf','IndexController@conf')->name('getConfig');
    Route::post('/quitRoom','IndexController@quitRoom');
    Route::post('/checkQuitUserForRole','IndexController@checkQuitUserForRole');
    Route::post('/onlineWhoRoleIs','IndexController@onlineWhoRoleIs');
});

Route::get('/test', function (){
    $guzzle = new GuzzleHttp\Client;

    $response = $guzzle->post('http://videochat.com/oauth/token', [
        'form_params' => [
            'grant_type' => 'client_credentials',
            'client_id' => '3',
            'client_secret' => 'OBb4WxJwOYpsZ7TOIKwnWf74kAKv5imk9wydvHme',
            'scope' => '',
        ],
    ]);

    return json_decode((string) $response->getBody(), true);
});

//注册登录
//Auth::routes();
//
//Route::get('/home', 'HomeController@index')->name('home');
