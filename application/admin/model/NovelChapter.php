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
use think\facade\Config;
use think\facade\Env;
use think\facade\Cache;
use org\File;
use app\admin\validate\NovelChapter as NovelChapterValidate;

class NovelChapter extends Model{

    protected $insert = ['status'=>1];

    protected function set_chapter($data){
        $chapter=NovelChapter::where(['id'=>$data['id']])->value('chapter');
        if(Config::get('web.data_save_compress')){
            $chapter=@gzuncompress(base64_decode($chapter));
        }
        $chapter=json_decode($chapter,true);
        $word=mb_strlen($data['content']);
        $chapter_data=[
            'title'=>$data['title'],
            'intro'=>$data['intro'],
            'update_time'=>time(),
            'issued'=>$data['issued'],
            'word'=>$word,
            'reurl'=>'',
            'auto'=>0
        ];
        if(empty($data['key'])){
            $key=uniqidReal();
            $novel_word=$word;
            $chapter[$key]=$chapter_data;
        }else{
            $key=$data['key'];
            $novel_word=$word-$chapter[$key]['word'];
        }
        $chapter_data['path']=$data['novel_id'].DIRECTORY_SEPARATOR.$key.'.txt';
        $chapter[$key]=$chapter_data;
        $novel_data=['update_time'=>time(),'word'=>Db::raw('word+'.$novel_word)];
        Db::name('novel')->where(['id'=>$data['novel_id']])->update($novel_data);
        model('common/api')->set_chapter_content($chapter_data['path'],$data['content']);
        $chapter=json_encode($chapter);
        if(Config::get('web.data_save_compress')){
            $chapter=base64_encode(gzcompress($chapter,Config::get('web.data_save_compress_level')));
        }
        return ['chapter'=>$chapter,'key'=>$key];
    }

	public function info($id,$key){
    	$info=NovelChapter::where(['id'=>$id])->field('id,chapter,novel_id')->find()->toArray();
        if(Config::get('web.data_save_compress')){
            $info['chapter']=@gzuncompress(base64_decode($info['chapter']));
        }
        $info['chapter']=json_decode($info['chapter'],true);
        if($info['chapter'][$key]['auto']==0){
            $info['chapter'][$key]['content']=model('common/api')->get_chapter_content($info['chapter'][$key]['path']);
        }
        $info['chapter'][$key]['id']=$id;
        $info['chapter'][$key]['key']=$key;
        $info['chapter'][$key]['novel_id']=$info['novel_id'];
		return $info['chapter'][$key];
	}

    public function lists($id){
        $list=NovelChapter::where(['novel_id'=>$id])->field('id,chapter')->find();
        if(empty($list)){
            $list=['chapter'=>[],'id'=>''];
        }else{
            if(Config::get('web.data_save_compress')){
                $list['chapter']=@gzuncompress(base64_decode($list['chapter']));
            }
            $list['chapter']=json_decode($list['chapter'],true);
            $list['chapter']=$list['chapter']?array_reverse($list['chapter'],true):[];
        }
        return $list;
    }

	public function edit($data){
        $validate = new NovelChapterValidate;
        if (!$validate->check($data)) {
            $this->error=$validate->getError();
            return false;
        }
        $NovelChapter = new NovelChapter();
        $chapter=$this->set_chapter($data);
        $data['chapter']=$chapter['chapter'];
        if(empty($data['id'])){
            $result = $NovelChapter->allowField(true)->save($data);
        }else{
            $result = $NovelChapter->allowField(true)->isUpdate(true)->save($data);
        }
        if(false === $result){
            $this->error=$NovelChapter->getError();
            return false;
        }
        if(empty($data['id'])){
            return ['id'=>$NovelChapter->id,'key'=>$chapter['key']];
        }else{
            if(empty($data['issued'])){
                return $chapter['key'];
            }else{
                return $result;    
            }
        }
    }

    public function del($id,$key){
        $word=0;
        $map = ['id' => $id];
        $data=NovelChapter::where($map)->field('id,novel_id,chapter')->find()->toArray();
        if(Config::get('web.data_save_compress')){
            $data['chapter']=@gzuncompress(base64_decode($data['chapter']));
        }
        $chapter=json_decode($data['chapter'],true);
        $addons_name = Cache::remember('addons_storage',function(){
            $map = ['status'=>1,'group'=>'storage'];
            return Db::name('Addons')->where($map)->value('name');
        });
        if($addons_name){
            $addons_class = get_addon_class($addons_name);
            if(class_exists($addons_class)){
                $addon = new $addons_class();
            }
        }
        foreach ($key as $v) {
            if($chapter[$v]['auto']==0){
                if($addons_name){
                    $addon->unlink($path);
                }else{
                    File::unlink(Env::get('runtime_path').'txt'.DIRECTORY_SEPARATOR.$chapter[$v]['path']);
                }
            }
            $word+=$chapter[$v]['word'];
            unset($chapter[$v]);
        }
        $data['chapter']=json_encode($chapter);
        if(Config::get('web.data_save_compress')){
            $data['chapter']=base64_encode(gzcompress($data['chapter'],Config::get('web.data_save_compress_level')));
        }
        $result=NovelChapter::isUpdate(true)->save($data);
        $chapter_data=['word'=>Db::raw('word-'.$word)];
        Db::name('novel')->where('id',$data['novel_id'])->update($chapter_data);
        if(false === $result){
            $this->error=NovelChapter::getError();
            return false;
        } else {
            return true;
        }
    }
}