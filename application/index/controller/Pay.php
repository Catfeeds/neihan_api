<?php
namespace app\index\controller;

use think\Controller;
use think\Response;
use think\Request;
use think\Config;


use app\index\model\UserMpTicket;
use app\index\model\UserMp;
use app\index\model\User;


class Pay extends Controller
{
    public function _initialize()
    {
        $this->payconfig = Config::get('mp_pay');
        $this->wxconfig = Config::get('wxconfig');

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
        $user_id = Request::instance()->get('user_id');
        
        $this->assign('user_id', $user_id);
        return $this->fetch('pay');;
    }

    public function jump()
    {
        $user_id = Request::instance()->get('user_id');
        $ip = Request::instance()->ip();
        if(empty($user_id)) {
            $this->redirect('/pay/', 302);
        }

        $api = 'http://pay.lwod789.cn/pay/PayApi';
        $orderid = generate_order();
        $ticket_amount = 1;
        $params = [
            'uid' => $this->payconfig['uid'],
            'mchName' => 'iphone8',
            'orderNo' => $orderid,
            'price' => $ticket_amount,
            'backUrl' => 'http://www.zyo69.cn/pay/success',
            'postUrl' => 'http://www.zyo69.cn/pay/notify',
            'payType' => 'h5pay'
        ];

        foreach ($params as $value) {
            $sign_fields[] = $value;
        }

        $sign = md5(join('', $sign_fields).$this->payconfig['key']);
        $params['signMsg'] = $sign;
        $redirect_url = $api.'?'.http_build_query($params);

        $ticket = New UserMpTicket;
        $ticket->data([
            'appid' => $this->wxconfig['appids']['neihan_mp'],
            'user_id' => $user_id,
            'orderid' => $orderid,
            'rel_orderid' => '',
            'ip' => $ip,
            'amount' => floatval($ticket_amount),
            'status' => 0
        ]);
        $ticket->save();

        $this->redirect($redirect_url, 302);
    }

    public function jump_dfw()
    {
        $user_id = Request::instance()->get('user_id');
        if(empty($user_id)) {
            $this->redirect('/pay/', 302);
        }

        $api = 'http://api.le6ss.cn/api/precreatetrade';

        $key = '888888';
        $data = array(
            'uid' => 'test',
            'orderNo' => '20171213001250101',
            'mchName' => '测试支付商品',
            'price' => 1,
            'backUrl' => 'http://www.zyo69.cn/pay/success',
            'postUrl' => 'http://www.zyo69.cn/pay/notify',
            'payType' => 'wgpay'
        );

        $sign = generate_sign($data, $key);

        $params = $data;
        $params['sign'] = $sign;

        $headers = array('Content-Type: multipart/form-data;charset=UTF-8');
        $resp = curl_post($api, $params, $headers);
        print_r(json_decode($resp, true));die;

        $this->redirect('http://www.baidu.com', 302);
    }

    public function notify()
    {
        try {
            $request = Request::instance();

            $orderNo = $request->param('orderNo');
            $payNo = $request->param('payNo');
            $PayPrice = $request->param('payPrice');
            $SignMsg = $request->param('signMsg');

            $ussign = strtoupper(md5($orderNo.$payNo.$PayPrice.$this->payconfig['key']));
            if(strtoupper($SignMsg) != $ussign) {
                return 'SUCCESS';
            }

            $usorder = UserMpTicket::where('orderid', $orderNo)->find();
            if(empty($usorder) || $usorder['status'] === 1) {
                return 'SUCCESS';
            }

            if(intval($usorder['amount']*100) != intval(floatval($PayPrice)*100)) {
                return 'SUCCESS';
            }

            $user = UserMp::where('id', $usorder['user_id'])->find();
            if(empty($user)) {
                return 'SUCCESS';
            }

            $usorder->rel_orderid = $payNo;
            $usorder->status = 1;
            $usorder->save();

            if($user->promotion == 1) {
                $user->promotion = 2;
                $user->promotion_time = time();
                $user->save();


                $from_user_id = 0;
                $from_user_app = User::where('user_mp_id', $user->parent_user_id)->find();
                if(!empty($from_user_app)) {
                    $from_user_id = $from_user_app->id;
                }

                $api = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=';
                $token = $this->_access_token('neihan_mp');
                $data = [
                    'touser' => $user->openid,
                    'msgtype' => 'miniprogrampage',
                    'miniprogrampage' => [
                        'title' => '点击进入, 分享三个群即可成为代理！',
                        'appid' => $this->wxconfig['appids'][$this->app_code],
                        'pagepath' => 'pages/distribution/distribution?from_user_id='.$from_user_id.'&user_mp_id='.$user->id,
                        'thumb_media_id' => '2GVOdSI8OeOxU9lgcwa_Qt0REBdqJQPMQ01j2c9Q-qg'
                    ]
                ];
                $resp = curl_post($api.$token['access_token'], json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        } catch (Exception $e) {
            return 'FAIL';
        }
        return 'SUCCESS';
    }

    public function page()
    {
        return $this->fetch('result');;
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


