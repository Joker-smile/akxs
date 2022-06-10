<?php
namespace app\admin\controller;
use think\Db;

class Feedback extends Base
{

    public function index($type,$id=null){
        $list = Db::name('feedback')->order('id asc')->paginate(config('web.list_rows'));
        $this->assign('list', $list);
        $this->assign('meta_title','用户反馈列表');
        return $this->fetch();
    }

	public function edit($id){
		$feedback=model('feedback');
		if($this->request->isPost()){
            $data=$this->request->post();
            if(empty($data['status'])) $data['status'] = 0;
			$res = $feedback->edit($data);
			if($res  !== false){
                return $this->success('处理成功！',url('index'));
            } else {
                $this->error($feedback->getError());
            }
		}else{
			$info=$feedback->info($id);
            $this->assign('info',$info);
			$this->assign('meta_title','处理反馈');
			return $this->fetch();
		}
	}

	public function del(){
        $feedback=model('feedback');
        $id = array_unique((array)$this->request->param('id'));
        if ( empty($id) ) {
            $this->error('请选择要操作的数据!');
        }
        $res=$feedback->del($id);
        if($res){
            return $this->success('删除成功');
        } else {
            $this->error($feedback->getError());
        }
    }
}