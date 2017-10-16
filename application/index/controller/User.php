<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Response;
use think\Loader;
use think\Db;

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
            $user_id = Request::instance()->post('user_id');
            $user_name = Request::instance()->post('user_name');
            $user_avatar = Request::instance()->post('user_avatar');

            $data = ['c' => 0, 'm'=> '', 'd' => []];

            if(empty($user_id) || empty($user_name) || empty($user_avatar)) {
                $data['c'] = -1024;
                $data['m'] = 'Arg Missing';
                return Response::create($data, 'json')->code(200);
            }
            $user = User_Model::get(['user_id' => $user_id]);
            if(!empty($user)) {
                $user->user_name = $user_name;
                $user->user_avatar = $user_avatar;
                $user->save();
            } else {
                $user = new User_Model;

                $user->data([
                    'user_id'  => $user_id,
                    'user_name' => $user_name,
                    'user_avatar' => $user_avatar
                ]);
                $user->save();    
            }

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
