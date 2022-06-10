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

namespace app\api\model;
use think\Db;
use think\Model;
use think\facade\Request;

class Api extends Model{
    public function category($cid,$type,$filter){
        $category=$this->get_nav($cid,$type,$filter,false,Request::param('cid'),'id,title,pid,icon,type');
        foreach ($category as $key => $value) {
            $class[$key]=$value;
            if($value['branch']==1){
                $class[$key]['sub_cate']=$this->category($value['id'],$type,$filter);
            }
        }
        return $class;
    }


    public function get_nav($category,$type,$limit,$cid,$field='id,title,pid,icon,type'){
        $map = ['status' => 1,'pid' => $category];
        if($type!==false){
            $map['type']=$type;
        }
        $data=Db::name('category')->field('id,title,pid,icon,type')->where($map)->limit($limit)->order('sort')->select();
        if($data){
            foreach ($data as $k=>$v){
                $meun_list[$k]=$v;
                if($v["type"]==3){
                    $meun_list[$k]["branch"]=0;
                }else{
                    $meun_list[$k]["branch"]=$this->get_branch($v["id"]);
                }
            }
            return $meun_list;
        }
    }

    public function get_branch($category){
        $map = ['status' => 1,'pid'=>$category];
        $Count=Db::name("category")->where($map)->find();
        if($Count>0){
            return 1;
        }else{
            return 0;
        }
    }
}