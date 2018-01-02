<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Response;
use think\Loader;
use think\Db;
use think\Config;
use think\Log;

use app\index\controller\Base;

use app\index\model\User as User_Model;
use app\index\model\UserMp;
use app\index\model\UserShare;
use app\index\model\UserShareClick;
use app\index\model\UserFission;
use app\index\model\UserFormId;
use app\index\model\UserMlevel;
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
use app\index\model\UserPointLog;
use app\index\model\UserSignLog;


use Thenbsp\Wechat\Payment\Unifiedorder;
use Thenbsp\Wechat\Payment\Notify;
use Symfony\Component\HttpFoundation\Request as WRequest;

use EasyWeChat\Foundation\Application;

use Predis;

class User extends Base
{
    public function _initialize()
    {
        $request = Request::instance();
        $this->comconfig = Config::get('comconfig');

        $this->app_code = 'neihan_1';
        foreach ($this->comconfig['domain_settings'] as $key => $value) {
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

            $wxconfig = Config::get('wxconfig');
            $request_url = $wxconfig['login_apis'][$this->app_code].'&js_code='.$js_code;
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

            $client = new Predis\Client();
            $not_exists = $client->executeRaw(['SETNX', md5($ret['openid']), 1]);
            $user = User_Model::get(['openid' => $ret['openid']]);

            if($not_exists) {
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

            $user_id = Request::instance()->param('user_id');
            $user_name = Request::instance()->param('user_name');
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

            $before_user_name = $user->user_name;

            $user->user_name = $user_name ? $user_name : '';
            $user->user_avatar = $user_avatar ? $user_avatar : '';
            $user->gender = $gender ? $gender : 0;
            $user->country = $country ? $country : '';
            $user->province = $province ? $province : '';
            $user->city = $city ? $city : '';

            $user->save();

            if(empty($before_user_name) && !empty($user->user_name)) {
                $ptype = '101';
                $this->_add_user_point($user_id, $ptype);
            }

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
            if($user->mp_qrcode) {
                $user_promo = UserPromotion::where('user_id', $user_id)->find();
                if($user_promo->status == 2) {
                    $ptype = 2;    
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
                'point_total' => 0,
                'point_info' => []
            ];
            $data['d']['qrcode'] = $user->mp_qrcode ? $request->domain().strval($user->mp_qrcode) : $request->domain().strval($user->promotion_qrcode_new);

            $point_log = Db::table('users_point_log')
                ->field('sum(point) as total_point, type, user_id')
                ->where('user_id', $user_id)
                ->where('date', date('Y-m-d'))
                ->group('user_id, type')
                ->select();
            foreach ($point_log as $k => $v) {
                $data['d']['point_info'][] = [
                    'type' => $v['type'],
                    'total' => intval($v['total_point'])
                ];
            }

            $user_balance = UserPromotionBalance::get(['user_id' => $user_id]);
            if($user_balance) {
                $data['d']['point_total'] = $user_balance->point_avail;
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

            $from_user = User_Model::get($from_user_id);
            $user = User_Model::get($user_id);
            
            $today = date('Y-m-d');
            $today_ts = strtotime($today);
            if($from_user_id != $user_id) {
                # 记录用户裂变数据
                $share_fission = UserFission::where('user_id', $user_id)->count();
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

                    # 代理获取新用户, 加值
                    if($from_user->promotion >= 3) {
                        $this->_add_user_point($from_user_id, '106');
                    } else {
                        # 下级用户获取新用户
                        if($from_user->create_time >= $today.' 00:00:00') {
                            # 新
                            $this->_add_parent_user_point($from_user_id, '206');
                        } else {
                            # 旧
                            $this->_add_parent_user_point($from_user_id, '306');
                        }
                    }
                }

                # 代理激活老用户
                if($from_user->promotion >= 3 && $user->create_time < $today.' 00:00:00') {
                    $share_click_exists = UserShareClick::where('from_user_id', $from_user_id)
                        ->where('user_id', $user_id)
                        ->where('date', $today)
                        ->count();
                    if(!$share_click_exists) {
                        $this->_add_user_point($from_user_id, '107');
                    }
                }

                $share_click = UserShareClick::where('from_user_id', $from_user_id)
                    ->where('user_id', $user_id)
                    ->where('video_id', $video_id)
                    ->where('date', $today)
                    ->where('wechat_gid', strval($wechat_gid))
                    ->count();
                if(!$share_click) {
                    $share_click = new UserShareClick;
                    $share_click->data([
                        'from_user_id'  => $from_user_id,
                        'user_id' => $user_id,
                        'video_id' => $video_id,
                        'date' => $today,
                        'wechat_gid' => strval($wechat_gid)
                    ]);
                    $share_click->save();

                    try {
                        $msg_send = Message::where('from_user_id', $from_user_id)
                            ->where('group_id', $video_id)
                            ->where('is_send', '>=', 1)
                            ->where('app', $this->app_code)
                            ->setInc('active_member');
                    } catch (Exception $e) {
                        
                    }
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
            $user_id = Request::instance()->param('user_id');
            $form_id = Request::instance()->param('form_id');

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
                                'send_time' => date('Y-m-d H:i:s', strtotime("+{$settings->interval} minutes")),
                                'source' => $this->app_code
                            ]);
                            $msgtask->save();
                        }
                    }
                }
            } catch (Exception $e) {
                
            }

