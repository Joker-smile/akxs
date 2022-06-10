<?php
namespace app\admin\validate;
use think\Validate;
use think\Db;

class Novel extends Validate{
	protected $rule =   [
        'title'  => 'require',
        'category' => 'require',
        'pic' => 'require',
        'author' => 'require',
        'content' => 'require',
    ];

    protected $message  =   [
        'title.require' => '名称必须填写！',
        'category.require' => '必须选择栏目！',
        'pic.require' => '封面必须填写！',
        'author.require' => '作者必须填写！',
        'content.require'  => '介绍必须填写！',
    ];

    protected $scene = [
        'edit'  =>  ['title','category','pic','author','content','id'=>'require'],
        'add' => ['title','category','pic','author','content'],
        'position' => ['id'=>'require']
    ];
}