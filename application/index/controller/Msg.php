<?php
namespace app\index\controller;

use think\Controller;
use think\Response;
use think\Request;
use think\Log;
use think\Config;


class Msg extends Controller
{
    public function _initialize()
    {
        $request = Request::instance();
        $comconfig = Config::get('comconfig');

        $this->app_code = 'neihan_1';
        foreach ($comconfig['domain_settings'] as $key => $value) {
            if(strrpos($request->domain(), $key) !== false) {
                $this->app_code = $value;
                break;
            }
        }
    }

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
        # Log::record($xml, 'info');

        if (!trim($xml)) {
            return 'success';
        }

        $origin_data = xml_to_data($xml);

        if(isset($origin_data['MsgType']) && $origin_data['MsgType'] == 'event') {
            if($origin_data['Event'] == 'user_enter_tempsession') {
                $api = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=';
                $token = $this->_access_token();
                if(!empty($token)) {
                    $data = [
                        'touser' => $origin_data['FromUserName'],
                        'msgtype' => 'link',
                        'link' => [
                            'title' => '点此进入',
                            'description' => '解锁更多精彩福利视频，戳这里！！',
                            'url' => 'https://mp.weixin.qq.com/s/QNU9fyuWzV96fwDy29pslw',
                            'thumb_url' => 'http://www.jialejiabianli.cn/static/image/msg_logo.png'
                        ]
                    ];
                    $resp = curl_post($api.$token['access_token'], json_encode($data, JSON_UNESCAPED_UNICODE));
                    # Log::record(json_decode($resp, true), 'info');
                }
            }
        }
        return 'success';
    }

    public function mp()
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
        # Log::record($xml, 'info');

        if (!trim($xml)) {
            return 'success';
        }

        $encrypt_data = xml_to_data($xml);

        $wxmsg_mp = Config::get('wxmsg_mp');
        $wxmsg_config = $wxmsg_mp[$this->app_code];
        $wxmsg = new \WxMsg\WXBizMsgCrypt($wxmsg_config['token'], $wxmsg_config['aes_key'], $wxmsg_config['appid']);
        $format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
        $from_xml = sprintf($format, $encrypt_data['Encrypt']);

        $decrypt_xml = '';
        $errcode = $wxmsg->decryptMsg($msg_sign, $timestamp, $nonce, $from_xml, $decrypt_xml);
        if ($errcode == 0) {
            $origin_data = xml_to_data($decrypt_xml);
        } else {
            $origin_data = [];
            return 'success';
        }

        Log::record($origin_data, 'info');

        if(isset($origin_data['Event']) && $origin_data['Event'] == 'CLICK' && $origin_data['EventKey'] == 'V1001_APP') {
            $wxconfig = Config::get('wxconfig');
            $api = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=';
            $token = $this->_access_token('neihan_mp');
            $data = [
                'touser' => $origin_data['FromUserName'],
                'msgtype' => 'miniprogrampage',
                'miniprogrampage' => [
                    'title' => '解锁更多精彩福利视频，戳这里！！',
                    'appid' => $wxconfig['appids']['neihan_1'],
                    'pagepath' => 'pages/index/index',
                    'thumb_media_id' => '2GVOdSI8OeOxU9lgcwa_Qt0REBdqJQPMQ01j2c9Q-qg'
                ]
            ];
            $resp = curl_post($api.$token['access_token'], json_encode($data, JSON_UNESCAPED_UNICODE));
            Log::record($resp, 'info');
            return 'success';
        } else {
           $data = array(
                'ToUserName' => $origin_data['FromUserName'],
                'FromUserName' => $origin_data['ToUserName'],
                'CreateTime' => time(),
                'MsgType' => 'news',
                'ArticleCount' => 1,
                'Articles' => array(
                    array(
                        'Title' => '支付一元美女带回家',
                        'Description' => '支付一元美女带回家',
                        'PicUrl' => 'http://mmbiz.qpic.cn/mmbiz_jpg/4YBian2HRWecFmqmqJ0icOljlO3fXKgq9AiaSfnv23nqlSExuY3BVCYHJDkpNeq1Er0PxUqqcQumssQtVasxmg5ow/0?wx_fmt=jpeg',
                        # 'PicUrl' => 'http://www.zyo69.cn/static/image/reply.jpeg',
                        'Url' => 'http://www.zyo69.cn/pay?member_id=1'
                    )
                )
            ); 
        }
        return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
    }

    private function _access_token($app_code='')
    {
        try {
            $is_expired = true;

            $access_token = [];
            if(empty($app_code)) {
                $app_code = $this->app_code;
            }
            $access_token_file = './../application/extra/access_token_'.$app_code.'.txt';
            
            if(file_exists($access_token_file)) {
                $access_token = json_decode(file_get_contents($access_token_file), true);
            }
            if(!empty($access_token)) {
                if($access_token['expires_time'] - time() - 1000 > 0) {
                    $is_expired = false;
                }
            }

            if($is_expired) {
                $wxconfig = Config::get('wxconfig');
                $resp = curl_get($wxconfig['token_apis'][$app_code]);
                if(!empty($resp)) {
                    $access_token = json_decode($resp, true);
                    if(array_key_exists('expires_in', $access_token)) {
                        $access_token['expires_time'] = intval($access_token['expires_in']) + time();
                        file_put_contents($access_token_file, json_encode($access_token));
                    }
                }
            }
        } catch (Exception $e) {
            $access_token = [];
        }
        return $access_token;
    }
}