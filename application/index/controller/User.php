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

use Thenbsp\Wechat\Payment\Unifiedorder;
use Thenbsp\Wechat\Payment\Notify;
use Symfony\Component\HttpFoundation\Request as WRequest;

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

            $user_id = Request::instance()->get('user_id');

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
            ];

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
            $wxconfig = Config::get('wxconfig');
            $user_id = Request::instance()->param('user_id');
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
            $ticket->data([
                'appid' => $wxconfig['appids'][$this->app_code],
                'user_id' => $user_id,
                'orderid' => $orderid,
                'rel_orderid' => '',
                'nonce_str' => '',
                'prepay_id' => '',
                'amount' => floatval($psetting->ticket),
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
            $unifiedorder->set('body',          '微信支付测试商品');
            $unifiedorder->set('total_fee',     intval($ticket->amount*100));
            $unifiedorder->set('openid',        $user->openid);
            $unifiedorder->set('trade_type',    'JSAPI');
            $unifiedorder->set('out_trade_no',  $orderid);
            $unifiedorder->set('notify_url',    'https://www.anglailed.cn/api/user/pay/callback');

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

    public function promotion_pay_callback()
    {
        try {
            $wrequest = WRequest::createFromGlobals();
            $notify = new Notify($wrequest);
            Log::record($notify);

            if(!$notify->containsKey('out_trade_no')) {
                $notify->fail('Invalid Request');
            }

            $callback = $notify->toArray();

            $wechat_order = New WechatOrder;
            $wechat_order->data($callback);
            $wechat_order->save();

            $data = ['return_code' => 'SUCCESS', 'return_msg' => 'OK'];
            // $xml = file_get_contents('php://input');
            // Log::record($xml, 'info');
            // if (!trim($xml)) {
            //     $data = ['return_code' => 'FAIL', 'return_msg' => '数据为空'];
            //     return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
            // }
            // $callback = xml_to_data($xml);

            // $wechat_order = New WechatOrder;

            // $wechat_order->data($callback);
            // $wechat_order->save();

            $wxconfig = Config::get('wxconfig');
            $sign = $callback['sign'];
            unset($callback['sign']);
            if($sign != generate_sign($callback, $wxconfig['mchkeys'][$this->app_code])) {
                $data = ['return_code' => 'FAIL', 'return_msg' => '签名失败'];
                return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
            }

            $usorder = UserPromotionTicket::where('orderid', $wechat_order['out_trade_no'])->find();
            if (empty($usorder) || $usorder['status'] == 1) {
                return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
            }

            if ($wechat_order['result_code'] !== 'SUCCESS') {
                $usorder->status = 2;
                $usorder->errmsg = $wechat_order['err_code'].'|'.$wechat_order['err_code_res'];

                return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
            }

            if ($wechat_order['total_fee'] != intval($usorder['amount']*100)) {
                return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
            }

            $usorder->status = 1;
            $usorder->save();


            # 如果你是一个代理, 那就不能做别人的代理了
            $exists = UserPromotionGrid::where('user_id', $usorder['user_id'])->count();
            if($exists) {
                return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
            }

            # 加代理
            $user_promo = UserPromotion::where('user_id', $usorder['user_id']);
            
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
                $user_promo_grid = New UserPromotionGrid;
                $user_promo_grid->data([
                    'parent_user_id' => $p1_promo->parent_user_id,
                    'user_id' => $user_promo->user_id,
                    'level' => 2
                ]);
                $user_promo_grid->save();
            } else {
                $p1_promo = UserPromotionGrid::where('user_id', $user_promo->parent_user_id)->where('level', 2)->find();

                $user_promo_grid = New UserPromotionGrid;
                $user_promo_grid->data([
                    'parent_user_id' => $p1_promo->parent_user_id,
                    'user_id' => $user_promo->user_id,
                    'level' => 3
                ]);
                $user_promo_grid->save();
            }
        } catch (Exception $e) {
            $data = ['return_code' => 'FAIL', 'return_msg' => '失败'];
        }
        return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
    }
}
