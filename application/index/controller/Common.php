<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Response;
use think\Loader;
use think\Db;
use think\Config;

use app\index\model\Setting;

class Common extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $comconfig = Config::get('comconfig');
        $request = Request::instance();
        $data = ['c' => 0, 'm'=> '', 'd' => []];

        $setting_id = 1;
        foreach ($comconfig['domain_settings'] as $key => $value) {
            if(strrpos($request->domain(), $key) !== false) {
                $setting_id = $value;
                break;
            }
        }

        $settings = Setting::get($setting_id);
        $data['d']['online'] = intval($settings['online']);

        return Response::create($data, 'json')->code(200);
    }

}
