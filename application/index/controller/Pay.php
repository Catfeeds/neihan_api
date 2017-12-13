<?php
namespace app\index\controller;

use think\Controller;
use think\Response;
use think\Request;
use think\Config;

use app\index\model\UserPromotionTicket;
use app\index\model\UserMp;


class Pay extends Controller
{
    public function _initialize()
    {
        $this->payconfig = Config::get('mp_pay');
        $this->wxconfig = Config::get('wxconfig');
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
            'payType' => 'wxpay'
        ];

        foreach ($params as $value) {
            $sign_fields[] = $value;
        }

        $sign = md5(join('', $sign_fields).$this->payconfig['key']);
        $params['signMsg'] = $sign;
        $redirect_url = $api.'?'.http_build_query($params);

        $ticket = New UserPromotionTicket;
        $ticket->data([
            'appid' => $this->wxconfig['appids']['neihan_mp'],
            'user_id' => $user_id,
            'orderid' => $orderid,
            'rel_orderid' => '',
            'nonce_str' => '',
            'prepay_id' => '',
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

            $usorder = UserPromotionTicket::where('orderid', $orderNo)->find();
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

            $usorder->status = 1;
            $usorder->save();

            if($user->promotion == 0) {
                $user->promotion = 1;
                $user->promotion_time = time();
                $user->save();
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

}


