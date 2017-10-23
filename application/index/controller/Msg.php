<?php
namespace app\index\controller;

use think\Controller;
use think\Response;

class Msg extends Controller
{
    public function index()
    {
        $sign = Request::instance()->get('signature');
        $msg_sign = Request::instance()->get('msg_signature');
        $timestamp = Request::instance()->get('timestamp');
        $nonce = Request::instance()->get('nonce');
        $echostr = Request::instance()->get('echostr');
        if (!empty($echostr)) {
            return $echostr;
        }

        return 'success';
    }

}
