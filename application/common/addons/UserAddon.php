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

namespace app\common\addons;
use app\common\addons\Addon;
use think\Db;
use think\View;
use think\Request;
use think\Cache;

class UserAddon extends Addon{

	public $request;

    protected $beforeActionList = [
        'check_login'  =>  ['except'=>'config,enable,disable,adminList,saveConfig,install,uninstall'],
    ];

	public function __construct(){
		parent::__construct();
        $this->request=Request::instance();
        $this->assign('user_menu', $this->get_menu());
    }

    public function check_login(){
        if(!defined('UID')){
            define('UID',is_login());
        }
        if(!UID){
            Cookie('__forward__',$_SERVER['HTTP_REFERER']);
            if(Request::instance()->isAjax()){
                $this->error('请先登录！',url('home/user/login'));
            }else{
                $this->redirect('home/user/login');
            }
        }
        $this->assign('user',model('user/user')->get_info(UID));
    }

    private function get_menu(){
        $user_menu=Cache::get('user_menu');
        if(!$user_menu){
            $where=['pid'=>0,'hide'=>0];
            $user_menu = Db::name('user_menu')->where($where)->order('sort asc')->select();
            foreach ($user_menu as $key => $value) {
                $menu= Db::name('user_menu')->where('pid',$value['id'])->order('sort asc')->select();
                $user_menu[$key]['child']=$menu;
            }
            Cache::set('user_menu',$user_menu);
        }
        return $user_menu;
    }

    public function install(){
        return true;
    }

    public function uninstall(){
        return true;
    }
}