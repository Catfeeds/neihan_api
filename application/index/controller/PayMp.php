<?php
namespace app\index\controller;

use think\Controller;
use think\Response;
use think\Request;
use think\Config;
use think\Log;

use app\index\model\UserMpTicket;
use app\index\model\UserMp;
use app\index\model\User;
use app\index\model\WechatOrder;

use Yansongda\Pay\Pay as MPay;

use Thenbsp\Wechat\Payment\Notify;
use Symfony\Component\HttpFoundation\Request as WRequest;

use GuzzleHttp;


class PayMp extends Controller
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

        if(empty($user_id)) {
            return 'request invalid';
        }

        $this->assign('user_id', $user_id);
        return $this->fetch('pay_mp');

    }

    public function jump()
    {
        $user_id = Request::instance()->get('user_id');
        $amount = floatval(Request::instance()->get('amount'));
        $ip = Request::instance()->ip();

        if(empty($user_id) || empty($amount)) {
            return 'request invalid';
        }

        $usermp = UserMp::get(['id' => $user_id]);
        if(empty($usermp)) {
            return 'request invalid';
        }

        $mpay = new MPay($this->payconfig['mp2']);
        $config_biz = [
            'out_trade_no' => generate_order(),
            'total_fee' => strval(intval($amount*100)),
            'body' => '代理门票',
            'spbill_create_ip' => $ip,
            'openid' => $usermp->openid,
        ];
        $uniorder = $mpay->driver('wechat')->gateway('mp')->pay($config_biz);

        $ticket = New UserMpTicket;
        $ticket->data([
            'appid' => $uniorder['appId'],
            'user_id' => $user_id,
            'orderid' => $config_biz['out_trade_no'],
            'rel_orderid' => '',
            'amount' => $amount,
            'ip' => $ip,
            'status' => 0
        ]);
        $ticket->save();

        $this->assign('uniorder', $uniorder);
        return $this->fetch('jump');
    }

    public function page()
    {
        return $this->fetch('result');;
    }


    public function notify()
    {
        $wrequest = WRequest::createFromGlobals();
        $notify = new Notify($wrequest);

        if(!$notify->containsKey('out_trade_no')) {
            $notify->fail('Invalid Request');
        }

        $verify = $notify->toArray();

        $wechat_order = New WechatOrder;
        $wechat_order->data($verify);
        $wechat_order->save();

        $data = ['return_code' => 'SUCCESS', 'return_msg' => 'OK'];

        Log::record($verify, 'info');
        if ($verify) {
            $wxconfig = $this->payconfig['mp2'];
            $sign = $verify['sign'];
            unset($verify['sign']);

            if($sign != generate_sign($verify, $wxconfig['wechat']['key'])) {
                $data = ['return_code' => 'FAIL', 'return_msg' => '签名失败'];
                return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
            }

            $usorder = UserMpTicket::where('orderid', $verify['out_trade_no'])->find();
            if (empty($usorder) || $usorder->status == 1) {
                return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
            }

            if ($verify['result_code'] !== 'SUCCESS') {
                $usorder->status = 2;
                $usorder->errmsg = $verify['err_code'].'|'.$verify['err_code_res'];

                return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
            }

            if (intval($verify['total_fee']) != intval($usorder->amount*100)) {
                return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
            }

            $usorder->rel_orderid = $verify['transaction_id'];
            $usorder->status = 1;
            $usorder->save();


            $usermp = UserMp::where('id', $usorder['user_id'])->find();
            if($usermp->promotion == 1) {
                $usermp->promotion = 2;
                $usermp->promotion_time = time();
                $usermp->save();


                $from_user_id = '0';
                if($usermp->parent_user_id) {
                   $from_user_app = User::where('user_mp_id', $usermp->parent_user_id)->find();
                    if(!empty($from_user_app)) {
                        $from_user_id = $from_user_app->id;
                    } 
                }
                $from_user_id = $from_user_id.'|'.$usermp->id;

                $api = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=';
                $token = $this->_access_token('neihan_mp');
                $data = [
                    'touser' => $usermp->openid,
                    'msgtype' => 'miniprogrampage',
                    'miniprogrampage' => [
                        'title' => '点击进入, 分享三个群即可成为代理！',
                        'appid' => $this->wxconfig['appids'][$this->app_code],
                        'pagepath' => 'pages/distribution/distribution?from_user_id='.$from_user_id,
                        'thumb_media_id' => '2GVOdSI8OeOxU9lgcwa_Qt0REBdqJQPMQ01j2c9Q-qg'
                    ]
                ];
                $resp = curl_post($api.$token['access_token'], json_encode($data, JSON_UNESCAPED_UNICODE));
                Log::record($resp, 'info');
            }
        } else {
            $data['resturn_msg'] = 'FAIL';
            return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
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


