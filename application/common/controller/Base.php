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

namespace app\common\controller;
use think\Controller;
use think\Db;
use think\facade\Config;
use think\facade\Env;
use think\facade\Cache;

class Base extends Controller{
    protected $mold;
    protected $home_tplpath;

    protected function initialize(){
        if(!Config::get('web.close')){
            $this->error(Config::get('web.lose_tip'));
        }
        if(!defined('UID')){
            define('UID',is_login());
        }
        $this->mold=($this->request->isMobile())?'wap':'web';
        $map[] = ['','exp',Db::raw('find_in_set("'.$this->mold.'",`mold`)')];
        $map[] = ['default','=',1];
        $tpl_name=Db::name('Template')->where($map)->value('name');
        $this->home_tplpath=Config::get('web.default_tpl').'/'.$tpl_name.'/';
        $this->view->config(['cache_path'=>Env::get('runtime_path').'temp'.DIRECTORY_SEPARATOR.'home'.DIRECTORY_SEPARATOR.$this->mold.DIRECTORY_SEPARATOR]);
        $this->assign('web',Config::get('web.'));
        if(UID){
            $this->assign('user',model('user/user')->get_info());
        }
        $this->assign('mold',$this->mold);
        $this->assign('home_tplpath','/'.$this->home_tplpath);
	}

    protected function fetch($template = '', $vars = [], $config = [], $renderContent = false)
    {
        $fetch=$this->view->fetch($template, $vars, $config, $renderContent);
        if(!in_array(strtolower($this->request->controller()."/".$this->request->action()),['comment/tree','comment/list'])){
            if($this->mold=="web"){
                $fetch.='<script src="/public/static/layer/layer.js"></script>';
            }else{
                $fetch.='<script src="/public/static/layer_mobile/layer.js"></script>';
            }
            $fetch.='<script type="text/javascript">';
            $fetch.='var view={controller:"'.strtolower($this->request->controller()).'",action:"'.strtolower($this->request->action()).'",mold:"'.$this->mold.'"};'.PHP_EOL;
            if(in_array(strtolower($this->request->controller()."/".$this->request->action()),['chapter/index','novel/index'])){
                $book_id=$this->view->__get('id');
                $is_bookshelf=model('user/bookshelf')->check($book_id);
                $fetch.='var book_id='.$book_id.',user_id='.UID.',is_bookshelf='.($is_bookshelf?$is_bookshelf:0).';';
            }
            $fetch.='</script>';
            $fetch.='<script src="/public/home/js/home.js"></script>';
        }
        if(Config::get('web.html_cache')){
            $key=md5($this->request->url(true));
            if($key){
                $options = [
                    'expire'=>  0,
                    'path'  =>  Env::get('runtime_path').'html'.DIRECTORY_SEPARATOR.$this->mold.DIRECTORY_SEPARATOR,
                ];
                $html_cache=Cache::connect($options)->get($key);
                if(!$html_cache){
                    Cache::connect($options)->set($key,$fetch);
                    $html_cache=$fetch;
                }
                return $html_cache;
            }
        }else{
            return $fetch;
        }
         
    }
}