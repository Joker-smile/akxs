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

namespace app\common\model;
use think\Model;
use think\Db;
use think\Validate;
use think\facade\Config;

class Comment extends Model {

    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $auto = ['content'];
    protected $insert = ['status' => 1];

	public function comment_add($data){
        $validate   = Validate::make(['content'=>'require'],['content.require'=>'评论内容不能为空']);
        $result = $validate->check($data);
        if(!$result) {
            $this->error=$validate->getError();
            return false;
        }
		$data['uid']=UID;
        return Comment::save($data);
    }

    /**
     * 获取分类树，指定分类则返回指定分类极其子分类，不指定则返回所有分类树
     * @param  integer $id    分类ID
     * @param  boolean $field 查询字段
     * @return array          分类树
     */
    public function get_tree($mid, $type='novel', $id = 0, $field = true){
        $map['status']=1;
        $map['type']=$type;
        $map['mid']=$mid;
        /* 获取所有分类 */
        $list = Comment::field($field)->where($map)->order('up desc,id desc')->select();
        foreach ($list as $key => $value) {
        	$list[$key]['user']=model('user/user')->get_info($value['uid'],'username,headimgurl,exp,integral');
        }
        $list = list_to_tree($list->toArray(), $pk = 'id', $pid = 'pid', $child = '_', $root = $id);
        return $list;
    }

    protected function setContentAttr($value){
        $str = htmlspecialchars($value);
        $comment_key = preg_split('/[\r\n]+/', trim(Config::get('web.comment_key'), "\r\n"));
        $str = str_replace($comment_key, '***', $str);
        return $str;
    }
}
