<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Response;
use think\Loader;
use think\Db;
use think\Config;

use app\index\model\User as User_Model;
use app\index\model\UserShare;
use app\index\model\UserShareClick;
use app\index\model\UserFission;
use app\index\model\UserFormId;
use app\index\model\MsgSendRecord;
use app\index\model\Message;
use app\index\model\MessageTask;
use app\index\model\MessageSetting;



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

            $data['d'] = [
                'user_id' => $user->id, 
                'user_name' => $user->user_name, 
                'user_avatar' => $user->user_avatar,
                'gender' => $user->gender,
                'country' => $user->country,
                'province' => $user->province,
                'city' => $user->city
            ];

        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }
        
        return Response::create($data, 'json')->code(200);
    }

    public function click_share_link()
    {
        try {
            $from_user_id = Request::instance()->post('from_user_id');
            $user_id = Request::instance()->post('user_id');
            $video_id = Request::instance()->post('video_id');

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
                    'video_id' => $video_id
                ]);
                if(!$share_click) {
                    $share_click = new UserShareClick;
                    $share_click->data([
                        'from_user_id'  => $from_user_id,
                        'user_id' => $user_id,
                        'video_id' => $video_id
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
}
