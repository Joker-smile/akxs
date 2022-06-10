<?php
// +----------------------------------------------------------------------
// | KyxsCMS [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018~2019 http://www.kyxscms.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: kyxscms
// +----------------------------------------------------------------------

namespace app\admin\controller;

class Upload extends Base 
{
    public function pic(){
        $file = $this->request->file('file');
        $info = $file->validate(['ext'=>'jpg,jpeg,png,gif,webp,bmp','type'=>'image/jpeg,image/png,image/gif,image/webp,image/bmp'])->move(config('web.upload_path').$this->request->param('path'));
        if($info){
            $this->success('上传成功！','',['path'=>substr(config('web.upload_path'),1).$this->request->param('path').'/'.str_replace('\\','/',$info->getSaveName())]);
        }else{
            $this->error($file->getError());
        }
    }

    public function file(){
        $file = $this->request->file('file');
        $info = $file->validate(['ext'=>'txt,html,zip,josn','type'=>'text/plain,text/html,application/zip,application/json'])->move(config('web.upload_path').$this->request->param('path'));
        if($info){
            $this->success('上传成功！','',['path'=>substr(config('web.upload_path'),1).$this->request->param('path').'/'.str_replace('\\','/',$info->getSaveName())]);
        }else{
            $this->error($file->getError());
        }
    }

}
