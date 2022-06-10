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
use think\Db;
use think\Model;
use think\facade\Request;
use org\File;
use app\admin\validate\News as NewsValidate;

class News extends Model{

    protected $autoWriteTimestamp = true;
    protected $auto = ['position'];
    protected $insert = ['status'=>1];

    public function getCategoryTextAttr($value,$data){
        return model('common/api')->get_category($data['category'],'title');
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
    	$info=News::where($map)->find();
		return $info;
	}

    public function lists(){
         $map = [];
        if(Request::param('category')){
            $map[]  = ['category','=',Request::param('category')];
        }
        if(Request::param('keywords')){
            $map[]  = ['title','like','%'.Request::param('keywords').'%'];
        }
        if(Request::param('position')){
            $map[] = ['position','exp',Db::raw('& '.Request::param('position').' = '.Request::param('position'))];
        }
        $list=News::where($map)->order('update_time desc')->paginate(config('web.list_rows'))->each(function($item, $key){
            $item->comment_count = Db::name('comment')->where(['type'=>'news','mid'=>$item->id,'pid'=>0])->count('id');
        });
        return $list;
    }

	public function edit($data,$type){
        $validate = new NewsValidate;
        if (!$validate->scene($type)->check($data)) {
            $this->error=$validate->getError();
            return false;
        }
        if(empty($data['id'])){
            $result = News::allowField(true)->save($data);
        }else{
            $result = News::allowField(true)->isUpdate(true)->save($data);
        }
        if(false === $result){
            $this->error=News::getError();
            return false;
        }
        return $result;
    }

    public function del($id){
        $map = ['id' => $id];
        $pic = News::where($map)->column('pic');
        foreach ($pic as $value) {
            File::unlink(".".$value);
        }
        $result = News::where($map)->delete();
        DB::name('comment')->where(['mid'=>$id,'type'=>'news'])->delete();
        if(false === $result){
            $this->error=News::getError();
            return false;
        }else{
            return $result;
        }
    }
}