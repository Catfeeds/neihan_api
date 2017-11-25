<?php
namespace app\index\model;

use think\Model;

class VideoPromotion extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'videos_promotion';

    protected $autoWriteTimestamp = true;

    public function video()
    {
        return $this->belongsTo('Video', 'group_id', 'group_id');
    }
    
}