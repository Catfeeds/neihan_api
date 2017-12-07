<?php
namespace app\index\controller;

use think\Controller;
use think\Response;
use think\Request;
use think\Log;

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
                            'description' => '关注【极品内涵君】福利天天有',
                            'url' => 'http://mp.weixin.qq.com/s/skDfB-NQ34ylZ7aRaZVZmQ',
                            'thumb_url' => ''
                        ]
                    ];
                    $resp = curl_post($api.$token['access_token'], $data);
                    Log::record($resp, 'info');
                }
            }
        }
        return 'success';
    }

    private function _access_token()
    {
        try {
            $is_expired = true;

            $access_token = [];
            $access_token_file = './../application/extra/access_token_'.$this->app_code.'.txt';
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
                $resp = curl_get($wxconfig['token_apis'][$this->app_code]);
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