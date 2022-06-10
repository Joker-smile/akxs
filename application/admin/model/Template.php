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

namespace app\admin\model;
use think\Model;
use think\Db;
use think\facade\Request;
use org\File;
use app\admin\validate\Template as TemplateValidate;

class Template extends Model{

    private $extensions = [
        "docx"  => "doc",
        "xsl"   => "excel",
        "xslx"  => "excel",
        "txt"   => "text", "md"=>"text",
        "html"  => "html", "js"=>"html", "json"=>"html", "css"=>"html", "php"=>"html", "htm"=>"html",
        "mpg"   => "video", "mp4"=>"video","avi"=>"video","mkv"=>"video",
        "png"   => "tupianwenjian", "jpg"=>"tupianwenjian", "gif"=>"tupianwenjian",
        "mp3"   => "audio", "ogg"=>"audio",
        "zip"   => "archive", "rar"=>"archive", "7z"=>"archive", "tar"=>"archive", "gz"=>"archive"
    ];

    private $template_type = [
        "header.html"  => "头文件",
        "footer.html"   => "尾文件",
        "index.html"   => "首页文件", 
        "type.html"=>"栏目文件",
        "lists.html"  => "筛选文件", 
        "news.html"   => "文章内容页",
        "newslists.html"   => "文章列表页", 
        "novel.html"   => "小说内容页",
        "search.html"   => "搜索页"
    ];

    public function getMoldAttr($value){
        return explode(',', $value);
    }

	public function info($id){
		$map['id'] = $id;
    	$info=Template::where($map)->find();
		return $info;
	}

    public function set_default($data){
        $validate = new TemplateValidate;
        if (!$validate->check($data)) {
            $this->error=$validate->getError();
            return false;
        }
        foreach ($data['mold'] as $k => $v) {
            $map=[];
            $map[] = ['','exp',Db::raw('find_in_set("'.$v.'",`mold`)')];
            $mold_data=Template::where($map)->field('id,mold')->select();
            foreach ($mold_data as $key => $value) {
                $mold=array_diff($value['mold'],$data['mold']);
                $mold=implode(",",$mold);
                if($mold){
                    Template::where('id',$value['id'])->setField('mold',$mold);
                }else{
                    Template::where('id',$value['id'])->update(['default'=>0,'mold'=>$mold]);
                }
            }
        }
        $data['mold']=implode(",",$data['mold']);
        $data['default']=1;
        $result = Template::allowField(true)->isUpdate(true)->save($data);
        if(false === $result){
            $this->error=Template::getError();
            return false;
        }
        return $result;
    }

    public function file_list($path,$is_all=FALSE,$exts='*'){
        $file_info=[];
        $list_info=File::list_dir_info($path,$is_all,$exts);
        foreach ($list_info as $key => $value) {
            $file_info[$key]=File::list_info($value);
            if($file_info[$key]['isFile']){
                $file_info[$key]['extensions']=$this->extensions[$file_info[$key]['ext']];
                $file_info[$key]['template_type']=@$this->template_type[$file_info[$key]['filename']];
                if(!$file_info[$key]['template_type']){
                    $file_info[$key]['template_type']='其它模版';
                }
            }else{
                $file_info[$key]['extensions']='folder';
                $file_info[$key]['template_type']='文件夹';
            }
            
        }
        return $file_info;
    }

    public function file_info($path){
        return File::read($path);
    }

    public function edit($data){
        return File::put($data['path'],$data['content']);
    }
}