            # 统计重度，中度，微度用户数
            $formid_count = UserFormId::where('user_id', $user_id)->where('is_used', 0)->where('create_time', '>=', intval(time())-86400)->count();
            if($formid_count >= 5) {
                $user_melvel = UserMlevel::get(['user_id' => $user_id]);
                if(empty($user_melvel)) {
                    $user_melvel = new UserMlevel;
                    $user_melvel->data([
                        'user_id' => $user_id,
                        'level' => 1
                    ]);
                }

                if($formid_count >= 11 && $formid_count <= 19 && $user_melvel->level < 2) {
                    $user_melvel->level = 2;
                } elseif($formid_count >= 20 && $user_melvel->level < 3) {
                    $user_melvel->level = 3;
                }
                $user_melvel->source = $this->app_code;
                $user_melvel->save();
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
            $from_user = explode('|', Request::instance()->param('from_user_id'));
            if(count($from_user) == 1) {
                $from_user[] = 0;
            }

            $from_user_id = $from_user[0];
            $user_mp_id = $from_user[1];

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
            if(!empty($user_mp_id) && !$user->user_mp_id) {

                $usermp = UserMp::get($user_mp_id);
                if(empty($usermp)) {
                    $data['c'] = -1024;
                    $data['m'] = 'User NotExists';
                    return Response::create($data, 'json')->code(200);
                }

                $mpexists = User_Model::where('user_mp_id', $user_mp_id)->count();
                if($mpexists) {
                    $data['c'] = -1024;
                    $data['m'] = 'User NotExists';
                    return Response::create($data, 'json')->code(200);
                }
 
                $user->user_mp_id = $user_mp_id;
                $user->user_name = $usermp->user_name;
                $user->gender = $usermp->gender;
                $user->save();
            }

            if(!empty($from_user_id)) {
                $parent_user = UserPromotion::get(['user_id' => $from_user_id]);
                if($parent_user->status != 2) {
                    $data['c'] = -1024;
                    $data['m'] = 'From User Is Not Valid';
                    return Response::create($data, 'json')->code(200);
                }
            }

            if($user->promotion == 0) {
                $usermp = UserMp::where('id', $user->user_mp_id)->find();
                if($usermp && $usermp->promotion == 2) {
                    $user->promotion = 2;
                    $user->promotion_time = $usermp->promotion_time;
                } else {
                    $user->promotion = 1;
                    $user->promotion_time = time();
                }
                $user->save();

                $user_promo = UserPromotion::where('parent_user_id', $from_user_id)
                    ->where('user_id', $user_id)->count();
                if(empty($user_promo)) {
                    $user_promo = New UserPromotion;
                    $user_promo->data([
                        'parent_user_id' => $from_user_id,
                        'user_id' => $user_id,
                        'status' => 1,
                        'type' => 0
                    ]);
                    $user_promo->save();
                }

                $user_balance = UserPromotionBalance::where('user_id', $user_id)->count();
                if(empty($user_balance)) {
                    $user_balance = New UserPromotionBalance;
                    $user_balance->data([
                        'user_id' => $user_id,
                        'commission' => 1,
                        'commission_avail' => 1
                    ]);
                    $user_balance->save();
                }
            } elseif($user->promotion == 0 && $user_mp_id) {
                User_Model::where('id', $user_id)->update(['promotion'=>2, 'promotion_time'=> time()]);

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
            $outfile = '';
            if($user->mp_qrcode) {
                $outfile = Request::instance()->domain().$user->mp_qrcode;
            }
            $data['d'] = ['code' => $outfile];
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
            if(empty($user)) {
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
                $data = ['c' => -1024, 'm'=> '系统余额不足', 'd' => []];

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

    public function refresh_qrcode()
    {
        try {
            $users =  User_Model::all(function($query){
                $query->where('promotion', 3)->where('promotion_qrcode_new', '');
            });
            foreach ($users as $u) {
                $url = 'http://www.jialejiabianli.cn/api/user/promo/qrcode?user_id='.$u->id;
                # curl_get($url);
                print_r($url.'<br>');
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
        return 'Script Done!';
    }

    public function mp_qrcode()
    {
        try {
            $data = ['c' => 0, 'm'=> '', 'd' => []];

            $user_id = Request::instance()->param('user_id');

            if(empty($user_id)) {
                $data['c'] = -1024;
                $data['m'] = '参数错误';
                return Response::create($data, 'json')->code(200);
            }

            $qrcode = $this->_generate_qrcode($user_id);

            $data['d']['code'] = Request::instance()->domain().$qrcode[0];
        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }
        return Response::create($data, 'json')->code(200);
    }

    public function signin()
    {
        try {
            $data = ['c' => 0, 'm'=> '', 'd' => []];

            $user_id = Request::instance()->param('user_id');
            if(empty($user_id)) {
                $data['c'] = -1024;
                $data['m'] = '参数错误';
                return Response::create($data, 'json')->code(200);
            }

            $user = User_Model::get($user_id);

            if(empty($user) || $user->promotion < 3) {
                $data['c'] = -1024;
                $data['m'] = '用户不存在';
                return Response::create($data, 'json')->code(200);
            }

            $sign_log = UserSignLog::get(['user_id' => $user_id, 'date' => date('Y-m-d')]);
            if(empty($sign_log)) {

                Db::startTrans();
                try{
                    $ptype = '102';
                    Db::execute('INSERT INTO users_sign_log (`user_id`, `date`, `create_time`, `update_time`) VALUES (?, ?, ?, ?)', [$user_id, date('Y-m-d'), time(), time()]);
                    $this->_add_user_point($user_id, $ptype);
                    Db::commit();    
                } catch (\Exception $e) {
                    Db::rollback();
                }
            }
        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }
        return Response::create($data, 'json')->code(200);
    }
}
