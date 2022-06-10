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

class Conf extends Controller
{
    public function index($v = '')
    {
        $cur_version = 4;
        $data['code'] = 0;

        if ($v == $cur_version) {
            //$data['splash_ad_interval'] = 1;//间隔时间
            $data['banner_ad'] = 0;//底部banner
            $data['splash_ad'] = 0;//开屏
            $data['interstitial_ad'] = 0;//插屏
            $data['nativeexpress_ad'] = 0;//原生
            $data['type_ad'] = 3;//1:穿山甲，2:腾讯，3:两种（穿山甲，腾讯）

            //包失效后提示下载其他包
            $data['is_popup'] = 0;
            $data['download_other_app_content'] = '';
            $data['download_other_app_url'] = '';

            return json($data);

        }

        //$data['splash_ad_interval'] = 1;//间隔时间
        $data['banner_ad'] = 1;//底部banner
        $data['splash_ad'] = 1;//开屏
        $data['interstitial_ad'] = 1;//插屏
        $data['nativeexpress_ad'] = 1;//原生
        $data['type_ad'] = 1;//1:穿山甲，2:腾讯，3:两种（穿山甲，腾讯）

        //包失效后提示下载其他包
        $data['is_popup'] = 0;
        $data['download_other_app_content'] = '';
        $data['download_other_app_url'] = '';

        return json($data);
    }
}
