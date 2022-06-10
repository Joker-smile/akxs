<?php
namespace app\admin\validate;
use think\Validate;
use think\Db;

class Template extends Validate{
	protected $rule =   [
        'mold'  => 'require'
    ];

    protected $message  =   [
        'mold.require' => '模版类型必须选择！'
    ];  
}