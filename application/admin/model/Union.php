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
use think\facade\Request;
use think\facade\Config;
use net\Http;
use org\Oauth;

class Union extends Model{

    private $oauth_access_token;

    protected function initialize(){
        parent::initialize();
        $auth = new Oauth();
        $this->oauth_access_token="AuthorizationCode: OAuth =".$auth->getToken();
    }

	public function user_info(){
        $url=Config::get('web.official_url').'/oauth/user';
		$content=Http::doGet($url,30,$this->oauth_access_token);
        $content=json_decode($content,true);
		return $content;
	}

    public function user_data(){
        $url=Config::get('web.official_url').'/union/user/data';
        $content=Http::doGet($url,30,$this->oauth_access_token);
        $content=json_decode($content,true);
        return $content;
    }

    public function integral_add($data){
        $url=Config::get('web.official_url').'/union/integral/add';
        $content=Http::doPost($url,$data,30,$this->oauth_access_token);
        $content=json_decode($content,true);
        if(empty($content['code'])){
            $this->error=$content['msg'];
            return false;
        }else{
            return true;
        }
    }
	
    public function integral_list(){
        $url=Config::get('web.official_url').'/union/integral/list/'.Config::get('web.list_rows').'/'.Request::param('type',0);
        if($p=Request::param('page')){
            $url.='/'.$p;
        }
        $content=Http::doGet($url,30,$this->oauth_access_token);
        $content=json_decode($content,true);
        if(empty($content['code'])){
            $this->error=$content['msg'];
            return false;
        }else{
            return $content;
        }
    }

    public function integral_del($id){
        $url=Config::get('web.official_url').'/union/integral/del/'.$id;
        $content=Http::doGet($url,30,$this->oauth_access_token);
        $content=json_decode($content,true);
        if(empty($content['code'])){
            $this->error=$content['msg'];
            return false;
        }else{
            return true;
        }
    }

    public function log($type){
        $url=Config::get('web.official_url').'/union/log/'.$type."/".Config::get('web.list_rows');
        $content=Http::doGet($url,30,$this->oauth_access_token);
        $content=json_decode($content,true);
        return $content;
    }
}