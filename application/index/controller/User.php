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
use app\index\model\UserFormId;


class User extends Controller
{
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
                    'unionid' => ''
                ]);
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

            $user->user_name = $user_name;
            $user->user_avatar = $user_avatar;
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

            $data['d'] = ['user_id' => $user->id, 'user_name' => $user->user_name, 'user_avatar' => $user->user_avatar];

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
        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }

        return Response::create($data, 'json')->code(200);
    }
}
