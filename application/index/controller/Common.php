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
        $data = ['c' => 0, 'm'=> '', 'd' => []];
        $settings = Setting::get(1);
        $data['d']['online'] = intval($settings['online']);

        return Response::create($data, 'json')->code(200);
    }

}
