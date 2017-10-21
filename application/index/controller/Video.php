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
use app\index\model\UserLog;
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
            $n = Request::instance()->has('n', 'get') ? Request::instance()->get('n/d') : 5;
            $user_id = Request::instance()->get('user_id');
            $order = Request::instance()->has('order', 'get') ? Request::instance()->get('order') : 'comment';

            $data = array('c' => 0, 'm' => '', 'd' => array());

            if(empty($user_id)) {
                $data['c'] = -1024;
                $data['m'] = 'Arg Missing';
                return Response::create($data, 'json')->code(200);
            }
            
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

            $vids = [];
            foreach ($records as $key => $record) {
                $vids[] = $record['group_id'];

                $info = array(
                    'video_id' => strval($record['group_id']),
                    'content' => $record['content'],
                    'online_time' => date('Y-m-d H:i:s', $record['online_time']),
                    'category_name' => $record['category_name'],
                    'url' => $this->timestamp_url($record['vurl']),
                    'cover_image' => str_replace('.webp', '', $record['cover_image']),
                    'user_name' => $record['user_name'],
                    'user_avatar' => $record['user_avatar'],
                    'play_count' => $record['play_count']+$record['c_play_count'],
                    'digg_count' => $record['digg_count']+$record['c_digg_count'],
                    'bury_count' => $record['bury_count']+$record['c_bury_count'],
                    'share_count' => $record['share_count']+$record['c_share_count'],
                    'comment_count' => $record['comment_count']+$record['c_comment_count'],
                    'is_digg' => 0,
                    'comments' => array()
                );
                $is_digg = Db::table('users_logs')
                                    ->where('user_id', $user_id)
                                    ->where('video_id', $info['video_id'])
                                    ->where('type', 6)
                                    ->count();
                if($is_digg) {
                    $info['is_digg'] = 1;
                }

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

            # 更新视频展示数
            Video_Model::where('group_id', 'in', $vids)->setInc('c_display_count');
            if(!empty($user_id)) {
                $display_logs = [];
                foreach ($vids as $vid) {
                    $display_logs[] = ['user_id' => $user_id, 'video_id' => $vid, 'type'=>1];
                }
                $use_log = new UserLog;
                $use_log->saveAll($display_logs);
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
            $user_id = Request::instance()->get('user_id');

            $data = array('c' => 0, 'm' => '', 'd' => array());
            
            if(empty($video_id) || empty($user_id)) {
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
                'cover_image' => str_replace('.webp', '', $record['cover_image']),
                'user_name' => $record['user_name'],
                'user_avatar' => $record['user_avatar'],
                'play_count' => $record['play_count']+$record['c_play_count'],
                'digg_count' => $record['digg_count']+$record['c_digg_count'],
                'bury_count' => $record['bury_count']+$record['c_bury_count'],
                'share_count' => $record['share_count']+$record['c_share_count'],
                'is_digg' => 0,
                'comment_count' => $record['comment_count']+$record['c_comment_count'],
                'comments' => array()
            );

            $is_digg = Db::table('users_logs')
                                    ->where('user_id', $user_id)
                                    ->where('video_id', $video_id)
                                    ->where('type', 6)
                                    ->count();
            if($is_digg) {
                $info['is_digg'] = 1;
            }

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

            $user = User::get($user_id);
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
                $video->c_digg_count += 1;
            } elseif($type == 'bury') {
                $video->c_bury_count += 1;
            } elseif($type == 'play') {
                $video->c_play_count += 1;
            } elseif($type == 'play_end') {
                $video->c_play_end_count += 1;
            }
            $video->save();

            $com_config = Config::get('comconfig');
            $user_log = New UserLog;
            $user_log->data([
                'user_id' => $user_id,
                'video_id' => $video_id,
                'type' => $com_config['log_type'][$type]
            ]);
            $user_log->save();
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
            $page = Request::instance()->post('page');

            $data = ['c' => 0, 'm'=> '', 'd' => []];

            if(empty($user_id) || empty($video_id)) {
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

            $video = Video_Model::get(['item_id' => $video_id]);
            if(empty($video)) {
                $data['c'] = -1024;
                $data['m'] = 'Video Not Exists';
                return Response::create($data, 'json')->code(200);   
            }

            $video->c_share_count += 1;
            $video->save();

            $user_share = new UserShare;
            $user_share->data([
                'user_id'  => $user_id,
                'video_id' => $video_id
            ]);
            $user_share->save();

            $com_config = Config::get('comconfig');
            $user_log = new UserLog;
            $user_log->data([
                'user_id' => $user_id,
                'video_id' => $video_id,
                'type' => $com_config['log_type']['share']
            ]);
            $user_log->save();

            /*
            # 暂时不用小程序码分享
            $access_token = $this->_access_token();
            if(!empty($access_token)) {
                $wxconfig = Config::get('wxconfig');
                $request_url = $wxconfig['code_api'].$access_token['access_token'];
                $params = [
                    'page' => $page,
                    'scene' => 'uid='.$user_id.'&vid='.$video_id.'&sid='.$user_share->id
                ];
                $resp = curl_post($request_url, json_encode($params));
                if(!empty($resp)) {
                    $code_filename = strval(time()).'.jpeg';
                    $codefile = './static/code/'.$code_filename;
                    file_put_contents($codefile, $resp);
                }
            }

            $data['d'] = ['id' => $user_share->id, 'code' => '/static/code/'.$code_filename];

            $user_share->code = $data['d']['code'];
            $user_share->save();
            */

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

            $user = User::get($user_id);
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

            $video->c_comment_count += 1;
            $video->save();

            $com_config = Config::get('comconfig');
            $user_log = new UserLog;
            $user_log->data([
                'user_id' => $user_id,
                'video_id' => $video_id,
                'type' => $com_config['log_type']['comment']
            ]);
            $user_log->save();

        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];  
        }

        return Response::create($data, 'json')->code(200);
    }

    public function timestamp_url($url)
    {
        return str_replace('timestamp', strval(time()).'.'.strval(rand(10, 60)), $url);
    }

    private function _access_token()
    {
        try {
            $is_expired = true;

            $access_token = [];
            $access_token_file = './../application/extra/access_token.txt';
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
                $resp = curl_get($wxconfig['token_api']);
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
