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

namespace app\api\controller;
use think\Controller;
use think\Db;
use think\facade\Cookie;
use org\Oauth;

class Oauthcall extends Controller{
	public function index(){
		if($this->request->isGet()){
			if($token=$this->request->get('access_token')){
				$expires=$this->request->get('expires_in');
				$auth = new Oauth();
				$auth->setToken($token,$expires);
				$this->redirect(Cookie::get('__forward__'),302);
			}
		}
	}
}