<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Response;
use think\Loader;
use think\Db;
use think\Config;
use think\Log;

use app\index\model\User as User_Model;
use app\index\model\UserShare;
use app\index\model\UserShareClick;
use app\index\model\UserFission;
use app\index\model\UserFormId;
use app\index\model\MsgSendRecord;
use app\index\model\Message;
use app\index\model\MessageTask;
use app\index\model\MessageSetting;
use app\index\model\UserPromotion;
use app\index\model\UserPromotionBalance;
use app\index\model\UserPromotionGrid;
use app\index\model\UserPromotionTicket;
use app\index\model\SettingPromotion;
use app\index\model\WechatOrder;
use app\index\model\UserWithdraw;

use Thenbsp\Wechat\Payment\Unifiedorder;
use Thenbsp\Wechat\Payment\Notify;
use Symfony\Component\HttpFoundation\Request as WRequest;

use EasyWeChat\Foundation\Application;

class User extends Controller
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
        $data = ['c' => 0, 'm'=> '', 'd' => []];
        return Response::create($data, 'json')->code(200);
    }

    public function init()
    {
        try {
            $data = ['c' => 0, 'm'=> '', 'd' => []];

            $js_code = Request::instance()->post('code');

            if(empty($js_code)) {
                $data['c'] = -1024;
                $data['m'] = 'Arg Missing';
                return Response::create($data, 'json')->code(200);
            }

            if($this->app_code == 'neihan_1') {
                $wxconfig = Config::get('wxconfig');
            } elseif($this->app_code == 'neihan_2') {
                $wxconfig = Config::get('wxconfig2');
            }
            
            $request_url = $wxconfig['login_api'].'&js_code='.$js_code;
            $resp = curl_get($request_url);
            if(empty($resp)) {
                $data['c'] = -1024;
                $data['m'] = 'WeiXin Grant Error';
                return Response::create($data, 'json')->code(200);   
            }
            $ret = json_decode($resp, true);
            if(array_key_exists('errcode', $ret)) {
                $data['c'] = -1024;
                $data['m'] = $ret['errmsg'];
                return Response::create($data, 'json')->code(200);   
            }

            $user = User_Model::get(['openid' => $ret['openid']]);
            if(empty($user)) {
                $user = new User_Model;
                $user->data([
                    'openid'  => $ret['openid'],
                    'unionid' => '',
                    'source' => $this->app_code,
                    'session_key' => $ret['session_key']
                ]);
                $user->save();    
            } else {
                $user->session_key = $ret['session_key'];
                $user->save();
            }
            $data['d'] = ['user_id' => $user->id, 'openid' => $user->openid, 'session_key' => $ret['session_key']];
        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }
        
        return Response::create($data, 'json')->code(200);
    }

    public function update()
    {
        try {
            $data = ['c' => 0, 'm'=> '', 'd' => []];

            $user_id = Request::instance()->post('user_id');
            $user_name = Request::instance()->post('user_name');
            $user_avatar = Request::instance()->post('user_avatar');
            $gender = Request::instance()->post('gender');
            $country = Request::instance()->post('country');
            $province = Request::instance()->post('province');
            $city = Request::instance()->post('city');

            if(empty($user_id)) {
                $data['c'] = -1024;
                $data['m'] = 'Arg Missing';
                return Response::create($data, 'json')->code(200);
            }

            $user = User_Model::get($user_id);
            if(empty($user)) {
                $data['c'] = -1024;
                $data['m'] = 'User Not Exists';
                return Response::create($data, 'json')->code(200);   
            }

            $user->user_name = $user_name ? $user_name : '';
            $user->user_avatar = $user_avatar ? $user_avatar : '';
            $user->gender = $gender ? $gender : 0;
            $user->country = $country ? $country : '';
            $user->province = $province ? $province : '';
            $user->city = $city ? $city : '';
            $user->save();

        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }
        
        return Response::create($data, 'json')->code(200);
    }

    public function info()
    {
        try {
            $data = ['c' => 0, 'm'=> '', 'd' => []];

            $request = Request::instance();
            $user_id = $request->get('user_id');

            if(empty($user_id)) {
                $data['c'] = -1024;
                $data['m'] = 'Arg Missing';
                return Response::create($data, 'json')->code(200);
            }

            $user = User_Model::get($user_id);
            if(empty($user)) {
                $data['c'] = -1024;
                $data['m'] = 'User Not Exists';
                return Response::create($data, 'json')->code(200);   
            }
            $ptype = 0;
            if($user->promotion == 3) {
                $user_promo = UserPromotion::where('user_id', $user_id)->find();
                if($user_promo) {
                    $ptype = $user_promo->type;    
                }
            }

            $data['d'] = [
                'user_id' => $user->id, 
                'user_name' => $user->user_name, 
                'user_avatar' => $user->user_avatar,
                'gender' => $user->gender,
                'country' => $user->country,
                'province' => $user->province,
                'city' => $user->city,
                'ptype' => $ptype,
                'qrcode' => '',
            ];
            if(!empty($user->promotion_qrcode)) {
                $data['d']['qrcode'] = $request->domain().$user->promotion_qrcode;
            }

        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }
        
        return Response::create($data, 'json')->code(200);
    }

    public function click_share_link()
    {
        try {
            $from_user_id = Request::instance()->param('from_user_id');
            $user_id = Request::instance()->param('user_id');
            $video_id = Request::instance()->param('video_id');
            $wechat_gid = Request::instance()->param('gid');

            $data = ['c' => 0, 'm'=> '', 'd' => []];

            if(empty($from_user_id) || empty($user_id) || empty($video_id)) {
                $data['c'] = -1024;
                $data['m'] = 'Arg Missing';
                return Response::create($data, 'json')->code(200);
            }

            if($from_user_id != $user_id) {
                $share_click = UserShareClick::get([
                    'from_user_id' => $from_user_id,
                    'user_id' => $user_id,
                    'video_id' => $video_id,
                    'wechat_gid' => strval($wechat_gid)
                ]);

                if(!$share_click) {
                    $share_click = new UserShareClick;
                    $share_click->data([
                        'from_user_id'  => $from_user_id,
                        'user_id' => $user_id,
                        'video_id' => $video_id,
                        'wechat_gid' => strval($wechat_gid)
                    ]);
                    $share_click->save();

                    try {
                        $msg_send = Message::get([
                            'from_user_id' => $from_user_id,
                            'group_id' => $video_id,
                            'is_send' => 1,
                            'app' => $this->app_code
                        ]);
                        if($msg_send) {
                            $msg_send->setInc('active_member');
                        }
                    } catch (Exception $e) {
                        
                    }                    
                }

                # 记录用户裂变数据
                $share_fission = UserFission::get(['user_id' => $user_id]);

                if(!$share_fission) {
                    $uinfo = User_Model::get($from_user_id);
                    $parent_user_id = $uinfo['parent_user_id'] ? $uinfo['parent_user_id'] : $from_user_id;

                    $share_fission = new UserFission;
                    $share_fission->data([
                        'parent_user_id' => $parent_user_id,
                        'from_user_id'  => $from_user_id,
                        'user_id' => $user_id,
                        'video_id' => $video_id
                    ]);
                    $share_fission->save();

                    $user = User_Model::get($user_id);
                    $user->parent_user_id = $parent_user_id;
                    $user->save();
                }
            }

            
        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }

        return Response::create($data, 'json')->code(200);
    }

    public function formid()
    {
        try {
            $user_id = Request::instance()->post('user_id');
            $form_id = Request::instance()->post('form_id');

            $data = ['c' => 0, 'm'=> '', 'd' => []];

            if(empty($user_id) || empty($form_id)) {
                $data['c'] = -1024;
                $data['m'] = 'Arg Missing';
                return Response::create($data, 'json')->code(200);
            }

            User_Model::where('id', $user_id)->update(['is_active' => 1]);

            $user_formid = UserFormId::get([
                'user_id' => $user_id,
                'form_id' => $form_id
            ]);
            if(!$user_formid) {
                $user_formid = new UserFormId;
                $user_formid->data([
                    'user_id'  => $user_id,
                    'form_id' => $form_id
                ]);
                $user_formid->save();
            }

            # 黏性用户
            try {
                $settings = MessageSetting::get(1);
                if($settings->status == 1) {
                    $today_t = strtotime(date('Y-m-d',time()));
                    $formids = UserFormId::where('user_id', $user_id)
                        ->where('create_time', '>=', $today_t)
                        ->where('create_time', '<=', $today_t+86399)
                        ->count();
                    if($formids >= 3) {
                        $exists = MessageTask::where('user_id', $user_id)
                            ->where('date', date('Y-m-d',time()))
                            ->count();
                        if(!$exists) {
                            $msgtask = New MessageTask;
                            $msgtask->data([
                                'user_id' => $user_id,
                                'date' => date('Y-m-d',time()),
                                'is_sended' => 0,
                                'send_time' => date('Y-m-d H:i:s', strtotime("+{$settings->interval} minutes"))
                            ]);
                            $msgtask->save();
                        }
                    }
                }
            } catch (Exception $e) {
                
            }

        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }

        return Response::create($data, 'json')->code(200);
    }

    public function share_group()
    {
        try {
            $user_id = Request::instance()->post('user_id');
            $video_id = Request::instance()->post('video_id');
            $group_name = Request::instance()->post('group_name');
            $group_id = Request::instance()->post('group_id');
            $encrypt_data = Request::instance()->post('encrypt_data');

            $data = ['c' => 0, 'm'=> '', 'd' => []];

            if(empty($user_id) || empty($video_id) || empty($group_name) || empty($encrypt_data)) {
                $data['c'] = -1024;
                $data['m'] = 'Arg Missing';
                return Response::create($data, 'json')->code(200);
            }

            $user = User_Model::get($user_id);
            
            
        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }
        return Response::create($data, 'json')->code(200);
    }

    public function promotion()
    {
        try {
            $user_id = Request::instance()->get('user_id');
            $data = ['c' => 0, 'm'=> '', 'd' => []];

            if(empty($user_id)) {
                $data['c'] = -1024;
                $data['m'] = 'Arg Missing';
                return Response::create($data, 'json')->code(200);
            }

            $user = User_Model::get($user_id);
            $promo = UserPromotionBalance::where('user_id', $user_id)->find();
            if(!empty($promo)) {
                $data['d'] = [
                    'commission' => $promo->commission,
                    'commission_avail' => $promo->commission_avail,
                    'agent_lv1' => UserPromotionGrid::where('parent_user_id', $user_id)->where('level', 1)->count(),
                    'agent_lv2' => UserPromotionGrid::where('parent_user_id', $user_id)->where('level', 2)->count(),
                    'agent_lv3' => UserPromotionGrid::where('parent_user_id', $user_id)->where('level', 3)->count(),
                    'groups' => UserShare::where('user_id', $user_id)->where('create_time', '>=', $user->promotion_time)->where('wechat_gid', '<>', '')->count('distinct wechat_gid')
                ];
            } else {
                $data['d'] = [
                    'commission' => 0,
                    'commission_avail' => 0,
                    'agent_lv1' => 0,
                    'agent_lv2' => 0,
                    'agent_lv3' => 0,
                    'groups' => 0
                ];
            }
        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }
        return Response::create($data, 'json')->code(200);
    }

    public function promotion_init()
    {
        try {
            $user_id = Request::instance()->param('user_id');
            $from_user_id = Request::instance()->param('from_user_id');

            $data = ['c' => 0, 'm'=> '', 'd' => []];

            if(empty($user_id)) {
                $data['c'] = -1024;
                $data['m'] = 'Arg Missing';
                return Response::create($data, 'json')->code(200);
            }
            
            $user = User_Model::get($user_id);
            if(empty($user)) {
                $data['c'] = -1024;
                $data['m'] = 'User NotExists';
                return Response::create($data, 'json')->code(200);
            }

            if(!empty($from_user_id)) {
                $parent_user = User_Model::get($from_user_id);
                if($parent_user->promotion != 3) {
                    $data['c'] = -1024;
                    $data['m'] = 'From User Is Not Valid';
                    return Response::create($data, 'json')->code(200);
                }
            }


            if($user->promotion == 0) {
                $user->promotion = 1;
                $user->promotion_time = time();
                $user->save();

                $user_promo = UserPromotion::where('parent_user_id', $from_user_id)
                    ->where('user_id', $user_id)->count();
                if(empty($user_promo)) {
                    $user_promo = New UserPromotion;
                    $user_promo->data([
                        'parent_user_id' => $from_user_id,
                        'user_id' => $user_id,
                        'status' => 0,
                        'type' => 0
                    ]);
                    $user_promo->save();
                }

                $user_balance = UserPromotionBalance::where('user_id', $user_id)->count();
                if(empty($user_balance)) {
                    $user_balance = New UserPromotionBalance;
                    $user_balance->data([
                        'user_id' => $user_id,
                        'commission' => 0,
                        'commission_avail' => 0
                    ]);
                    $user_balance->save();
                }


            }
        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }
        return Response::create($data, 'json')->code(200);
    }

    public function promotion_prepay()
    {
        try {
            $request = Request::instance();
            $wxconfig = Config::get('wxconfig');
            $user_id = $request->param('user_id');
            $ptype = intval(Request::instance()->param('ptype'));

            $data = ['c' => 0, 'm'=> '', 'd' => []];

            if(empty($user_id)) {
                $data['c'] = -1024;
                $data['m'] = 'Arg Missing';
                return Response::create($data, 'json')->code(200);
            }
            
            $user = User_Model::get($user_id);
            if(empty($user)) {
                $data['c'] = -1024;
                $data['m'] = 'User NotExists';
                return Response::create($data, 'json')->code(200);
            }

            UserPromotion::where('user_id', $user_id)->update(['type' => $ptype ]);

            $ticket = New UserPromotionTicket;
            $psetting = SettingPromotion::get(1);
            
            list($usec, $sec) = explode(" ", microtime());  
            $msec = strval(round($usec*1000)); 
            $orderid = date('YmdHis').$msec;

            $ticket_amount = 100;
            if($ptype == 1) {
                $ticket_amount = floatval($psetting->ticket);
            } elseif($ptype == 2) {
                $ticket_amount = floatval($psetting->golden_ticket);
            }
            $ticket->data([
                'appid' => $wxconfig['appids'][$this->app_code],
                'user_id' => $user_id,
                'orderid' => $orderid,
                'rel_orderid' => '',
                'nonce_str' => '',
                'prepay_id' => '',
                'amount' => floatval($ticket_amount),
                'status' => 0
            ]);
            $ticket->save();

            # 发给统一下单请求
            $unifiedorder = new Unifiedorder(
                $wxconfig['appids'][$this->app_code],
                $wxconfig['mchids'][$this->app_code],
                $wxconfig['mchkeys'][$this->app_code]
            );

            // 必填
            $unifiedorder->set('body',          '代理门票');
            $unifiedorder->set('total_fee',     intval($ticket->amount*100));
            $unifiedorder->set('openid',        $user->openid);
            $unifiedorder->set('trade_type',    'JSAPI');
            $unifiedorder->set('out_trade_no',  $orderid);
            $unifiedorder->set('notify_url',    $request->domain().'/api/user/pay/callback');

            try {
                $response = $unifiedorder->getResponse();
            } catch (\Exception $e) {
                exit($e->getMessage());
            }

            $pay_ret = $response->toArray();
            if($pay_ret['return_code'] === 'SUCCESS') {
                if($pay_ret['result_code'] === 'SUCCESS') {
                    $ticket->prepay_id = $pay_ret['prepay_id'];
                }

                $sign_data = [
                    'appId' => $ticket->appid,
                    'timeStamp' => strtotime($ticket->create_time),
                    'nonceStr' => generate_str(),
                    'package' => 'prepay_id='.$pay_ret['prepay_id'],
                    'signType' => 'MD5'
                ];

                $ticket->nonce_str = $sign_data['nonceStr'];
                $ticket->pay_sign = generate_sign($sign_data, $wxconfig['mchkeys'][$this->app_code]);
                $ticket->save();

                $data['d'] = [
                    'timeStamp' => strtotime($ticket->create_time),
                    'nonceStr' => $ticket->nonce_str,
                    'package' => 'prepay_id='.$ticket->prepay_id,
                    'signType' => 'MD5',
                    'paySign' => $ticket->pay_sign
                ];
            } else {
                $ticket->status = 2;
                $ticket->save();
            }
        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }
        return Response::create($data, 'json')->code(200);
    }

    public function promotion_qrcode()
    {
        try {    
            $data = ['c' => 0, 'm'=> '', 'd' => []];

            $user_id = Request::instance()->param('user_id');
            if(empty($user_id)) {
                $data['c'] = -1024;
                $data['m'] = 'Arg Missing';
                return Response::create($data, 'json')->code(200);
            }
            $user = User_Model::get($user_id);
            if(empty($user)) {
                $data['c'] = -1024;
                $data['m'] = 'User Not Exists';
                return Response::create($data, 'json')->code(200);
            }
            if(empty($user->promotion_qrcode_new) && $user->promotion == 3) {
                $access_token = $this->_access_token();
                if(!empty($access_token)) {
                    $wxconfig = Config::get('wxconfig');
                    $request_url = $wxconfig['code_apis'][$this->app_code].$access_token['access_token'];
                    $params = [
                        'page' => 'pages/distribution/distribution',
                        'scene' => 'from_user_id='.$user_id.'&promo=1',
                        'width' => 180
                    ];

                    $resp = curl_post($request_url, json_encode($params));
                    if(!empty($resp)) {
                        $code_filename = strval($user_id).strval(time());
                        $codefile = './static/code/'.$code_filename.'.png';
                        file_put_contents($codefile, $resp);


                        $file = 'static/image/pk1.png';
                        $file_1 = substr($codefile, 2);
                        $outfile = "static/code/p-".$code_filename.".jpeg";

                        // 加载水印以及要加水印的图像
                        $stamp = imagecreatefromjpeg($file_1);
                        $im = imagecreatefrompng($file);

                        // 设置水印图像的外边距，并且获取水印图像的尺寸
                        $marge_right = 0;
                        $marge_bottom = 0;
                        $sx = imagesx($stamp);
                        $sy = imagesy($stamp);

                        // 利用图像的宽度和水印的外边距计算位置，并且将水印复制到图像上

                        imagecopy($im, $stamp, 220, 690, 0, 0, $sx, $sy);

                        // 输出图像并释放内存
                        imagejpeg($im, $outfile, 100, NULL);
                        imagedestroy($im);

                        $user->promotion_qrcode_new = '/'.$outfile;
                        $user->save();
                    }
                }
                $data['d'] = ['code' => $outfile];
            }
        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }
        return Response::create($data, 'json')->code(200);
    }

    public function promotion_pay_callback()
    {
        try {
            $wrequest = WRequest::createFromGlobals();
            $notify = new Notify($wrequest);
            # Log::record($notify);

            if(!$notify->containsKey('out_trade_no')) {
                $notify->fail('Invalid Request');
            }

            $callback = $notify->toArray();

            $wechat_order = New WechatOrder;
            $wechat_order->data($callback);
            $wechat_order->save();

            $data = ['return_code' => 'SUCCESS', 'return_msg' => 'OK'];

            $wxconfig = Config::get('wxconfig');
            $sign = $callback['sign'];
            unset($callback['sign']);
            if($sign != generate_sign($callback, $wxconfig['mchkeys'][$this->app_code])) {
                $data = ['return_code' => 'FAIL', 'return_msg' => '签名失败'];
                return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
            }

            $usorder = UserPromotionTicket::where('orderid', $wechat_order['out_trade_no'])->find();
            if (empty($usorder) || $usorder->status == 1) {
                return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
            }

            if ($wechat_order['result_code'] !== 'SUCCESS') {
                $usorder->status = 2;
                $usorder->errmsg = $wechat_order['err_code'].'|'.$wechat_order['err_code_res'];

                return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
            }

            if ($wechat_order['total_fee'] != intval($usorder->amount*100)) {
                return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
            }

            $usorder->status = 1;
            $usorder->save();


            $user = User_Model::get($usorder->user_id);
            $user->promotion = 3;
            if(empty($user->promotion_qrcode)) {
                # 生成小程序码
                $access_token = $this->_access_token();
                if(!empty($access_token)) {
                    $wxconfig = Config::get('wxconfig');
                    $request_url = $wxconfig['code_apis'][$this->app_code].$access_token['access_token'];
                    $params = [
                        # 'page' => 'pages/index/index',
                        'page' => 'pages/distribution/distribution',
                        'scene' => 'from_user_id='.$usorder->user_id.'&promo=1',
                        'width' => 180
                    ];

                    $resp = curl_post($request_url, json_encode($params));
                    if(!empty($resp)) {
                        $code_filename = strval($usorder->user_id).strval(time());
                        $codefile = './static/code/'.$code_filename.'.png';
                        file_put_contents($codefile, $resp);


                        $file = 'static/image/p1.png';
                        $file_1 = substr($codefile, 2);
                        $outfile = "static/code/p-".$code_filename.".jpeg";

                        // 加载水印以及要加水印的图像
                        $stamp = imagecreatefromjpeg($file_1);
                        $im = imagecreatefrompng($file);

                        // 设置水印图像的外边距，并且获取水印图像的尺寸
                        $marge_right = 0;
                        $marge_bottom = 0;
                        $sx = imagesx($stamp);
                        $sy = imagesy($stamp);

                        // 利用图像的宽度和水印的外边距计算位置，并且将水印复制到图像上

                        imagecopy($im, $stamp, 220, 690, 0, 0, $sx, $sy);

                        // 输出图像并释放内存
                        imagejpeg($im, $outfile, 100, NULL);
                        imagedestroy($im);

                        $user->promotion_qrcode = '/'.$outfile;
                    }
                }
            }
            $user->save();

            # 如果你是一个代理, 那就不能做别人的代理了
            $exists = UserPromotionGrid::where('user_id', $usorder->user_id)->count();
            if($exists) {
                return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
            }

            $psettings = SettingPromotion::get(1);

            # 加代理
            $user_promo = UserPromotion::where('user_id', $usorder->user_id)->find();
            $user_promo->status = 2;
            $user_promo->save();
            # 加钱
            if($user_promo->parent_user_id) {
                UserPromotionBalance::where('user_id', $user_promo->parent_user_id)
                    ->update([
                        'commission'  => ['exp', "commission+{$psettings->commission_lv1}"],
                        'commission_avail' => ['exp', "commission_avail+{$psettings->commission_lv1}"],
                    ]);
            }
            
            # user_id是谁的一级代理
            $user_promo_grid = New UserPromotionGrid;
            $user_promo_grid->data([
                'parent_user_id' => $user_promo->parent_user_id,
                'user_id' => $user_promo->user_id,
                'level' => 1
            ]);
            $user_promo_grid->save();


            if($user_promo->parent_user_id == 0) {
                return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
            }

            # 找出parent_user_id是谁的一级代理, 把user_id加成为二级代理
            $p1_promo = UserPromotionGrid::where('user_id', $user_promo->parent_user_id)
                ->where('level', 1)->find();
            if(!empty($p1_promo)) {
                if(!empty($p1_promo->parent_user_id)) {
                    $user_promo_grid = New UserPromotionGrid;
                    $user_promo_grid->data([
                        'parent_user_id' => $p1_promo->parent_user_id,
                        'user_id' => $user_promo->user_id,
                        'level' => 2
                    ]);
                    $user_promo_grid->save();

                    # 加钱
                    UserPromotionBalance::where('user_id', $p1_promo->parent_user_id)
                        ->update([
                            'commission'  => ['exp', "commission+{$psettings->commission_lv2}"],
                            'commission_avail' => ['exp', "commission_avail+{$psettings->commission_lv2}"],
                        ]); 
                }

                $p2_promo = UserPromotionGrid::where('user_id', $p1_promo->parent_user_id)->where('level', 1)->find();
                if(!empty($p2_promo)) {
                    $user_promo_grid = New UserPromotionGrid;
                    $user_promo_grid->data([
                        'parent_user_id' => $p2_promo->parent_user_id,
                        'user_id' => $user_promo->user_id,
                        'level' => 3
                    ]);
                    $user_promo_grid->save();

                    # 加钱
                    UserPromotionBalance::where('user_id', $p2_promo->parent_user_id)
                        ->update([
                            'commission'  => ['exp', "commission+{$psettings->commission_lv3}"],
                            'commission_avail' => ['exp', "commission_avail+{$psettings->commission_lv3}"],
                        ]);
                }
                
            }
        } catch (Exception $e) {
            $data = ['return_code' => 'FAIL', 'return_msg' => '失败'];
        }
        return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
    }

    public function transfer()
    {
        try {
            $data = ['c' => 0, 'm'=> '', 'd' => []];

            $request = Request::instance();
            $user_id = $request->param('user_id');
            $amount = $request->param('amount');

            if(empty($user_id) || empty($amount)) {
                $data['c'] = -1024;
                $data['m'] = '参数错误';
                return Response::create($data, 'json')->code(200);
            }

            $user = User_Model::get($user_id);
            if(empty($user) || $user->source != 'neihan_1') {
                $data['c'] = -1024;
                $data['m'] = '用户不存在';
                return Response::create($data, 'json')->code(200);
            }

            $balance = UserPromotionBalance::where('user_id', $user_id)->find();
            if(empty($balance)) {
                $data['c'] = -1024;
                $data['m'] = '账号余额不足';
                return Response::create($data, 'json')->code(200);
            }
            $user_withdraw = New UserWithdraw;

            $exists = $user_withdraw->where('user_id', $user_id)
                ->where('status', 1)
                ->where('create_time', '>=', strtotime(date('Ymd')))
                ->where('create_time', '<=', strtotime(date('Ymd'))+86399)
                ->count();
            if($exists) {
                $data['c'] = -1024;
                $data['m'] = '一天只能提现一次';
                return Response::create($data, 'json')->code(200);
            }

            $amount_left = $balance->commission_avail - $amount;
            if($amount_left < 0) {
                $data['c'] = -1024;
                $data['m'] = '账号余额不足';
                return Response::create($data, 'json')->code(200);
            }
            $balance->commission_avail = $amount_left;
            $balance->save();

            list($usec, $sec) = explode(" ", microtime());  
            $msec = strval(round($usec*1000)); 
            $orderid = date('YmdHis').$msec;

            $user_withdraw->data([
                'user_id' => $user->id,
                'orderid' => $orderid,
                'amount' => floatval($amount),
                'status' => 0,
                'ip' => $request->ip()
            ]);
            $user_withdraw->save();

            $wxconfig = Config::get('wxconfig');
            $options = [
                'app_id' => $wxconfig['appids'][$this->app_code],
                'payment' => [
                    'merchant_id' => $wxconfig['mchids'][$this->app_code],
                    'key' => $wxconfig['mchkeys'][$this->app_code],
                    'cert_path' => './../application/extra/paycert_'.$this->app_code.'/apiclient_cert.pem',
                    'key_path' => './../application/extra/paycert_'.$this->app_code.'/apiclient_key.pem'
                ],
            ];
            $app = new Application($options);
            $merchantPay = $app->merchant_pay;

            $merchantPayData = [
                'partner_trade_no' => $orderid,
                'openid' => $user->openid,
                'check_name' => 'NO_CHECK',
                're_user_name'=> '',
                'amount' => intval($user_withdraw->amount*100),
                'desc' => '代理门票',
                'spbill_create_ip' => $request->ip(),
            ];
            $result = $merchantPay->send($merchantPayData);

            if($result->result_code === 'SUCCESS') {
                $user_withdraw->rel_orderid = $result->payment_no;
                $user_withdraw->payment_time = $result->payment_time;
                $user_withdraw->status = 1;
            } else {
                $user_withdraw->status = 2;
                $user_withdraw->errmsg = $result->err_code.'|'.$result->err_code_des;
                $user_withdraw->ext = json_encode($result);
                $data = ['c' => -1024, 'm'=> '提现失败，请稍后再试', 'd' => []];

                # 失败了再把钱加回去
                $balance->commission_avail += $amount;
                $balance->save();
            }
            $user_withdraw->save();
        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }
        return Response::create($data, 'json')->code(200);
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
