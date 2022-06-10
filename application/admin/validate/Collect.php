<?php
namespace app\admin\validate;
use think\Validate;
use think\Db;

class Collect extends Validate{
	protected $rule =   [
        'title'  => 'require',
        'source_url' => 'require',
        'url_rule' => 'require',
        'category_way' => 'checkCategoryWay:true'
    ];

    protected $message  =   [
        'title.require' => '规则名称必须填写！',
        'source_url.require'  => '采集列表必须填写！',
        'url_rule.require' => '采集列表网址规则必须填写！',
    ]; 

    protected function checkCategoryWay($value,$rule,$data=[])
    {
    	if($value==1){
    		return empty($data['category_fixed'])?'请选择固定分类':true;
    	}else{
    		return empty($data['category_equivalents'])?'栏目转换必须填写':true;
    	}
    }
}