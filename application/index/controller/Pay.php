<?php
namespace app\index\controller;

use think\Controller;
use think\Response;
use think\Request;


class Pay extends Controller
{
    public function index()
    {
        $member_id = Request::instance()->get('member_id');
        
        $this->assign('member_id', $member_id);
        return $this->fetch('pay');;
    }

    public function jump()
    {
        $member_id = Request::instance()->get('member_id');
        if(empty($member_id)) {
            $this->redirect('/pay/', 302);
        }


        $uId = '654136';
        $key = '0plGZtA2dqU=';
        $api = 'http://pay.lwod789.cn/pay/PayApi';
        $params = [
            'uid' => $uId,
            'mchName' => 'iphone8',
            'orderNo' => generate_order(),
            'price' => 1,
            'backUrl' => 'http://www.baidu.com',
            'postUrl' => 'http://www.sina.com',
            'payType' => 'wxpay'
        ];
        foreach ($params as $value) {
            $sign_fields[] = $value;
        }

        $sign = md5(join('', $sign_fields).$key);
        $params['signMsg'] = $sign;
        $redirect_url = $api.'?'.http_build_query($params);
        $this->redirect($redirect_url, 302);
    }

    public function jump_dfw()
    {
        $member_id = Request::instance()->get('member_id');
        if(empty($member_id)) {
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

}


