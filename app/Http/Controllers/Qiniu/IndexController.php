<?php
namespace App\Http\Controllers\Qiniu;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

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
                if ($disk->put($qiniuFile['path'].$qiniuFile['filename'].$existion,file_get_contents($media))){
                    //$disk->persistentFop($qiniuFile['path'].$qiniuFile['filename'].$existion,'vframe/jpg/offset/5/w/480/h/360');      //持久化数据处理，如压缩图片转换图片等
                    $this->returnJson['status'] = 200;
                    $this->returnJson['msg'] = '上传成功';
                    $this->returnJson['path'] = $qiniuFile['path'].$qiniuFile['filename'].$existion;
                }else{
                    $this->returnJson['status'] = -102;
                    $this->returnJson['msg'] = '上传失败';
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
