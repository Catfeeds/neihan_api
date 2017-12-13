<?php
namespace app\index\controller;

use think\Controller;
use think\Response;
use think\Request;
use think\Log;
use think\Config;

use app\index\model\UserMp;
use app\index\model\User;


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
        $wxmsg_config = $wxmsg_mp['neihan_mp'];
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

        # 找上线
        $parent_user = '';
        if(isset($origin_data['Ticket']) && $origin_data['Ticket'] != '') {
            $parent_user = UserMp::where('qrcode_ticket', $origin_data['Ticket'])->find();
        }

        if(isset($origin_data['Event']) && $origin_data['Event'] == 'subscribe') {
            $token = $this->_access_token('neihan_mp');
            $api = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$token['access_token'].'&openid='.$origin_data['FromUserName'].'&lang=zh_CN';
            $resp = curl_get($api);
            $resp = json_decode($resp, true);


            # 创建用户
            $usermp = UserMp::get(['openid' => $origin_data['FromUserName']]);
            if(empty($usermp)) {
                $usermp = new UserMp;
                $usermp->data([
                    'parent_user_id' => $parent_user ? $parent_user->id : 0,
                    'user_name' => $resp['nickname'],
                    'user_avatar' => $resp['headimgurl'],
                    'gender' => $resp['sex'],
                    "city" =>  $resp['city'],
                    "province" => $resp['province'],
                    "country" => $resp['country'],
                    "unionid" => isset($resp['unionid']) ? $resp['unionid'] : '',
                    'openid'  => $origin_data['FromUserName'],
                    'source' => 'neihan_mp_1',
                    'subscribe' => 1
                ]);
                $usermp->save();    
            } else {
                $usermp->user_name = $resp['nickname'];
                $usermp->user_avatar = $resp['headimgurl'];
                $usermp->gender = $resp['sex'];
                $usermp->city = $resp['city'];
                $usermp->province = $resp['province'];
                $usermp->country = $resp['country'];
                $usermp->unionid = isset($resp['unionid']) ? $resp['unionid'] : '';
                $usermp->subscribe = 1;
                $usermp->save();
            }
        }

        if(isset($origin_data['Event']) && $origin_data['Event'] == 'unsubscribe') {
           UserMp::where('openid', $origin_data['FromUserName'])->update(['subscribe' => 0]);        
        }

        $usermp = UserMp::get(['openid' => $origin_data['FromUserName']]);

        # 各种按钮
        if(isset($origin_data['Event']) && $origin_data['Event'] == 'CLICK' && $origin_data['EventKey'] == 'V1001_APP') {
            $wxconfig = Config::get('wxconfig');
            $api = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=';
            $token = $this->_access_token('neihan_mp');
            $data = [
                'touser' => $origin_data['FromUserName'],
                'msgtype' => 'miniprogrampage',
                'miniprogrampage' => [
                    'title' => '解锁更多精彩福利视频，戳这里！！',
                    'appid' => $wxconfig['appids'][$this->app_code],
                    'pagepath' => 'pages/index/index',
                    'thumb_media_id' => '2GVOdSI8OeOxU9lgcwa_Qt0REBdqJQPMQ01j2c9Q-qg'
                ]
            ];
            $resp = curl_post($api.$token['access_token'], json_encode($data, JSON_UNESCAPED_UNICODE));
            Log::record($resp, 'info');
            return 'success';
        } elseif(isset($origin_data['Event']) && (($origin_data['Event'] == 'CLICK' && $origin_data['EventKey'] == 'V1001_PROMO') || $origin_data['Event'] == 'subscribe' ) ) {
            if($usermp->promotion  == 1) {
                $data = array(
                    'ToUserName' => $origin_data['FromUserName'],
                    'FromUserName' => $origin_data['ToUserName'],
                    'CreateTime' => time(),
                    'MsgType' => 'news',
                    'ArticleCount' => 1,
                    'Articles' => array(
                        array(
                            'Title' => '小程序风口，加入代理，手把手教你躺赚百元【小程序代理商躺盈教程】',
                            'Description' => '解密内涵极品君小程序代理机制轻松赚钱之路',
                            'PicUrl' => 'http://mmbiz.qpic.cn/mmbiz_jpg/4YBian2HRWecFmqmqJ0icOljlO3fXKgq9AiaSfnv23nqlSExuY3BVCYHJDkpNeq1Er0PxUqqcQumssQtVasxmg5ow/0?wx_fmt=jpeg',
                            'Url' => 'http://www.zyo69.cn/pay?user_id='.$usermp->id
                        )
                    )
                ); 
                return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
            } elseif($usermp->promotion == 2) {
                $wxconfig = Config::get('wxconfig');
                $api = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=';
                $token = $this->_access_token('neihan_mp');

                $from_user_id = 0;
                $from_user_app = User::where('user_mp_id', $usermp->parent_user_id)->find();
                if(!empty($from_user_app)) {
                    $from_user_id = $from_user_app->id;
                }

                $data = [
                    'touser' => $origin_data['FromUserName'],
                    'msgtype' => 'miniprogrampage',
                    'miniprogrampage' => [
                        'title' => '点击进入, 分享三个群即可成为代理！',
                        'appid' => $wxconfig['appids'][$this->app_code],
                        'pagepath' => 'pages/distribution/distribution?from_user_id='.$from_user_id.'&user_mp_id='.$usermp->id,
                        'thumb_media_id' => '2GVOdSI8OeOxU9lgcwa_Qt0REBdqJQPMQ01j2c9Q-qg'
                    ]
                ];
                $resp = curl_post($api.$token['access_token'], json_encode($data, JSON_UNESCAPED_UNICODE));
                Log::record($resp, 'info');
                return 'success';
            } elseif($usermp->promotion == 3) {
                $wxconfig = Config::get('wxconfig');
                $api = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=';
                $token = $this->_access_token('neihan_mp');

                $from_user_id = 0;
                $from_user_app = User::where('user_mp_id', $usermp->parent_user_id)->find();
                if(!empty($from_user_app)) {
                    $from_user_id = $from_user_app->id;
                }
        
                $data = [
                    'touser' => $origin_data['FromUserName'],
                    'msgtype' => 'miniprogrampage',
                    'miniprogrampage' => [
                        'title' => '点击进入, 查看你的代理人数！',
                        'appid' => $wxconfig['appids'][$this->app_code],
                        'pagepath' => 'pages/distribution/distribution?from_user_id='.$from_user_id.'&user_mp_id='.$usermp->id,
                        'thumb_media_id' => '2GVOdSI8OeOxU9lgcwa_Qt0REBdqJQPMQ01j2c9Q-qg'
                    ]
                ];
                $resp = curl_post($api.$token['access_token'], json_encode($data, JSON_UNESCAPED_UNICODE));
                Log::record($resp, 'info');
                return 'success';
            }
        } elseif(isset($origin_data['Event']) && $origin_data['Event'] == 'CLICK' && $origin_data['EventKey'] == 'V1001_QRCODE') {
            if(!$usermp->qrcode_media_id) {
                $token = $this->_access_token('neihan_mp');
                $api = 'https://api.weixin.qq.com/cgi-bin/material/add_material?type=image&access_token='.$token['access_token'];
                $post_data = array(
                    "access_token" => $token['access_token'],
                    "type" => "image",
                    "file" => '.'.$usermp->promotion_qrcode,
                );
                Log::record($post_data, 'info');
                $resp = curl_post($api, $post_data);
                Log::record($resp, 'info');
                $resp = json_decode($resp, true);
                $usermp->qrcode_media_id = $resp['media_id'];
                $usermp->save();
            }
            $MediaId = $usermp->qrcode_media_id ? $usermp->qrcode_media_id : '2GVOdSI8OeOxU9lgcwa_Qt0REBdqJQPMQ01j2c9Q-qg'; 

            $data = array(
                'ToUserName' => $origin_data['FromUserName'],
                'FromUserName' => $origin_data['ToUserName'],
                'CreateTime' => time(),
                'MsgType' => 'image',
                'Image' => array('MediaId' => $MediaId)
            );
            Log::record($data, 'info');
            return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
        }
        return 'success';
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