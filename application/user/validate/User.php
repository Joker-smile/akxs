<?php
namespace app\user\validate;
use think\Validate;
use think\Db;
use think\facade\Config;
use think\facade\Session;
use captcha\Captcha;

class User extends Validate{
	protected $rule =   [
        'username'  => 'require|unique:user',
        'email' => 'email|unique:user',
        'password' => 'require|length:6,30',
        'repassword' => 'require|confirm:password',
        'code' =>       'require|checkCode:reg',
        'protocol' => 'accepted',
        'curpassword' => 'require|checkUserPass:pass',
        'newpassword' => 'require|length:6,30',
        'confirmpassword' => 'require|confirm:newpassword',
        'pwdcode' => 'require|checkPwdCode:passw'
    ];

    protected $message  =   [
        'username.require' => '请先填写用户名',
        'username.unique' => '该用户名已注册',
        'email.email' => '请填写正确邮箱地址',
        'email.unique' => '该邮箱已注册',
        'password.require' => '用户密码必须填写',
        'password.length'  => '用户密码长度必须在6-30个字符之间！',
        'repassword.require' => '重复密码必须填写',
        'repassword.confirm'  => '用户密码与重复密码不一致！',
        'code.require' => '请必须填写验证码',
        'code.checkCode' => '验证码错误',
        'protocol' => '抱歉不同意服务协议无法注册！',
        'curpassword.require' => '用户当前密码必须填写',
        'curpassword.checkUserPass'  => '用户当前密码不正确！',
        'newpassword.require' => '用户密码必须填写',
        'newpassword.length'  => '用户密码长度必须在6-30个字符之间！',
        'confirmpassword.require' => '确认新密码必须填写',
        'confirmpassword.confirm'  => '用户密码与确认新密码不一致！',
        'pwdcode.require' => '请必须填写验证码',
        'pwdcode.checkPwdCode' => '验证码错误',
    ];

    protected $scene = [
    	'login'  =>  ['username'=>'require','password','code'=>'require|checkCode:login'],
        'reg'  =>  ['username','email','password','repassword','code'=>'require|checkCode:reg','protocol'],
        'password' =>['curpassword','newpassword','confirmpassword'],
        'edit' => ['email'=>'require'],
        'passwcode' =>['pwdcode'=>'require|checkPwdCode:passw'],
        'passw' =>['newpassword','confirmpassword','code'=>'require|checkCode:passw'],
    ];

    protected function checkCode($value,$rule,$data)
    {
        if(Config::get('web.user_reg_verify')!=1 && $rule=='reg'){
            return true;
        }
        if(Config::get('web.user_login_verify')!=1  && $rule=='login'){
            return true;
        }
        $captcha = new Captcha();
        if(!$captcha->check($value)){
            return false;
        }
        return true;
    }

    protected function checkUserPass($value,$rule,$data){
        $password = Db::name('user')->where('id',UID)->value('password');
        if(think_ucenter_md5($value) === $password){
            return true;
        }
        return false;
    }

    protected function checkPwdCode($value,$rule,$data){
        $cell_code=Session::set('cell_code', '', 'email_'.$rule);
        if((time()-$cell_code['cell_time'])>180){
            Session::delete('email_'.$rule,'cell_code');
            return false;
        }
        if($cell_code['cell_code'] == $value){
            Session::delete('email_'.$rule,'cell_code');
            return true;
        }else{
            return false;
        }
    }
}