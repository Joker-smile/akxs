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
use org\Oauth;

class Feedback extends Controller{

    protected function checkDeduct(){
    	$user_ip=$this->request->ip();
    	$allow_ip=['127.0.0.1','localhost'];
    	if(in_array($user_ip,$allow_ip)){
    		return false;
    	}
    }

    public function submit(){
        $feedback=model('admin/feedback');
        if($this->request->isPost()){
            $data=$this->request->post();
            if(empty($data['content']) || empty($data['contacts'])  ){
            //if(empty($data['content']) || empty($data['contacts']) || empty($data['pid']) ){
                return json(['msg' => '参数错误']);
            }
            $res = $feedback->edit($data);
            if($res  !== false){
                return json(['msg' => '反馈成功！']);
            } else {
                return json(['msg' => '反馈失败！']);
            }
        }else{
            return json(['msg' => '非法请求']);
        }
    }

}
