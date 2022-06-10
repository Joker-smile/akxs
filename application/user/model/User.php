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

namespace app\user\model;

use think\Model;
use think\facade\Request;
use think\facade\Config;
use think\facade\Session;
use app\user\validate\User as UserValidate;

class User extends Model
{

	protected $insert = ['password','status'=>1];

    protected $autoWriteTimestamp = true;

    protected function setPasswordAttr($value){
        return think_ucenter_md5($value);
    }

	public function get_info($id=UID,$field = true){
		$map=['status'=>1,'id'=>$id];
		$data=User::where($map)->field($field)->find();
		return $data;
	}

	public function check_require($field,$value){
		if(User::where($field,$value)->value('id')){
			return true;
		}else{
			return false;
		}
	}

	public function login($username, $password){
    	$data=Request::post();
        $validate = new UserValidate;
        if (!$validate->scene('login')->check($data)) {
            $this->error=$validate->getError();
            return false;
        }
        $map=['username'=>$username,'status'=>1];
		$user = User::where($map)->find();
		if($user){
			if(think_ucenter_md5($password) === $user['password']){
				$this->auto_login($user);
				return true;
			} else {
				$this->error = '密码错误！';
				return false;
			}
		} else {
			$this->error = '用户不存在或被禁用！';
			return false;
		}
	}

	private function auto_login($user){
        $data = [
            'login'           => ['inc', 1],
            'login_time' => Request::time(),
            'login_ip'   => Request::ip(1)
        ];
        User::where('id', $user['id'])->update($data);
        $auth = [
            'uid'             => $user['id'],
            'username'        => $user['username']
        ];
        session('user_auth', $auth);
        session('user_auth_sign', data_auth_sign($auth));
    }

    public function logout(){
        session('user_auth', null);
        session('user_auth_sign', null);
    }

    public function reg($data){
        $validate = new UserValidate;
        if (!$validate->scene('reg')->check($data)) {
            $this->error=$validate->getError();
            return false;
        }
        $data['headimgurl']=substr(config('web.upload_path'),1).'/user/head/user-icon.png';
        $data['integral']=Config::get('web.user_reg_integral');
        $result = User::allowField(true)->create($data);
        if(false === $result){
            $this->error=User::getError();
            return false;
        }else{
            return $result;
        }
    }

    public function edit(){
        $data=Request::post();
        $validate = new UserValidate;
        if (!$validate->scene('edit')->check($data)) {
            $this->error=$validate->getError();
            return false;
        }
        $result = User::allowField(true)->save($data,['id'=>UID]);
        if(false === $result){
            $this->error=User::getError();
            return false;
        }else{
            return $result;
        }
    }

    public function password(){
        $data=Request::post();
        $validate = new UserValidate;
        if (!$validate->scene('password')->check($data)) {
            $this->error=$validate->getError();
            return false;
        }
        $data['password']=think_ucenter_md5($data['newpassword']);
        $result = User::allowField(true)->save($data,['id'=>UID]);
        if(false === $result){
            $this->error=User::getError();
            return false;
        }
        return $result;
    }

    public function forgetpwd(){
        $password=Request::post('newpassword');
        $forget_pwd=Session::get('forget_pwd');
        $map['id']=$forget_pwd['uid'];
        $map['status']=1;
        User::where($map)->update(['password'=>think_ucenter_md5($password)]);
        Session::pull('forget_pwd');
    }

    public function crop_img($params){
        $params = explode(',', $params);
        $Image=\image\Image::open(config('web.upload_path').'user/head/'."user_haed_".UID.".png");
        $headimg=config('web.upload_path').'user/head/'.uniqid().'.png';
        $Image->crop($params[2],$params[3],$params[0],$params[1])->save($headimg);
        $headimg=substr($headimg, 1);
        $headimgurl=User::where('id',UID)->value('headimgurl');
        if($headimgurl!= substr(config('USER_HEAD_UPLOAD_PATH'), 1).'user-icon.png'){
            @unlink(".".$headimgurl);
        }
        @unlink(config('web.upload_path').'user/head/'."user_haed_".UID.".png");
        User::where('id',UID)->setField('headimgurl',$headimg);
        return $headimg;
    }
}