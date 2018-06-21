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
        return view('qiniu.index');
    }

    public function play($mediaName){
        $disk = \Storage::disk('qiniu');
        $media = $disk->privateDownloadUrl('2018/06/06/20180606120542516090.mp4');
        return view('qiniu.play',['media'=>$media]);
    }

    public function upload(Request $request){
        if ($request->hasFile('media') && $request->file('media')->isValid()){
            $media = $request->file('media');
            $disk = \Storage::disk('qiniu');
            $existion = '.'.$media->extension();
            $qiniuFile = $this->_getFilePathAndName($disk,$existion);
            if ($disk->put($qiniuFile['path'].$qiniuFile['filename'].$existion,file_get_contents($media))){
                //$disk->persistentFop($qiniuFile['path'].$qiniuFile['filename'].$existion,'vframe/jpg/offset/5/w/480/h/360');
                $this->returnJson['status'] = 200;
                $this->returnJson['msg'] = '上传成功';
            }else{
                $this->returnJson['status'] = -101;
                $this->returnJson['msg'] = '上传失败';
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
        $path = $year.DIRECTORY_SEPARATOR.$month.DIRECTORY_SEPARATOR.$day.DIRECTORY_SEPARATOR;
        $filename = date('YmdHis').mt_rand(100000,999999);
        if ($qiniu->exists($path.$filename.$existion)){
            $this->_getFilePathAndName($qiniu,$existion);
        }else{
            return ['path'=>$path,'filename'=>$filename];
        }
    }
}
