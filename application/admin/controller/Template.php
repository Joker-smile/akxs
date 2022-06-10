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

namespace app\admin\controller;
use think\Db;

class Template extends Base
{

    public function index(){
        $list = Db::name('template')->paginate(config('web.list_rows'));
        $this->assign('list', $list);
        $this->assign('meta_title','模版列表');
        return $this->fetch();
    }

    public function set_default($id){
        $Template=model('template');
        if($this->request->isPost()){
            $data = $this->request->post();
            $res = $Template->set_default($data);
            if($res  !== false){
                return $this->success('设置默认模版成功！',url('index'));
            } else {
                $this->error($Template->getError());
            }
        }else{
            $info=$Template->info($id);
            $this->assign('info',$info);
            $this->assign('meta_title','设置默认模版');
            return $this->fetch();
        }
    }
	
	public function lists($path){
        $Template=model('template');
        $list_info=$Template->file_list(urldecode($path));
        $this->assign('top_dir',dirname(urldecode($path)));
        $this->assign('list',$list_info);
        $this->assign('meta_title','模版管理');
        return $this->fetch();
    }

    public function select_template($mold='web'){
        $tpl_name=Db::name('Template')->where(['mold'=>$mold,'default'=>1])->value('name');
        $Template=model('template');
        $list_info=$Template->file_list(config('web.default_tpl').'/'.$tpl_name,false,'html');
        $this->assign('list',$list_info);
        $this->assign('meta_title','模版选择');
        return $this->fetch();
    }

    public function edit(){
        $Template=model('template');
        $data=$this->request->post();
        if($this->request->isPost()){
            $res = $Template->edit($data);
            if($res  !== false){
                return $this->success('模版文件修改成功！',url('index'));
            } else {
                $this->error($Template->getError());
            }
        }else{
            $path=urldecode($this->request->param('path'));
            $info=$Template->file_info($path);
            $this->assign('path',$path);
            $this->assign('content',$info);
            $this->assign('meta_title','修改模版文件');
            return $this->fetch();
        }
    }
}