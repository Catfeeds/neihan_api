<?php
namespace app\index\model;

use think\Model;
use think\Db;

class Video extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'videos';




    public function get_videos($user_id, $level=[], $n=1, $normal=0)
    {
        $ret = [];
        $p = 1;

        $sql_select = "SELECT * FROM videos";
        $sql_where = " WHERE category_id IN (65) AND top_comments = 1 ";
        if(!empty($level)) {
            $sql_where .= " AND level IN (".join(',', $level).")";
        }
        if($normal) {
            $sql_where .= " AND c_display_count < 1000 ";
        }

        $sql_where .= " AND group_id NOT IN (SELECT video_id FROM videos_display_logs WHERE user_id = :user_id)";
        $sql_order = " ORDER BY c_display_count DESC, comment_count DESC ";
        $sql_limit = " LIMIT ".(($p-1)*$n).", {$n}";

        $sql = $sql_select.$sql_where.$sql_order.$sql_limit;

        $records = Db::query($sql, ['user_id' => $user_id]);
        foreach ($records as $key => $record) {
            $comments = json_decode($record['comments'], true);

            $info = array(
                'video_id' => strval($record['group_id']),
                'content' => $record['content'],
                'online_time' => date('Y-m-d H:i:s', $record['online_time']),
                'category_name' => $record['category_name'],
                'url' => timestamp_url($record['vurl']),
                'cover_image' => str_replace('.webp', '', $record['cover_image']),
                'user_name' => $record['user_name'],
                'user_avatar' => $record['user_avatar'],
                'play_count' => $record['play_count']+$record['c_play_count'],
                'digg_count' => $record['digg_count']+$record['c_digg_count'],
                'bury_count' => $record['bury_count']+$record['c_bury_count'],
                'share_count' => $record['share_count']+$record['c_share_count'],
                'comment_count' => $record['comment_count']+$record['c_comment_count'],
                'is_digg' => 0,
                'level' => $record['level'],
                'comments' => []
            );
            if(!empty($comments)) {
                foreach ($comments as $c) {
                    $info['comment'][] = [
                        'user_name'=> $c['user_name'],
                        'user_avatar'=> $c['user_profile_image_url'],
                        'create_time'=> $c['create_time'],
                        'digg_count'=> $c['digg_count'],
                        'content'=> $c['text'],
                        'is_digg'=> 0
                    ];
                }
            }
            $ret[] = $info;
        }
        return $ret;
    }

    public function get_videos_online($user_id, $p, $n)
    {
        $ret = [];

        $sql_select = "SELECT * FROM videos";
        $sql_where = " WHERE online=1 ";
        $sql_order = " ORDER BY comment_count DESC ";
        $sql_limit = " LIMIT ".(($p-1)*$n).", {$n}";

        $sql = $sql_select.$sql_where.$sql_order.$sql_limit;

        $records = Db::query($sql);
        foreach ($records as $key => $record) {
            $comments = json_decode($record['comments'], true);

            $info = array(
                'video_id' => strval($record['group_id']),
                'content' => $record['content'],
                'online_time' => date('Y-m-d H:i:s', $record['online_time']),
                'category_name' => $record['category_name'],
                'url' => timestamp_url($record['vurl']),
                'cover_image' => str_replace('.webp', '', $record['cover_image']),
                'user_name' => $record['user_name'],
                'user_avatar' => $record['user_avatar'],
                'play_count' => $record['play_count']+$record['c_play_count'],
                'digg_count' => $record['digg_count']+$record['c_digg_count'],
                'bury_count' => $record['bury_count']+$record['c_bury_count'],
                'share_count' => $record['share_count']+$record['c_share_count'],
                'comment_count' => $record['comment_count']+$record['c_comment_count'],
                'is_digg' => 0,
                'level' => $record['level'],
                'comments' => []
            );
            if(!empty($comments)) {
                foreach ($comments as $c) {
                    $info['comment'][] = [
                        'user_name'=> $c['user_name'],
                        'user_avatar'=> $c['user_profile_image_url'],
                        'create_time'=> $c['create_time'],
                        'digg_count'=> $c['digg_count'],
                        'content'=> $c['text'],
                        'is_digg'=> 0
                    ];
                }
            }
            $ret[] = $info;
        }
        return $ret;
    }
    
}