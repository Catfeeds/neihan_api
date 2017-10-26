<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Response;
use think\Loader;
use think\Db;
use think\Config;

use app\index\model\User;
use app\index\model\UserShare;
use app\index\model\Video as Video_Model;
use app\index\model\UserLog;
use app\index\model\Comment as Comment_Model;
use app\index\Model\VideoDisplayLog;

class Comment extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $data = ['c' => 0, 'm'=> '', 'd' => []];   
        return Response::create($data, 'json')->code(200);
    }

    public function count()
    {
        try {
            $user_id = Request::instance()->post('user_id');
            $comment_id = Request::instance()->post('comment_id');
            $type = Request::instance()->post('type');

            $data = ['c' => 0, 'm'=> '', 'd' => []];

            if(empty($user_id) || empty($comment_id) || empty($type)) {
                $data['c'] = -1024;
                $data['m'] = 'Arg Missing';
                return Response::create($data, 'json')->code(200);
            }

            $user = User::get($user_id);
            if(empty($user)) {
                $data['c'] = -1024;
                $data['m'] = 'User Not Exists';
                return Response::create($data, 'json')->code(200);   
            }

            $comment = Comment_Model::get(['id' => $comment_id]);
            if(empty($comment)) {
                $data['c'] = -1024;
                $data['m'] = 'Comment Not Exists';
                return Response::create($data, 'json')->code(200);   
            }
            if($type == 'digg') {
                $comment->digg_count += 1;
            }
            $comment->save();

            $com_config = Config::get('comconfig');
            $user_log = New UserLog;
            $user_log->data([
                'user_id' => $user_id,
                'video_id' => $comment_id,
                'type' => 7
            ]);
            $user_log->save();

        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }

        return Response::create($data, 'json')->code(200);
    }
}
