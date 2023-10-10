<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 2018-07-30
 * Time: 11:17
 */

namespace App\Common;

use App\Http\Traits\FormatTrait;
use App\Model\Admin\ImagesManage;
use App\Services\OSS;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Model\Admin\Image;

class UploadAdmin
{
    use FormatTrait;
    public function handle(Request $request)
    {
        $file = $request->file('upload_file');
        //要保存的文件名 时间+扩展名
        $saveDir = sprintf("tmp/%s", date("Ymd"));

        if ( !empty($file) ) {
            if ( !$file->isValid() ) {
                return false;
            }else{
                //获取文件的扩展名
                $ext = $file->getClientOriginalExtension();
                $filename = sprintf('%s/%s.%s', $saveDir, uniqid(), $ext);
                //获取文件的绝对路径，但是获取到的在本地不能打开
                $path = $file->getRealPath();
                //保存文件          配置文件存放文件的名字  ，文件名，路径
                $bool= Storage::disk('upload')->put($filename,file_get_contents($path));
                if($bool){
                    return config('filesystems.disks.upload.url').$filename;
                }
            }
        }

        //base64 图片
        $base64String = $request->get('upload_file'); //截取data:image/png;base64, 这个逗号后的字符
        if(preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64String, $result)){
            $ext = $result[2];
            if( in_array($ext,array('pjpeg','jpeg','jpg','gif','bmp','png')) ){
                //获取文件的扩展名
                $filename = sprintf('%s/%s.%s', $saveDir, uniqid(), $ext);
                //保存文件
                $bool = Storage::disk('upload')->put($filename, base64_decode(str_replace($result[1], '', $base64String)) );
                if($bool) {
                    return config('filesystems.disks.upload.url').$filename;
                }else{
                    return false;
                }
            }else{
                //文件类型错误
                return false;
            }
        }

