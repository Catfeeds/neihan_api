<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Response;
use think\Loader;
use think\Db;

use app\index\model\User;
use app\index\model\UserShare;
use app\index\model\Video as Video_Model;
use app\index\model\Comment;

class Video extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        try {
            $p = Request::instance()->has('p', 'get') ? Request::instance()->get('p/d') : 1;
            $n = Request::instance()->has('n', 'get') ? Request::instance()->get('n/d') : 20;
            $order = Request::instance()->has('order', 'get') ? Request::instance()->get('order') : 'comment';

            $data = array('c' => 0, 'm' => '', 'd' => array());
            
            $q = Db::table('videos')->where('top_comments', 1);
            if ($order == 'share') {
                $q = $q->order('share_count', 'desc');
            } elseif ($order == 'online') {
                $q = $q->order('online_time', 'desc');
            } else {
                $q = $q->order('comment_count', 'desc');
            }
            $q = $q->limit($n)->page($p);
            $records = $q->select();
            foreach ($records as $key => $record) {
                $info = array(
                    'video_id' => strval($record['group_id']),
                    'content' => $record['content'],
                    'online_time' => date('Y-m-d H:i:s', $record['online_time']),
                    'category_name' => $record['category_name'],
                    'url' => $this->timestamp_url($record['vurl']),
                    'cover_image' => $record['cover_image'],
                    'user_name' => $record['user_name'],
                    'user_avatar' => $record['user_avatar'],
                    'play_count' => $record['play_count'],
                    'digg_count' => 0,
                    'bury_count' => $record['bury_count'],
                    'share_count' => $record['share_count'],
                    'comment_count' => $record['comment_count'],
                    'comments' => array()
                );
                $top_comments = Db::table('comments')->where('group_id', $record['group_id'])
                                                        ->limit(10)
                                                        ->order('id', 'desc')
                                                        ->select();
                foreach ($top_comments as $val) {
                    $info['comments'][] = array(
                        'comment_id' => $val['id'],
                        'user_name' => $val['user_name'],
                        'user_avatar' => $val['user_avatar'],
                        'content' => $val['content'],
                        'create_time' => $val['create_time'],
                        'digg_count' => $val['digg_count'],
                        'comment_count' => $val['comment_count']
                    );
                }
                $data['d'][] = $info;
            }
        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }
        
        return Response::create($data, 'json')->code(200);
    }

    public function detail()
    {
        try {
            $video_id = Request::instance()->get('video_id');

            $data = array('c' => 0, 'm' => '', 'd' => array());
            
            if(empty($video_id)) {
                $data['c'] = -1024;
                $data['m'] = 'Arg Missing';
                return Response::create($data, 'json')->code(200);
            }

            $record = Video_Model::get(['item_id' => $video_id]);

            $info = array(
                'video_id' => strval($record['group_id']),
                'content' => $record['content'],
                'online_time' => date('Y-m-d H:i:s', $record['online_time']),
                'category_name' => $record['category_name'],
                'url' => $this->timestamp_url($record['vurl']),
                'cover_image' => $record['cover_image'],
                'user_name' => $record['user_name'],
                'user_avatar' => $record['user_avatar'],
                'play_count' => $record['play_count'],
                'digg_count' => 0,
                'bury_count' => $record['bury_count'],
                'share_count' => $record['share_count'],
                'comment_count' => $record['comment_count'],
                'comments' => array()
            );
            $top_comments = Db::table('comments')->where('group_id', $record['group_id'])
                                                    ->limit(10)
                                                    ->order('id', 'desc')
                                                    ->select();
            foreach ($top_comments as $val) {
                $info['comments'][] = array(
                    'comment_id' => $val['id'],
                    'user_name' => $val['user_name'],
                    'user_avatar' => $val['user_avatar'],
                    'content' => $val['content'],
                    'create_time' => $val['create_time'],
                    'digg_count' => $val['digg_count'],
                    'comment_count' => $val['comment_count']
                );
            }
            $data['d'] = $info;
        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }
        
        return Response::create($data, 'json')->code(200);
    }

    public function count()
    {
        try {
            $user_id = Request::instance()->post('user_id');
            $video_id = Request::instance()->post('video_id');
            $type = Request::instance()->post('type');

            $data = ['c' => 0, 'm'=> '', 'd' => []];

            if(empty($user_id) || empty($video_id) || empty($type)) {
                $data['c'] = -1024;
                $data['m'] = 'Arg Missing';
                return Response::create($data, 'json')->code(200);
            }

            $user = User::get(['user_id' => $user_id]);
            if(empty($user)) {
                $data['c'] = -1024;
                $data['m'] = 'User Not Exists';
                return Response::create($data, 'json')->code(200);   
            }

            $video = Video_Model::get(['item_id' => $video_id]);
            if(empty($video)) {
                $data['c'] = -1024;
                $data['m'] = 'Video Not Exists';
                return Response::create($data, 'json')->code(200);   
            }
            if($type == 'digg') {
                $video->digg_count += 1;
            } elseif($type == 'bury') {
                $video->bury_count += 1;
            } elseif($type == 'play') {
                $video->play_count += 1;
            }
            $video->save();
        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }

        return Response::create($data, 'json')->code(200);
    }

    public function share()
    {
        try {
            $user_id = Request::instance()->post('user_id');
            $video_id = Request::instance()->post('video_id');

            $data = ['c' => 0, 'm'=> '', 'd' => []];

            if(empty($user_id) || empty($video_id)) {
                $data['c'] = -1024;
                $data['m'] = 'Arg Missing';
                return Response::create($data, 'json')->code(200);
            }

            $user = User::get(['user_id' => $user_id]);
            if(empty($user)) {
                $data['c'] = -1024;
                $data['m'] = 'User Not Exists';
                return Response::create($data, 'json')->code(200);   
            }

            $video = Video_Model::get(['item_id' => $video_id]);
            if(empty($video)) {
                $data['c'] = -1024;
                $data['m'] = 'Video Not Exists';
                return Response::create($data, 'json')->code(200);   
            }

            $video->share_count += 1;
            $video->save();

            $user_share = new UserShare;
            $user_share->data([
                'user_id'  => $user_id
            ]);
            $user_share->save();

            $data['d'] = ['id' => $user_share->id];
        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];   
        }
        
        return Response::create($data, 'json')->code(200);
    }

    public function comment()
    {
        try {
            $user_id = Request::instance()->post('user_id');
            $video_id = Request::instance()->post('video_id');
            $content = Request::instance()->post('content');

            $data = ['c' => 0, 'm'=> '', 'd' => []];

            if(empty($user_id) || empty($video_id) || empty($content)) {
                $data['c'] = -1024;
                $data['m'] = 'Arg Missing';
                return Response::create($data, 'json')->code(200);
            }

            $user = User::get(['user_id' => $user_id]);
            if(empty($user)) {
                $data['c'] = -1024;
                $data['m'] = 'User Not Exists';
                return Response::create($data, 'json')->code(200);   
            }

            $video = Video_Model::get(['item_id' => $video_id]);
            if(empty($video)) {
                $data['c'] = -1024;
                $data['m'] = 'Video Not Exists';
                return Response::create($data, 'json')->code(200);   
            }

            $comment = new Comment;
            $comment->data([
                'user_id'  => $user_id,
                'user_name' => $user->user_name,
                'user_avatar' => $user->user_avatar,
                'group_id' => $video->group_id,
                'item_id' => $video->item_id,
                'content' => $content,
                'digg_count' => 0,
                'comment_count' =>0
            ]);
            $comment->save();

            $video->comment_count += 1;
            $video->save();

        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];  
        }

        return Response::create($data, 'json')->code(200);
    }

    public function timestamp_url($url)
    {
        return str_replace('timestamp', strval(time()).'.'.strval(rand(10, 60)), $url);
    }
}
