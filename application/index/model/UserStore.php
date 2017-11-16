<?php
namespace app\index\model;

use think\Model;

class UserStore extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'users_stores';

    protected $autoWriteTimestamp = true;

    public function video()
    {
        return $this->belongsTo('Video', 'video_id', 'group_id');
    }
    
}