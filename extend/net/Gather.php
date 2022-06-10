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
namespace net;

use think\Db;
use think\facade\Request;
use think\facade\Config;
use think\facade\Validate;
use net\Http;
use org\File;

class Gather
{
    static protected $sign_match = '\[内容(?P<num>\d*)\]';
    protected static $echo_msg_head;
    protected static $page;
    protected static $list_page;

    //自动编码转换
    static public function auto_convert2utf8($str,$encode) {
        if(empty($encode) || $encode=='auto'){
            $encode = mb_detect_encoding($str, array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'));
        }
        if (strcasecmp($encode, 'utf-8') !== 0) {
            $str = iconv($encode, 'utf-8//IGNORE', $str);
        }
        return $str;
    }

    //获取数据
    static public function get_html($url,$encode,$url_complete){
        $html=Http::doGet($url);
        $collect_sleep=Config::get('web.collect_sleep');
        if(!empty($collect_sleep)){
            sleep($collect_sleep);
        }

        if($url_complete){
            $html=self::url_complete($html,$url);
        }
        return self::auto_convert2utf8($html,$encode);
    }

    //生成起始地址
	static public function convert_source_url($url) {
		$urls = [];
        $url_array=json_decode($url,true);
        foreach ($url_array as $key => $value) {
        	switch ($value['type']) {
        		case 1:
        			if ($value['param'][3]) {
        				for ($i = $value['param'][1];$i <= $value['param'][0];$i--) {
	                        $urls[]=str_replace('[内容]',$value['param'][0]+($i-$value['param'][0])*$value['param'][2],$value['url']);
	                    }
        			}else{
	        			for ($i = $value['param'][0];$i <= $value['param'][1];$i++) {
	                        $urls[]=str_replace('[内容]',$value['param'][0]+($i-$value['param'][0])*$value['param'][2],$value['url']);
	                    }
                	}
        			break;
        		case 2:
        			$urls=array_merge($urls,explode(chr(10), $value['url']));
        			break;
        		default:
        			$urls[]=$value['url'];
        			break;
        	}
        }
        return $urls;
    }

    //列表正则
    static public function convert_sign_match($str) {
        $str = preg_replace_callback('/(\={0,1})(\s*)([\'\"]{0,1})' . self::$sign_match . '\3/', function ($matches) {
            $ruleStr = $matches[1] . $matches[2] . $matches[3] . '(?P<match' . $matches['num'] . '>';
            if (!empty($matches[1]) && !empty($matches[3])) {
                $ruleStr.= '[^\<\>]*?)';
            } else {
                $ruleStr.= '[\s\S]*?)';
            }
            $ruleStr.= $matches[3];
            return $ruleStr;
        }, $str);
        $str = preg_replace('/\\\*([\'\/])/', "\\\\$1", $str);
        $str = str_replace('(*)', '[\s\S]*?', $str);
        return $str;
    }

    //地址拼接
    static public function set_merge_default($reg, $merge) {
        if (empty($merge)) {
            $merge = '';
            if (!empty($reg)) {
                if (preg_match_all('/\<match(?P<num>\d*)\>/i', $reg, $match_signs)) {
                    foreach ($match_signs['num'] as $snum) {
                        $merge.= '[内容'.$snum.']';
                    }
                }
            }
        }
        return $merge;
    }

    // 获取指定标记中的内容
    static public function get_section_data($str, $section){
        if(!$section || !$str){
            return $str;
        }
        $section = explode('[内容]', $section);
        if (empty($section[0]) || empty($section[1])){
            return $str;
        }
        $str = explode($section[0], $str);
        $str = explode($section[1], $str[1]);
        return $str[0];
    }

    //规则采集
    static public function field_rule($field_params, $html, $is_loop = false) {
        if(!empty($field_params['rule_multi'])) $is_loop=true;
        $field_params['rule'] = self::convert_sign_match($field_params['rule']);
        $field_params['merge'] = self::set_merge_default($field_params['rule'],$field_params['merge']);
        if (!empty($field_params['rule']) && preg_match_all('/' . self::$sign_match . '/i', $field_params['merge'], $match_signs)) {
            if ($is_loop) {
                preg_match_all('/' . $field_params['rule'] . '/i', $html, $match_conts, PREG_SET_ORDER);
            } else {
                if (preg_match('/' . $field_params['rule'] . '/i', $html, $match_cont)) {
                    $match_conts = [$match_cont];
                }
            }

            $curI = 0;
            if(!empty($match_conts)){
                foreach ($match_conts as $match_cont) {
                    $curI++;
                    $re_match = [];
                    foreach ($match_signs['num'] as $ms_k => $ms_v) {
                        $re_match[$ms_k] = $match_cont['match' . $ms_v];
                    }
                    $contVal = str_replace($match_signs[0], $re_match, $field_params['merge']);
                    if(!empty($field_params['strip'])){
                        if(strpos($field_params['strip'],'all') !== false){
                            $contVal = self::strip_tags_content($contVal,'style,script,object');
                            $contVal = strip_tags($contVal);
                        } else {
                            $contVal = self::strip_tags_content($contVal,$field_params['strip']);
                        }
                    }
                    if(!empty($field_params['replace'])){
                        // if (preg_match_all('/([^\r\n]+?)\=([^\r\n]+)/', $field_params['replace'], $mlist)) {
                        //     $replace_re = is_array($mlist[1]) ? $mlist[1] : null;
                        //     $replace_to = is_array($mlist[2]) ? $mlist[2] : null;
                        //     if (!empty($replace_re) && count($replace_re) == count($replace_to)) {
                        //         $contVal = str_replace($replace_re, $replace_to, $contVal);
                        //     }
                        // }
                        foreach (explode(chr(10),$field_params['replace']) as $key => $value) {
                            $replace_gz=explode("=",$value);
                            $replace_re[]=$replace_gz[0];
                            $replace_to[]=$replace_gz[1];
                        }
                        if (!empty($replace_re) && count($replace_re) == count($replace_to)) {
                            $contVal = str_replace($replace_re, $replace_to, $contVal);
                        }
                    }
                    $contVal = str_replace(array("&nbsp;","&amp;","<br/>"),array("","","\n"),$contVal);
                    //$contVal = strip_tags($contVal);
                    if ($is_loop) {
                        if(empty($field_params['rule_multi'])){
                            $val[] = $contVal;
                        }else{
                            $val.= ($curI <= 1 ? '' : '|') . $contVal;
                        }
                    } else {
                        $val= $contVal;
                    }
                }
                return $val;
            }

        }
    }

    //字段内容
    static public function field_content($url,$info,$field,$test,$source_num,$page='default'){
        if($title=self::has_url($url,$info,$field)){
            self::echo_msg('url['.$url.']--['.$title.']已存在');
            return self::$list_page;
        }

        $html=self::get_html($url,$info['charset'],$info['url_complete']);
        if(!$html){
            self::echo_msg('无法获取页面:url['.$url.']');
            return false;
        }
        foreach ($info['rule'] as $rule_key => $rule_value){
            if(!$rule_value['rule'] && $test['state'] && $rule_value['field']===$test['field']){
                self::echo_msg('填写好规则后在进行测试！');
                return false;
            }
            if($rule_value['source']===$page){
                if($test['state']===false){
                    switch ($rule_value['field']) {
                        case 'category':
                            if($info['category_way']===1){
                                $field[$rule_value['field']]=$info['category_fixed'];
                            }else{
                                $category_mb=self::field_rule($rule_value,$html);
                                $category=self::category_equivalents($info['category_equivalents'],$category_mb);
                                $field[$rule_value['field']]=$category;
                                if(empty($category)){
                                    self::echo_msg('获取对应栏目出错--'.$category_mb.':url['.$url.']');
                                    return ['finish'=>true];
                                }
                            }
                            break;
                        case 'pic':
                            $pic=self::field_rule($rule_value,$html);
                            if($info['pic_local']==1){
                                $down_img = self::down_img($pic,$info['type']);
                                $pic = $down_img['path'];
                            }
                            $field[$rule_value['field']]=$pic;
                            break;
                        case 'serialize':
                            $field[$rule_value['field']]=0;
                            break;
                        case 'chapter_title':
                            $field[$rule_value['field']]=self::field_rule($rule_value,$html);
                            self::echo_msg('章节名称:['.$field[$rule_value['field']].']');
                            break;
                        case 'chapter_content':
                            $field[$rule_value['field']]=self::field_rule($rule_value,$html);
                            self::echo_msg('章节内容:['.mb_substr(strip_tags($field[$rule_value['field']]),0,80).'...]');
                            break;
                        case 'title':
                            $field[$rule_value['field']]=self::field_rule($rule_value,$html);
                            self::echo_msg('标题:['.$field[$rule_value['field']].']');
                            break;
                        default:
                            $field[$rule_value['field']]=self::field_rule($rule_value,$html);
                            unset($field['novel_id']);
                            break;
                    }
                }else{
                    if($rule_value['field']===$test['field']){
                        self::echo_msg('获取页面:url['.$url.']');
                        $test_value=self::field_rule($rule_value,$html);
                        if(empty($test_value)){
                            self::echo_msg('未获取到内容，请检测规则！');
                            self::echo_msg('<pre class="layui-code" lay-title="页面代码" lay-height="500px">'.htmlentities($html).'</pre><script>layui.use("code", function(){layui.code();});</script>');
                        }else{
                            self::echo_msg('获取结果:['.$test_value.']');
                        }
                        return false;
                    }
                }
            }
        }

        if($test['state']===false){
            $field=self::sql_data($info,$field,$url);
        }
        $relation=json_decode($info['relation_url'],true);
        if($relation){

            foreach ($relation as $key => $value) {
                if($value['page']===$page){
                    if(!empty($value['section'])){
                        $html=self::get_section_data($html,$value['section']);
                    }
                    $relation_list=self::field_rule(['rule'=>$value['url_rule'],'merge'=>$value['url_merge']],$html,true);


                    if(empty($relation_list)){
                        self::echo_msg('获取关联页面出错--'.$value['title'].':url['.$url.']');
                        if($test['state']!==false){
                            self::echo_msg('<pre class="layui-code" lay-title="页面代码" lay-height="500px">'.htmlentities($html).'</pre><script>layui.use("code", function(){layui.code();});</script>');
                        }
                        return false;
                    }else{
                        $relation_list=($test['state'])?[$relation_list[0]]:array_unique($relation_list);
                        self::$list_page=self::array_page($relation_list,Config::get('web.collect_chapter_page'),md5($url));
                        foreach (self::$list_page['data'] as $list_key => $list_url){
                            self::echo_msg('关联页面--'.$value['title'].':url['.$list_url.']');
                            self::field_content($list_url,$info,$field,$test,$source_num,strval($key));
                        }
                        return self::$list_page;
                    }
                }
            }
        }
    }

    static public function category_equivalents($content,$category) {
        foreach (explode("#",$content) as $key => $value) {
            $category_gz=explode("=",$value);
            if($category_gz[0]==$category){
                return Db::name('category')->where(['title'=>$category_gz[1],'status'=>1])->value('id');
            }
        }
    }

    static public function has_url($url,$info,$field) {
        if($info['type']=='novel'){
            if(isset($field['novel_id'])){
                if($chapter=Db::name($info['type'].'_chapter')->where(['novel_id'=>$field['novel_id']])->value('chapter')){
                    if(Config::get('web.data_save_compress')){
                        $chapter=@gzuncompress(base64_decode($chapter));
                    }
                    $chapter=json_decode($chapter,true);
                    $chapter_url=array_column($chapter,'title','reurl');
                    if(isset($chapter_url[$url])){
                        return $chapter_url[$url];
                    }
                }
            }
            $map['serialize']=1;
        }
        $map['reurl']=$url;
        if($title=Db::name($info['type'])->where($map)->value('title')){
            return $title;
        }
        return false;
    }

    static public function sql_data($info,$field,$url){
        $collect = new \app\admin\model\Collect;
        $collect_field=$collect->field();
        $diff_collect_field=$collect_field['field'][$info['type']];
        unset($diff_collect_field['chapter_title'],$diff_collect_field['chapter_content']);
        if(is_array($field)){
            if(empty(array_diff_key($diff_collect_field,$field))){
                $novel_id=Db::name($info['type'])->where('reurl',$url)->value('id');
                $field_data['category']=$field['category'];
                $field_data['title']=$field['title'];
                $field_data['pic']=$field['pic'];
                $field_data['content']=$field['content'];
                $field_data['update_time']=time();
                $field_data['reurl']=$url;
                if($info['type']=='novel'){
                    $field_data['author']=$field['author'];
                    $field_data['serialize']=$field['serialize'];
                    $field_data['tag']=$field['tag'];
                }
                if($novel_id){
                    Db::name($info['type'])->where('id',$novel_id)->update($field_data);
                }else{
                    $field_data['create_time']=time();
                    $novel_id=Db::name($info['type'])->insertGetId($field_data);
                }
                return ["novel_id"=>$novel_id];
            }
            if($info['type']=='novel'){
                if(empty(array_diff_key(["chapter_title"=>"章节名称","chapter_content"=>"章节内容","novel_id"=>"小说ID"],$field))){
                    echo
                    $key=uniqidReal();
                    $data['title']=$field["chapter_title"];
                    $data['intro']='';
                    $data['update_time']=time();
                    $data['issued']=1;
                    $data['word']=mb_strlen($field['chapter_content']);
                    $data['reurl']=$url;
                    $data['auto']=0;
                    $data['path']=$field['novel_id'].DIRECTORY_SEPARATOR.$key.'.txt';
                    $api = new \app\common\model\Api;
                    $api->set_chapter_content($data['path'],$field["chapter_content"]);
                    if($chapter_data=Db::name($info['type'].'_chapter')->where(['novel_id'=>$field['novel_id']])->field('id,chapter')->find()){
                        if(Config::get('web.data_save_compress')){
                            $chapter_data['chapter']=@gzuncompress(base64_decode($chapter_data['chapter']));
                        }
                        $chapter_data['chapter']=json_decode($chapter_data['chapter'],true);
                        $chapter_data['chapter'][$key]=$data;
                        $chapter_data['chapter']=json_encode($chapter_data['chapter']);
                        if(Config::get('web.data_save_compress')){
                            $chapter_data['chapter']=base64_encode(gzcompress($chapter_data['chapter'],Config::get('web.data_save_compress_level')));
                        }
                        Db::name($info['type'].'_chapter')->update($chapter_data);
                    }else{
                        $chapter_data['status']=1;
                        $chapter_data['novel_id']=$field["novel_id"];
                        $chapter_data['chapter']=json_encode([$key=>$data]);
                        if(Config::get('web.data_save_compress')){
                            $chapter_data['chapter']=base64_encode(gzcompress($chapter_data['chapter'],Config::get('web.data_save_compress_level')));
                        }
                        $chapter_id=Db::name($info['type'].'_chapter')->insert($chapter_data);
                    }
                    Db::name($info['type'])->where(['id'=>$field['novel_id']])->update(['update_time'=>time(),'word'=>Db::raw('word+'.$data['word'])]);
                    return ['novel_id'=>$field['novel_id']];
                }
            }else{
                return [];
            }
            return $field;
        }
    }
    //标签过滤
    static public function strip_tags_content($content, $tags) {
        $tags_special = $tags_ordinary = [];
        $tags = explode(',', $tags);
        $tags = array_unique($tags);
        foreach ($tags as $tag) {
            $tag = strtolower($tag);
            if ($tag == 'script' || $tag == 'style' || $tag == 'object') {
                $tags_special[$tag] = $tag;
            } else {
                $tags_ordinary[$tag] = $tag;
            }
        }
        if ($tags_special) {
            $content = preg_replace('/<(' . implode('|', $tags_special) . ')[^<>]*>[\s\S]*?<\/\1>/i', '', $content);
        }
        if ($tags_ordinary) {
            $content = preg_replace('/<[\/]*(' . implode('|', $tags_ordinary) . ')[^<>]*>/i', '', $content);
        }
        return $content;
    }

    //地址补全
    static public function url_complete($html,$base_url){
        $html = preg_replace_callback('/(?<=\bhref\=[\'\"])([^\'\"]*)(?=[\'\"])/i', function ($matche) use($base_url) {
            return self::create_url($matche[1], $base_url);
        }, $html);
        $html = preg_replace_callback('/(?<=\bsrc\=[\'\"])([^\'\"]*)(?=[\'\"])/i', function ($matche) use($base_url) {
            return self::create_url($matche[1], $base_url);
        }, $html);
        return $html;
    }

    /**
     * URL地址补全
     * @param string $url      需要检查的URL
     * @param string $baseurl  基本URL
     */
    static public function create_url($url, $baseurl) {
        $urlinfo = parse_url($baseurl);
        $baseurl = $urlinfo['scheme'].'://'.$urlinfo['host'].(substr($urlinfo['path'], -1, 1) === '/' ? substr($urlinfo['path'], 0, -1) : str_replace('\\', '/', dirname($urlinfo['path']))).'/';
        if (strpos($url, '://') === false) {
            if ($url[0] == '/') {
                $url = $urlinfo['scheme'].'://'.$urlinfo['host'].$url;
            } else {
                $url = $baseurl.$url;
            }
        }
        return $url;
    }

    static public function array_page($array,$limit,$level){
        if(!isset(self::$page)){
            self::$page=json_decode(Request::param('list_page'),true);
        }
        if(empty(self::$page[$level])){
            self::$page[$level]['page']=0;
        }
        $totals=count($array);
        $count=ceil($totals/$limit);
        if($totals>$limit){
            $start=self::$page[$level]['page']*$limit;
            $data=array_slice($array,$start,$limit);
        }else{
            $data=$array;
        }
        self::$page[$level]=['count'=>$count,'page'=>self::$page[$level]['page']+1];
        if((self::$page[$level]['page'])>=$count && $totals>1){
            self::$page=self::array_page_unset(self::$page,$level);
        }
        return ['finish'=>empty(self::$page)?true:false,'data'=>$data,'list_page'=>self::$page];
    }

    static public function array_page_unset($page,$level){
        unset($page[$level]);
        if(!empty($page)){
            $page_end=array_slice($page,-1,1);
            $page_key=key($page_end);
            if($page_end[$page_key]['count']<=($page_end[$page_key]['page']+1)){
                return self::array_page_unset($page_end,$page_key);
            }
        }
        return $page;
    }
 
    static public function down_img($url,$path){
        if(!Validate::checkRule($url,'url')){
            return false;
        }
        $extension = pathinfo($url, PATHINFO_EXTENSION);
        $extension=$extension?".".$extension:".jpg";
        $img_name = md5($url);
        $upload_path=Config::get('web.upload_path');
        $filename =$upload_path.$path.'/'.date('Ymd',time()).'/'.$img_name.$extension;
        $get_file = Http::doGet($url);
        if($get_file){
            if(!in_array(strtolower($extension),array('.jpg','.jpeg','.png','.gif','.bmp','.wepb'))){
                return false;
            }
            if(File::put($filename,$get_file)){
                $date["path"]=substr($filename,1);
                $date["md5"]=md5_file($filename);
                $date["sha1"]=sha1_file($filename);
                return $date;
            }
            return false;
        }
    }

    static public function echo_jump($url,$sec=0){
        echo '<script>setTimeout(function (){location.href="'.$url.'";},'.($sec*1000).');</script><span>暂停'.$sec.'秒后继续  >>>  </span><a href="'.$url.'" >如果您的浏览器没有自动跳转，请点击这里</a><br>';
    }

    static public function echo_msg($str){
        if (!isset(self::$echo_msg_head)) {
            self::$echo_msg_head = true;
            header('X-Accel-Buffering:no');
            @ini_set('output_buffering', 'Off');
            echo '<style type="text/css">body{font:14px Verdana, "Helvetica Neue", helvetica, Arial, "Microsoft YaHei", sans-serif;background:#fff;}p{padding:5px;margin:0;}.layui-badge{position: relative;display: inline-block;padding: 0 6px;font-size: 12px;text-align: center;background-color: #FF5722;color: #fff;border-radius: 2px;line-height: 22px;margin-left: 10px;}.layui-bg-blue{background-color: #1E9FFF!important;}.layui-bg-green {background-color: #009688!important;}</style>';
        }
        echo '<p>'.$str.'</p>';
        echo '<script type="text/javascript">document.getElementsByTagName("body")[0].scrollTop=document.getElementsByTagName("body")[0].scrollHeight;</script>';
        ob_flush();
        flush();
    }
}
?>