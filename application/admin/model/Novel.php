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
use think\facade\Config;
use think\facade\Env;
use think\facade\Cache;
use org\File;
use app\admin\validate\Novel as NovelValidate;

class Novel extends Model{

    protected $autoWriteTimestamp = true;
    protected $auto = ['position'];
    protected $insert = ['status'=>1];

    public function getCategoryTextAttr($value,$data){
        return model('common/api')->get_category($data['category'],'title');
    }

    public function getSerializeTextAttr($value,$data){
        $serialize = [0=>'连载',1=>'完结'];
        return $serialize[$data['serialize']];
    }

    public function setPositionAttr($value){
        if(!is_array($value)){
            return 0;
        }else{
            $pos = 0;
            foreach ($value as $key=>$value){
                $pos += $value;
            }
            return $pos;
        }
    }

	public function info($id){
		$map['id'] = $id;
    	$info=Novel::where($map)->find();
		return $info;
	}

    public function lists(){
        $map = [];
        $map[] = ['status','=',1];
        if(Request::param('category')){
            $map[]  = ['category','=',Request::param('category')];
        }
        $serialize=Request::param('serialize');
        if(isset($serialize)){
            $map[]  = ['serialize','=',Request::param('serialize')];
        }
        if(Request::param('keywords')){
            $map[]  = ['title','like','%'.Request::param('keywords').'%'];
        }
        if(Request::param('position')){
            $map[] = ['position','exp',Db::raw('& '.Request::param('position').' = '.Request::param('position'))];
        }
        $list=Novel::where($map)->order('update_time desc')->paginate(config('web.list_rows'))->each(function($item, $key){
            $item->comment_count = Db::name('comment')->where(['type'=>'novel','mid'=>$item->id,'pid'=>0])->count('id');
        });
        return $list;
    }

	public function edit($data,$type){
        $validate = new NovelValidate;
        if (!$validate->scene($type)->check($data)) {
            $this->error=$validate->getError();
            return false;
        }
        if(empty($data['id'])){
            $result = Novel::allowField(true)->save($data);
        }else{
            $result = Novel::allowField(true)->isUpdate(true)->save($data);
        }
        if(false === $result){
            $this->error=Novel::getError();
            return false;
        }
        return $result;
    }

    public function del($id){
        $map = ['id' => $id];
        $data = Novel::field('id,pic')->where($map)->select();
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
        foreach ($data as $value) {
            if(!filter_var($value['pic'],FILTER_VALIDATE_URL)){
                File::unlink(".".$value['pic']);
            }
            if($addons_name){
                $chapter=DB::name('novel_chapter')->where(['novel_id'=>$id])->value('chapter');
                if(Config::get('web.data_save_compress')){
                    $chapter=@gzuncompress(base64_decode($chapter));
                }
                $chapter=json_decode($chapter,true);
                if($chapter){
                    $path=array_column($chapter,'path');
                    $addon->unlink($path);
                }
                
            }else{
                del_dir_file(Env::get('runtime_path').'txt'.DIRECTORY_SEPARATOR.$value['id'].DIRECTORY_SEPARATOR,true);
            }
        }
        $result = Novel::where($map)->delete();
        Db::name('bookshelf')->where(['novel_id'=>$id])->delete();
        DB::name('novel_chapter')->where(['novel_id'=>$id])->delete();
        DB::name('comment')->where(['mid'=>$id,'type'=>'novel'])->delete();
        if(false === $result){
            $this->error=Novel::getError();
            return false;
        }else{
            return $result;
        }
    }
}