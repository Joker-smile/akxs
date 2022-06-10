<?php
namespace app\common\addons;
use app\common\addons\Addon;
use think\Db;
use think\View;
use think\Request;

class HomeAddon extends Addon{

	public $request;

	public function __construct(){
		parent::__construct();
        $this->request=Request::instance();
    }

    public function install(){
        return true;
    }

    public function uninstall(){
        return true;
    }
}