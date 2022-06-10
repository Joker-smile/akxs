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

class User extends Controller{
	public function index($version = ''){
        $data['code'] = 0;
        $data['uid'] = 100001;
        return json($data);
	}
}
