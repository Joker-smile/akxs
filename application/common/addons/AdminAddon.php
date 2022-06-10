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
use think\Config;

class AdminAddon extends Addon{

	public $request;

	public function __construct(){
		parent::__construct();
        Config::load(APP_PATH.'admin/config.php');
        $this->request=Request::instance();
        define('AID',is_login('admin'));
        if(!AID){
            $this->redirect(url('admin/publicl/login'));
        }
		$this->assign('__MENU__', $this->getMenus());
    }

    /**
     * 对数据表中的单行或多行记录执行修改 GET参数id为数字或逗号分隔的数字
     *
     * @param string $model 模型名称,供M函数使用的参数
     * @param array  $data  修改的数据
     * @param array  $where 查询时的where()方法的参数
     * @param array  $msg   执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'')
     */
    final protected function editRow ( $model ,$data, $where , $msg ){
        $id    = array_unique((array)$this->request->param('id'));
        $id    = is_array($id) ? implode(',',$id) : $id;
        $fields = Db::name($model)->getTableInfo();
        if(in_array('id',$fields) && !empty($id)){
            $where = array_merge( ['id' => ['in', $id ]] ,(array)$where );
        }
        $msg   = array_merge( ['success'=>'操作成功！', 'error'=>'操作失败！', 'url'=>''] , (array)$msg );
        if( Db::name($model)->where($where)->update($data)!==false ) {
            return $this->success($msg['success'],$msg['url']);
        }else{
            $this->error($msg['error'],$msg['url']);
        }
    }

    /**
     * 禁用条目
     * @param string $model 模型名称,供D函数使用的参数
     * @param array  $where 查询时的 where()方法的参数
     * @param array  $msg   执行正确和错误的消息,可以设置四个元素 array('success'=>'','error'=>'', 'url'=>'')
     *
     */
    protected function forbid ( $model , $where = [] , $msg = ['success'=>'状态禁用成功！','error'=>'状态禁用失败！']){
        $data    =  ['status' => 0];
        return $this->editRow( $model , $data, $where, $msg);
    }

    /**
     * 恢复条目
     * @param string $model 模型名称,供D函数使用的参数
     * @param array  $where 查询时的where()方法的参数
     * @param array  $msg   执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'')
     */
    protected function resume (  $model , $where = [] , $msg = ['success'=>'状态恢复成功！','error'=>'状态恢复失败！']){
        $data    =  ['status' => 1];
        return $this->editRow( $model , $data, $where, $msg);
    }

    protected function request_url(){
        $request_url=$this->request->path();
        $request_url=explode('/',$request_url);
        for ($i=0; $i < 3; $i++) { 
            $rurl.=$request_url[$i].'/';
        }
        return rtrim($rurl,'/');
    }
	
    final public function getMenus(){
        if(empty($menus)){
            // 获取主菜单
            $where  =  ['pid'=>0,'hide'=>0];
            $menus['main']  =   Db::name('menu')->where($where)->order('sort asc')->select();
            $menus['child'] = []; //设置子节点
            //高亮主菜单
            $pid = Db::name('menu')->where("url like '%".$this->request_url()."%'")->value('id');
            if($pid){
                $nav = model('admin/menu')->getPath($pid);
                foreach ($menus['main'] as $key => $item) {
                    // 获取当前主菜单的子菜单项
                    if($item['id'] == $nav['id']){
                        //生成child树
                        $groups = Db::name('Menu')->where(['group'=>['neq',''],'pid' =>$item['id']])->distinct(true)->order('sort asc')->column("group");
                        //获取二级分类的合法url
                        $where = ['pid'=>$item['id'],'hide'=>0];
                        $second_urls = Db::name('Menu')->where($where)->column('url','id');
                        // 按照分组生成子菜单树
                        foreach ($groups as $g) {
                            $map = ['group'=>$g,'pid'=>$item['id'],'hide'=>0];
                            $menuList = Db::name('menu')->where($map)->field('id,pid,title,url,icon,tip')->order('sort asc')->select();
                            $menus['child'][$g] = list_to_tree($menuList, 'id', 'pid', 'operater', $item['id']);
                        }
                    }
                }
            }
        }
        return $menus;
    }

    protected function fetch($template='', $vars = [], $replace = [], $config =[]){
        return $this->view->fetch($template, $vars, array_merge($replace,Config::get('view_replace_str')),$config);
    }

    public function install(){
        return true;
    }

    public function uninstall(){
        return true;
    }
}