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
                    'unionid' => $ret['unionid']
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
            $share_id = Request::instance()->get('share_id');
            $user_id = Request::instance()->get('user_id');

            $data = ['c' => 0, 'm'=> '', 'd' => []];

            if(empty($share_id) || empty($user_id)) {
                $data['c'] = -1024;
                $data['m'] = 'Arg Missing';
                return Response::create($data, 'json')->code(200);
            }

            $share_click = new UserShareClick;
            $share_click->data([
                'share_id'  => $share_id,
                'user_id' => $user_id
            ]);
            $share_click->save();

        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }

        return Response::create($data, 'json')->code(200);
    }
}