        return false;
    }

    /**
     * 1. 上传到临时目录
     * @param $file 上传的文件对象，$_FILES/$request->file('file');
     * @param $path 待上传的相对路径
     * @return bool|string
     */
    public function uploadToTmp($file, $path)
    {
        set_time_limit(0);
        @ini_set('memory_limit', '4096M');
        if (!empty($file)) {
            if (!$file->isValid()) {
                return false;
            } else {
                //获取文件的扩展名
                $kuoname = $file->getClientOriginalExtension();
                //获取文件的绝对路径，但是获取到的在本地不能打开
                $tmpFile = $file->getRealPath();
                //获取文件内容
                $content = file_get_contents($tmpFile);
                $fileName = date('YmdHis') . rand(10000, 99999) . "." . $kuoname;

                $res = Storage::disk('tmp')->put(sprintf("/%s%s", $path, $fileName), $content);
                if ($res) {
                    return sprintf("%s%s", $path, $fileName);
                }
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 1. 上传到public目录
     * @param $file 上传的文件对象，$_FILES/$request->file('file');
     * @param $path 待上传的相对路径
     * @return bool|string
     */
    public function uploadToPlublic($file, $path, $fileName = '')
    {
        set_time_limit(0);
        @ini_set('memory_limit', '4096M');
        if (!empty($file)) {
            if (!$file->isValid()) {
                return false;
            } else {
                //获取文件的扩展名
                $kuoname = $file->getClientOriginalExtension();
                //获取文件的绝对路径，但是获取到的在本地不能打开
                $tmpFile = $file->getRealPath();
                //获取文件内容
                $content = file_get_contents($tmpFile);
                if (empty($fileName)) {
                    $fileName = date('YmdHis') . rand(10000, 99999) . "." . $kuoname;
                } else {
                    $fileName = $fileName . "." . $kuoname;
                }

                $res = Storage::disk('public')->put(sprintf("/%s%s", $path, $fileName), $content);
                if ($res) {
                    return sprintf("%s%s", $path, $fileName);
                }
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 2. 移动零时文件到正式目录下
     *
     * @param $tmpFile
     * @param $type oss为阿里云上传
     * @param $subPath
     * @param $checkExist
     * @param $fileName
     * @return string
     */
    public function uploadFinish($tmpFile, $type, $subPath, $checkExist = true, $fileName = '',$backData = false)
    {
        if (empty($fileName)){
            $fileName = substr($tmpFile, strrpos($tmpFile, "/")+1);
        }
        $fileNameArr = explode('.', $fileName);
        $fileName = preg_replace('/\W/i', '', $fileNameArr[0]);
        $fileName = $fileName.".".end($fileNameArr);
        $file = sprintf("%s%s", $subPath, $fileName);
        $tmpFile = str_replace(config('filesystems.disks.tmp.root'), '', $tmpFile);//兼容带路经的图片
        $tmpFile = sprintf("%s%s", config('filesystems.disks.tmp.root'), $tmpFile);
        $mImage = new ImagesManage();
        //之前传过
        $md5File = md5_file($tmpFile);
        if ($checkExist){
            $isExist = $mImage->where('org_file_md5', $md5File)->first();
            if (! empty($isExist)) {
                $mImage->where('org_file_md5', $md5File)->update(['updated_at' => date('Y-m-d H:i:s')]);

                $isExist = json_decode(json_encode($isExist), true);
                return $isExist['file'];
            }
        }

        if ($type == 'oss') {
            $ossRoot = config('admin.aliyun_oss.OSS_ROOT');
            $bucket = config('admin.aliyun_oss.bucket');
            if (! empty($ossRoot)) {
                $file = $ossRoot.$file;
                OSS::publicUpload($bucket, $file, $tmpFile);
            } else {
                OSS::privateUpload($bucket, $file, $tmpFile);
            }

            $fileInfo = @getimagesize($tmpFile);
            $mImage->insert([
                'file' => $file,
                'size' => @$this->fileSize($tmpFile),
                'org_file_md5' => $md5File,
                'w' => empty($fileInfo[0])? 'unknow': $fileInfo[0],
                'y' => empty($fileInfo[1])? 'unknow': $fileInfo[1],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            if ($backData){
                $data['file']=$file;
                $data['size']=@$this->fileSize($tmpFile);
                $data['w']=empty($fileInfo[0])? 'unknow': $fileInfo[0];
                $data['y']=empty($fileInfo[1])? 'unknow': $fileInfo[1];
            }
            unlink($tmpFile);
        } else {
            $savePath = config("filesystems.disks.".$type.".root").$subPath;
            if (!is_dir($savePath)) {
                mkdir($savePath, 0777, true);
            }

            rename($tmpFile, $savePath.$fileName);
        }
        if($type == 'oss' && $backData){
            return $data;
        }

        ///
        return $file;
    }

    /**
     * 直接上传内容
     * @param $content
     * @param $type
     * @param $subPath
     * @param $fileName
     * @return bool|string
     */
    public function uploadByContent($content, $type='oss', $subPath, $fileName, $options=[],$size = 0)
    {
        $mImage = new ImagesManage();
        $file = sprintf("%s%s", $subPath, $fileName);
        $ossRoot = config('admin.aliyun_oss.OSS_ROOT');
        $file = $ossRoot.$file;

        //之前传过
        $md5File = md5($content);
        $isExist = $mImage->where('org_file_md5', $md5File)->first();
        if (! empty($isExist)) {
            $isExist = json_decode(json_encode($isExist), true);
            return $isExist['file'];
        }

        $bucket = config('admin.aliyun_oss.bucket');
        if (! empty($ossRoot)) {
            $result = OSS::publicUploadContent($bucket, $file, $content, $options);
        } else {
            $result = OSS::privateUploadContent($bucket, $file, $content, $options);
        }

        $urlPre = config('admin.aliyun_oss.url_pre');
        $fileInfo = @getimagesize($urlPre.$file);
        if ($result) {
            $mImage->insert([
                'file' => $file,
                'size' => $size,
                'org_file_md5' => $md5File,
                'w' => empty($fileInfo[0])? 'unknow': $fileInfo[0],
                'y' => empty($fileInfo[1])? 'unknow': $fileInfo[1],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            return $file;
        } else {
            return false;
        }
    }
}
