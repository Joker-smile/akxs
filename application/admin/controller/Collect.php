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
use think\facade\Cache;
use net\Gather;

set_time_limit(0);
ini_set('memory_limit', '-1');

class Collect extends Base
{

    public function index(){
        $Collect=model('collect');
        $this->assign('list', $Collect->lists());
        $this->assign('meta_title','采集列表');
        return $this->fetch();
    }
	
	public function edit($id){
		$Collect=model('collect');
		if($this->request->isPost()){
			$res = $Collect->edit();
			if($res  !== false){
                $this->success('采集修改成功！',url('index'));
            } else {
                $this->error($Collect->getError());
            }
		}else{
			$info=$Collect->info($id);
            $this->assign('info',$info);
            $this->assign('field',$Collect->field());
			$this->assign('meta_title','修改采集');
			return $this->fetch();
		}
	}

	public function add(){
		$Collect=model('collect');
		if($this->request->isPost()){
			$res = $Collect->edit();
			if($res  !== false){
                $this->success('采集添加！',url('index'));
            } else {
                $this->error($Collect->getError());
            }
		}else{
            $this->assign('field',$Collect->field());
			$this->assign('meta_title','添加采集');
			return $this->fetch('edit');
		}
	}

	public function del(){
        $id = array_unique((array)$this->request->param('id'));
        if ( empty($id) ) {
            $this->error('请选择要操作的数据!');
        }
        $map = ['id' => $id];
        if(Db::name('Collect')->where($map)->delete()){
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }

    public function source(){
        $this->assign('index',$this->request->param('index'));
        $this->assign('meta_title','添加列表采集');
        return $this->fetch();
    }

    public function relation(){
        $this->assign('index',$this->request->param('index'));
        $this->assign('meta_title','添加关联采集');
        return $this->fetch();
    }

    public function collect($id,$source_num=0,$test=['state'=>false],$start=1){
        if($test['state']===false){
	        $Collect=model('collect');
	        $info=$Collect->info($id);
	        $list_key=$this->request->param('list_key');
	    }else{
	    	$info=$this->request->param('info');
	    	$info['id']=0;
	    }
        if($start===1){
        	Db::name('collect')->where('id',$id)->setField('collect_time',time());
        	Cache::clear('collect');
        }
        $source_url=Cache::tag('collect')->get('collect_source_url_'.$info['id']);
        if(empty($source_url)){
        	$source_url=Gather::convert_source_url($info['source_url']);
        	Cache::tag('collect')->set('collect_source_url_'.$info['id'],$source_url);
        }

        if($test['state']===false){
        	$source_count=count($source_url);
	        if($source_count<=$source_num){
	        	Cache::clear('collect');
	        	return Gather::echo_msg('采集完成！');
	        }
	        Gather::echo_msg('起始页面:url['.$source_url[$source_num].'] 共计:<b>'.($source_num+1).'/'.$source_count.'</b>页');
        }

        $list_url=Cache::tag('collect')->get('list_url_'.$info['id'].'_'.$source_num);
        if(empty($list_url)){
        	$list_content_html=Gather::get_html($source_url[$source_num],$info['charset'],$info['url_complete']);

            if (empty($list_content_html)){
	        	return Gather::echo_msg('未获得起始页面数据!:url['.$source_url[$source_num].']');
	        }

            if(!empty($info['section'])){
                $list_content_html=Gather::get_section_data($list_content_html,$info['section']);
            }
	        $list_url=Gather::field_rule(['rule'=>$info['url_rule'],'merge'=>$info['url_merge']],$list_content_html,true);


            Cache::tag('collect')->set('list_url_'.$info['id'].'_'.$source_num,$list_url);
        }
        if($list_url){
        	$url_map=['id'=>$info['id'],'source_num'=>$source_num,'start'=>0];
            $list_url=array_unique($list_url);
            if($info['url_reverse']){
                $list_url=array_reverse($list_url);
            }
            foreach ($list_url as $key=>$cont_url) {
                if (!empty($info['url_must'])) {
                    if (!preg_match('/' . $info['url_must'] . '/i', $cont_url)) {
                        continue;
                    }
                }
                if (!empty($info['url_ban'])) {
                    if (preg_match('/' . $info['url_ban'] . '/i', $cont_url)) {
                        continue;
                    }
                }
                if($test['state']===false){
					if($key<$list_key && $list_key){
	                	continue;
	                }
                }else{
	                if($key!=0){
                		continue;
                	}
                }
                if (!empty($cont_url)){
                    if (strpos($cont_url, ' ') == false) {
                    	Gather::echo_msg('列表页面:url['.$cont_url.'] 共计:<b>'.($key+1).'/'.count($list_url).'</b>条');

                        $return=Gather::field_content($cont_url,$info,[],$test,$source_num);

                        if($test['state']===false){

                            if($return['finish'] || (empty($return['finish']) && $info['type']=='news')){
		            			if(count($list_url)<=$key+1){
		            				Cache::tag('collect')->rm('list_url_'.$info['id'].'_'.$source_num);
		            				$url_map['source_num']=$source_num+1;
		            				return Gather::echo_jump(url('collect',$url_map),2);
		            			}	
			            	}else{
			            		$url_map['list_key']=$key;
			            		$url_map['list_page']=json_encode($return['list_page']);
			            		return Gather::echo_jump(url('collect',$url_map),2);
			            	}
				        }
                    }
                }

            }

            if($test['state']===false){
	            $url_map['source_num']=$source_num+1;
	            return Gather::echo_jump(url('collect',$url_map),2);
	        }

        }else{
        	return Gather::echo_msg('未获得列表网址，请检查列表规则!:url['.$source_url[$source_num].']');
        }
    }

    public function test(){
    	$this->assign('field',$this->request->param('field'));
        $this->assign('meta_title','采集规则测试');
        return $this->fetch();
    }
}