<?php
namespace app\index\model;

use think\Model;
use think\Db;

class Video extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'videos';




    public function get_videos($user_id, $category=[], $vids=[], $n=1, $order="display_click_ratio", $dcount=0)
    {
        $ret = [];
        $p = 1;

        $sql_select = "SELECT * FROM videos";
        if(empty($category)) {
            $sql_where = " WHERE category_id IN (12, 109, 187) AND top_comments = 1 ";
        } else {
            $sql_where = " WHERE category_id IN (".implode(",", $category).")";
        }
        if(!empty($vids)) {
            $sql_where .= " AND group_id NOT IN ('".implode("','", $vids)."')";
        }
        if($dcount > 0) {
            $sql_where .= " AND c_display_count <= {$dcount}";
        }

        $sql_where .= " AND group_id NOT IN (SELECT video_id FROM videos_display_logs WHERE user_id = :user_id)";
        $sql_order = " ORDER BY ".$order." DESC, play_count DESC ";
        $sql_limit = " LIMIT ".(($p-1)*$n).", {$n}";

        $sql = $sql_select.$sql_where.$sql_order.$sql_limit;

        $records = Db::query($sql, ['user_id' => $user_id]);
        foreach ($records as $key => $record) {
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
                'jump' => 0,
                'level' => $record['level'],
                'display_click_ratio' => $record['display_click_ratio'],
                'comments' => [],
            );
            if($record['category_id'] == 1111) {
                $info['jump'] = 1;
            }
            $ret[] = $info;
        }
        return $ret;
    }

    public function get_videos_waitting($user_id, $p, $n)
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