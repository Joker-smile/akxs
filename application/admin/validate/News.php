<?php
namespace app\admin\validate;
use think\Validate;
use think\Db;

class News extends Validate{
	protected $rule =   [
        'title'  => 'require',
        'category' => 'require',
        'content' => 'require',
    ];

    protected $message  =   [
        'title.require' => '标题必须填写！',
        'category.require'  => '必须选择栏目！',
        'content.require'  => '内容必须填写！',
    ];

    protected $scene = [
        'edit'  =>  ['title','category','content','id'=>'require'],
        'add' => ['title','category','content'],
        'position' => ['id'=>'require']
    ];
}