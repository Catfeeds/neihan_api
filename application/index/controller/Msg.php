<?php
namespace app\index\controller;

use think\Controller;
use think\Response;
use think\Request;
use think\Log;

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

        $xml = file_get_contents('php://input');
        Log::record($xml, 'info');

        if (!trim($xml)) {
            return 'success';
        }

        $encrypt_data = xml_to_data($xml);

        $data = array(
            'ToUserName' => $origin_data['FromUserName'],
            'FromUserName' => $origin_data['ToUserName'],
            'CreateTime' => time(),
            'MsgType' => 'text',
            'Content' => 'lalalala'
        );
        return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
    }

}
