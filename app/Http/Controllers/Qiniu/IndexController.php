<?php
namespace App\Http\Controllers\Qiniu;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class IndexController extends Controller
{
    private $returnJson;
    public function __construct(){
        $this->returnJson = [];
    }

    public function index(){
        $output['timestamp'] = time();
        $output['token'] = md5(sha1('TCSM_UPLOADFILE_TO_QINIU').sha1($output['timestamp']));
        return view('qiniu.index',$output);
    }

    public function play($mediaName){
        $disk = \Storage::disk('qiniu');
        $media = $disk->privateDownloadUrl($mediaName);
        $this->returnJson['status'] = 200;
        $this->returnJson['msg'] = 'Success';
        $this->returnJson['url'] = $media;
        //$media = $disk->privateDownloadUrl('2018/06/06/20180606120542516090.mp4');
        //return view('qiniu.play',['media'=>$media]);
        return response()->json($this->returnJson);
    }

    public function upload(Request $request){
        $request->session()->getId();
        if ($request->hasFile('media') && $request->file('media')->isValid()){
            $media = $request->file('media');
            $allowExtension = ['jpg','jpeg','png','gif','mp4','mov'];
            if($media->getSize() > 200*1024*1024 || !in_array($media->extension(),$allowExtension)){
                $this->returnJson['status'] = -101;
                $this->returnJson['msg'] = '文件不能超过200M或文件类型错误';
            }else{
                $disk = \Storage::disk('qiniu');
                $existion = '.'.$media->extension();
                $qiniuFile = $this->_getFilePathAndName($disk,$existion);

                if (env('APP_DEBUG')){
                    $ak = "Y1Hbn5lZtuZgdtcgiaIPouJf5uia42vXnfAUetlc";
                    $sk = "l3NZSoRpT30FBK3Uhtqk-p9iy3e70h4Ulvpi9c18";
                    $bucket = "canyoutellme";
                }else{
                    $ak = "c-GaNPLvrcWyAP9zZysn0wFL-7aOhzxgYLQW8VgV";
                    $sk = "f6Fb9Ny4riOmO6IsLjUqiS-Nj5BgSmVE0KGvYOzE";
                    $bucket = "koyoo-video-chat";
                }

                $auth = new Auth($ak, $sk);
                $token = $auth->uploadToken($bucket);
                $uploadMgr = new UploadManager();
                // 调用 UploadManager 的 putFile 方法进行文件的上传
                list($ret, $err) = $uploadMgr->putFile($token, $qiniuFile['path'].$qiniuFile['filename'].$existion, $media->getRealPath());
                if ($ret['key']){
                    /*if (in_array($media->extension(),['mp4','mov'])){
                        $fop = 'avthumb/mp4/ab/160k/ar/44100/acodec/libfaac/r/30/vb/2200k/vcodec/libx264/s/1280x720/autoscale/1/stripmeta/0';
                        $queueName = 'transMp4';
                        $a = $disk->persistentFop($qiniuFile['path'].$qiniuFile['filename'].$existion,$fop,$queueName);
                        dd($a);
                    }*/
                    //$disk->persistentFop($qiniuFile['path'].$qiniuFile['filename'].$existion,'vframe/jpg/offset/5/w/480/h/360');      //持久化数据处理，如压缩图片转换图片等
                    $this->returnJson['status'] = 200;
                    $this->returnJson['msg'] = '上传成功';
                    $this->returnJson['path'] = $qiniuFile['path'].$qiniuFile['filename'].$existion;
                }else{
                    $this->returnJson['status'] = -102;
                    $this->returnJson['msg'] = '上传失败';
                    $this->returnJson['error'] = $err['error'];
                }
            }
        }else{
            $this->returnJson['status'] = -100;
            $this->returnJson['msg'] = '文件不能为空或文件验证失败';
        }
        return response()->json($this->returnJson);
    }

    private function _getFilePathAndName($qiniu,$existion){
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        $path = $year.'-'.$month.'-'.$day.'-';
        $filename = date('YmdHis').mt_rand(100000,999999);
        if ($qiniu->exists($path.$filename.$existion)){
            $this->_getFilePathAndName($qiniu,$existion);
        }else{
            return ['path'=>$path,'filename'=>$filename];
        }
    }
}
