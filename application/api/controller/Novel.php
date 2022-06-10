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
use think\facade\Cache;

class Novel extends Controller{

	protected $beforeActionList = [
        'checkDeduct'  =>  ['only'=>'content,get_chapter_list']
    ];

    protected function checkDeduct(){
    	$user_ip=$this->request->ip();
    	$allow_ip=['127.0.0.1','localhost'];
    	if(in_array($user_ip,$allow_ip)){
    		return false;
    	}
//        $oauth = new Oauth();
//		$check_deduct=$oauth->checkDeduct('novel');
//		$check_deduct=json_decode($check_deduct,true);
//		if($check_deduct['code']!=1){
//			echo json_encode($check_deduct);
//			exit;
//		}
    }
	public function comment(){
		$data = ['url' => 'https://apps.apple.com/cn/app/id1474948770'];
		return json($data);
	}
    /**
     * banner
     * @param bool $cid
     * @param int $type
     * @param bool $filter
     * @return \think\response\Json
     */
	public function banner(){
	    $limit = 10;
		$banner=model('common/api')->get_slider($limit);
		return json($banner);
	}/**
     * 获取分类
     * @param bool $cid
     * @param int $type
     * @param bool $filter
     * @return \think\response\Json
     */
	public function category($cid=false,$type=0,$filter=false){
		$category=model('api/api')->category($cid,$type,$filter);
		return json($category);
	}

    /**
     * 分类下的书籍列表
     * @param bool $cid
     * @param string $order
     * @param int $limit
     * @param bool $pos
     * @param bool $time
     * @param bool $newbook
     * @param bool $over
     * @param bool $author
     * @param int $paginator
     * @param null $id
     * @return \think\response\Json
     */
	public function lists($cid=false,$order='update_time desc',$limit=20,$pos=false,$time=false,$newbook=false,$over=false,$author=false,$paginator=1,$id=null){
		$list=model('common/api')->get_novel($cid,$order,$limit,$pos,$time,$newbook,$over,$author,$paginator,$id);
		return json($list);
	}

    /**
     * 某本书的章节列表
     * @param $id
     * @param string $order
     * @param string $limit
     * @param bool $page
     * @return \think\response\Json
     */
	public function content($id,$order='id asc',$limit='',$page=false){
		$book=model('common/api')->novel_detail($id);
		$book['chapter']=model('common/api')->get_chapter_list($id,$order,$limit,$page);
		return json($book);
	}

    /**
     * 某本书的章节列表带书的内容
     * @param $id
     * @param string $order
     * @param string $limit
     * @param bool $page
     * @return \think\response\Json
     */
	public function get_chapter_list($id, $order='id asc', $limit='', $page=false){
		$chapter_list=model('common/api')->get_chapter_list($id, $order, $limit, $page);
        return json($chapter_list);

    }

	public function chapter($id,$key){
		$chapter=model('common/api')->get_chapter($id,$key);
		if($chapter['content']) $chapter['content'] = str_replace(['<p>','<p/>','</p>'],['',"\n","\n"],$chapter['content']);
		return json($chapter);
	}
    public function index($cid=false){
        $key = $cid == 4 ?  'index_categroy_boy' :   'index_categroy_girl' ;
        $json =Cache::get($key);
        if($json){
            return $json;
        }
        $order='update_time desc';
        $limit=10;
        $pos=false;
        $time=false;
        $newbook=false;
        $over=false;
        $author=false;
        $paginator=0;
        $id=null;
        //4 男生 8 女生
	    $map = ['status' => 1,'pid' => $cid];
        $category =Db::name('category')->field('id,title')->where($map)->order('sort')->select();
	$rdata = [];
        foreach ($category as $item){
		$list =  model('common/api')->get_novel($item['id'],$order,$limit,$pos,$time,$newbook,$over,$author,$paginator,$id);
		if(empty($list)){
			unset($item);
		}else{
            		$item['list'] = $list;
			$rdata[] = $item;
		}
        }
        Cache::set($key,json_encode($rdata),3000);
        return json($rdata);
	}


    public function rank($cid=false,$order='update_time desc',$limit=20,$pos=false,$time=false,$newbook=false,$over=false,$author=false,$paginator=1,$id=null){
	    if($cid == 1){
            $order ='hits_day desc';
        }elseif ($cid ==2){
            $order ='hits_week desc';
	    }elseif ($cid ==3){
            $order ='update_time desc';
	    }elseif ($cid ==4){
            $order ='hits_month desc';
            $over="1";
        }else{
            $order ='update_time desc';
        }
        $list=model('common/api')->get_novel(false,$order,$limit,$pos,$time,$newbook,$over,$author,$paginator,$id);
        return json($list);
    }

    public function rank_category($cid=false,$type=0,$filter=false){
	    $data = [];
	    $data[] = ['id' => 1 , 'title' => '惊悚榜'];
	    $data[] = ['id' => 2 , 'title' => '修仙榜'];
	    $data[] = ['id' => 3 , 'title' => '留存单'];
	    $data[] = ['id' => 4 , 'title' => '热度榜'];
        return json($data);
    }

}
