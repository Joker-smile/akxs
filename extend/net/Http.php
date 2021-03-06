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
//数据采集，doGET,doPOST,文件下载，
class Http
{
	static public $way=1;
	//手动设置访问方式
	static public function setWay($way)
	{
		self::$way=intval($way);
	}
	static public function getSupport()
	{	
		//如果指定访问方式，则按指定的方式去访问
		if(isset(self::$way)&&in_array(self::$way,array(1,2,3)))
			return self::$way;
			
		//自动获取最佳访问方式	
		if(function_exists('curl_init'))//curl方式
		{
			return 1;
		}
		else if(function_exists('fsockopen'))//socket
		{
			return 2;
		}
		else if(function_exists('file_get_contents'))//php系统函数file_get_contents
		{
			return 3;
		}
		else
		{
			return 0;
		}	
	}
		//通过get方式获取数据
	static public function doGet($url,$timeout=60,$header="")
	{	
		if(empty($url)||empty($timeout))
			return false;
		if(!preg_match('/^(http|https)/is',$url))
			$url="http://".$url;
		$code=self::getSupport();
		switch($code)
		{
			case 1:return self::curlGet($url,$timeout,$header);break;
			case 2:return self::socketGet($url,$timeout,$header);break;
			case 3:return self::phpGet($url,$timeout,$header);break;
			default:return false;	
		}
	}
	//通过POST方式发送数据
	static public function doPost($url, $post_data=array(), $timeout=60,$header="") 
	{
		if(empty($url)||empty($post_data)||empty($timeout))
			return false;
		if(!preg_match('/^(http|https)/is',$url))
			$url="http://".$url;
		$code=self::getSupport();
		switch($code)
		{
			case 1:return self::curlPost($url,$post_data,$timeout,$header);break;
			case 2:return self::socketPost($url,$post_data,$timeout,$header);break;
			case 3:return self::phpPost($url,$post_data,$timeout,$header);break;
			default:return false;	
		}
	}	
	//通过curl get数据
	static public function curlGet($url,$timeout=60,$header="")
	{
		$header=empty($header)?"":$header;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));//模拟的header头
		if(self::hasHttps($url)){
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}
        curl_setopt($ch, CURLOPT_ENCODING ,'gzip'); //加入gzip解析
        $result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
	
	//通过curl post数据
	static public function curlPost($url, $post_data=array(), $timeout=60,$header="") 
	{
		$header=empty($header)?"":$header;
		$post_string = http_build_query($post_data);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));//模拟的header头
		if(self::hasHttps($url)){
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
	//通过socket get数据
	static public function socketGet($url,$timeout=60,$header="")
	{
		$header=empty($header)?self::defaultHeader():$header;
		$url2 = parse_url($url);
		$url2["path"] = isset($url2["path"])? $url2["path"]: "/" ;
		$url2["query"] = isset($url2["query"])? "?".$url2["query"] : "";
		$host_ip = @gethostbyname($url2["host"]);
		if(self::hasHttps($url)){
			$host_ip = 'ssl://'.$url2['host'];
			$url2["port"] = isset($url2["port"])? $url2["port"] : 443;
		}else{
			$url2["port"] = isset($url2["port"])? $url2["port"] : 80;
		}
		if(($fsock = fsockopen($host_ip, $url2['port'], $errno, $errstr, $timeout)) < 0){
			return false;
		}
		$request =  $url2["path"] .$url2["query"];
		$in  = "GET " . $request . " HTTP/1.0\r\n";
		if(false===strpos($header, "Host:")){	
			 $in .= "Host: " . $url2["host"] . "\r\n";
		}
		$in .= $header;
		$in .= "Connection: Close\r\n\r\n";
		
		if(!@fwrite($fsock, $in, strlen($in))){
			@fclose($fsock);
			return false;
		}
		return self::GetHttpContent($fsock);
	}
	//通过socket post数据
	static public function socketPost($url, $post_data=array(), $timeout=60,$header="")
	{
		$header=empty($header)?self::defaultHeader():$header;
		$post_string = http_build_query($post_data);  
		$url2 = parse_url($url);
		$url2["path"] = isset($url2["path"])? $url2["path"]: "/" ;
		$host_ip = @gethostbyname($url2["host"]);
		if(self::hasHttps($url)){
			$host_ip = 'ssl://'.$url2['host'];
			$url2["port"] = isset($url2["port"])? $url2["port"] : 443;
		}else{
			$url2["port"] = isset($url2["port"])? $url2["port"] : 80;
		}
		if(($fsock = fsockopen($host_ip, $url2['port'], $errno, $errstr, $timeout)) < 0){
			return false;
		}
		$request =  $url2["path"].($url2["query"] ? "?" . $url2["query"] : "");
		$in  = "POST " . $request . " HTTP/1.0\r\n";
		$in .= "Host: " . $url2["host"] . "\r\n";
		$in .= $header;
		$in .= "Content-type: application/x-www-form-urlencoded\r\n";
		$in .= "Content-Length: " . strlen($post_string) . "\r\n";
		$in .= "Connection: Close\r\n\r\n";
		$in .= $post_string . "\r\n\r\n";
		unset($post_string);
		if(!@fwrite($fsock, $in, strlen($in))){
			@fclose($fsock);
			return false;
		}
		return self::GetHttpContent($fsock);
	}

	//通过file_get_contents函数get数据
	static public function phpGet($url,$timeout=10,$header="") 
	{
		$header=empty($header)?self::defaultHeader():$header;
		$opts = array( 
				'http'=>array(
							'protocol_version'=>'1.0', //http协议版本(若不指定php5.2系默认为http1.0)
							'method'=>"GET",//获取方式
							'timeout' => $timeout ,//超时时间
							'header'=> $header)
				  ); 
		$context = stream_context_create($opts);    
		return  @file_get_contents($url,false,$context);
	}
	//通过file_get_contents 函数post数据
	static public function phpPost($url, $post_data=array(), $timeout=10,$header="") 
	{
		$header=empty($header)?self::defaultHeader():$header;
		$post_string = http_build_query($post_data);  
		$header.="Content-length: ".strlen($post_string);
		$opts = array( 
				'http'=>array(
							'protocol_version'=>'1.0',//http协议版本(若不指定php5.2系默认为http1.0)
							'method'=>"POST",//获取方式
							'timeout' => $timeout ,//超时时间 
							'header'=> $header,  
							'content'=> $post_string)
				  ); 
		$context = stream_context_create($opts);    
		return  @file_get_contents($url,false,$context);
	}
	
	//默认模拟的header头
	static private function defaultHeader()
	{
		$header="User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2767.5 Safari/537.36\r\n";
		$header.="Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8\r\n";
		$header.="Accept-Language:zh-CN,zh;q=0.8\r\n";
		$header.="Accept-Charset: GB2312,utf-8;q=0.7,*;q=0.7\r\n";
		return $header;
	}

	//获取通过socket方式get和post页面的返回数据
	static private function GetHttpContent($fsock=null)
	{
		$out = null;
		while($buff = @fgets($fsock, 2048)){
			 $out .= $buff;
		}
		fclose($fsock);
		$pos = strpos($out, "\r\n\r\n");
		$head = substr($out, 0, $pos);    //http head
		$status = substr($head, 0, strpos($head, "\r\n"));    //http status line
		$body = substr($out, $pos + 4, strlen($out) - ($pos + 4));//page body
		if(preg_match("/^HTTP\/\d\.\d\s([\d]+)\s.*$/", $status, $matches))
		{
			if(intval($matches[1]) / 100 == 2)
			{
				return $body;  
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	static private function hasHttps($url){
		$matches = parse_url($url);
		if($matches['scheme'] == 'https'){
			return true;
		}else{
			return false;
		}
	}
}
?>