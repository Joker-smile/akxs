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
use think\facade\Config;
use app\admin\validate\User as UserValidate;

class User extends Model{

    protected $autoWriteTimestamp = true;

	public function info($id){
		$map['id'] = $id;
    	$info=User::where($map)->find();
		return $info;
	}

    public function lists(){
        $map=[];
        $map=['status'=>1];
        $list=User::where($map)->order('id desc')->paginate(Config::get('web.list_rows'));
        return $list;
    }

	public function edit($data){
        $validate = new UserValidate;
        if(empty($data['id'])){
            if (!$validate->scene('reg')->check($data)) {
                $this->error=$validate->getError();
                return false;
            }
            $data['password']=think_ucenter_md5($data['password']);
            $result = User::allowField(true)->save($data);
        }else{
            if(!empty($data['password'])){
                if (!$validate->scene('password')->check($data)) {
                    $this->error=$validate->getError();
                    return false;
                }
                $data['password']=think_ucenter_md5($data['password']);
            }else{
                if (!$validate->scene('edit')->check($data)) {
                    $this->error=$validate->getError();
                    return false;
                }
            }
            $result = User::allowField(true)->isUpdate(true)->save($data);
        }
        if(false === $result){
            $this->error=User::getError();
            return false;
        }
        return $result;
    }

    public function del($id){
        $map = ['id' => $id];
        $pic = User::where($map)->column('headimgurl');
        foreach ($pic as $value) {
            if($value!='/uploads//user/head/user-icon.png'){
                File::unlink(".".$value);
            }
        }
        $result = User::where($map)->delete();
        if(false === $result){
            $this->error=User::getError();
            return false;
        }else{
            return $result;
        }
    }
}