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

class Feedback extends Model{

    protected $autoWriteTimestamp = true;

	public function info($id){
		$map['id'] = $id;
    	$info=Feedback::where($map)->find();
		return $info;
	}

    public function lists($type='novel',$id=null,$limit=0){
        $map=[];
        $map=['status'=>1];
        if($id){
            $map=['mid'=>$id];
        }
        $limit=$limit?$limit:Config::get('web.list_rows');
        $list=Feedback::where($map)->order('id desc')->paginate($limit);
        return $list;
    }

	public function edit($data){
        if(empty($data['id'])){
            $result = Feedback::allowField(true)->save($data);
        }else{
            $result = Feedback::allowField(true)->isUpdate(true)->save($data);
        }
        if(false === $result){
            $this->error=Feedback::getError();
            return false;
        }
        return $result;
    }


    public function del($id){
        $map = ['id' => $id];
        $result = Feedback::where($map)->delete();
        if(false === $result){
            $this->error=Feedback::getError();
            return false;
        }else{
            $sub_id=Feedback::where(['pid'=>$id])->column('id');
            if($sub_id){
                $this->del($sub_id);
            }
            return $result;
        }
    }

}