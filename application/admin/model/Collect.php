<?php
namespace app\admin\model;
use think\Model;
use think\facade\Request;
use think\facade\Cache;
use think\Db;
use app\admin\validate\Collect as CollectValidate;

class Collect extends Model{

    protected $autoWriteTimestamp = true;
    protected $json = ['rule'];
    protected $jsonAssoc = true;
    protected $auto = ['category_equivalents'];

    protected function setCategoryEquivalentsAttr($value){
        return str_replace(chr(10),'#',$value);
    }

    public function getTypeTextAttr($value,$data){
        $status = ['novel'=>'小说','news'=>'文章'];
        return $status[$data['type']];
    }

	public function info($id){
		$map['id'] = $id;
    	$info=Collect::where($map)->find();
		return $info;
	}

    public function lists(){
        return Collect::order('id asc')->paginate(config('web.list_rows'));
    }

	public function edit(){
        $data=Request::post(false);
        $validate = new CollectValidate;
        if (!$validate->check($data)) {
            $this->error=$validate->getError();
            return false;
        }
        $Collect = new Collect();
        if(empty($data['id'])){
            $result = $Collect->allowField(true)->save($data);
        }else{
            Cache::clear('collect');
            $result = $Collect->allowField(true)->isUpdate(true)->save($data);
        }
        if(false === $result){
            $this->error=$Collect->getError();
            return false;
        }
        return $result;
    }

    public function field(){
        $data=["field"=>[
                "novel"=>[
                    "category"=>"栏目",
                    "title"=>"名称",
                    "author"=>"作者",
                    "serialize"=>"连载",
                    "pic"=>"图片",
                    "content"=>"介绍",
                    "tag"=>"标签",
                    "chapter_title"=>"章节名称",
                    "chapter_content"=>"章节内容"
                ],
                "news"=>[
                    "category"=>"栏目",
                    "title"=>"名称",
                    "pic"=>"图片",
                    "content"=>"内容"
                ]
            ],
            "category"=>[
                'novel'=>get_tree(0),
                'news'=>get_tree(1)
            ]
        ];
        return $data;
    }
}