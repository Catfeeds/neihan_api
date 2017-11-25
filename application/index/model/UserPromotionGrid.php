<?php
namespace app\index\model;

use think\Model;

class UserPromotionGrid extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'users_promotion_grid';

    protected $autoWriteTimestamp = true;

    public function user()
    {
        return $this->belongsTo('User');
    }

    public function parent_user()
    {
        return $this->belongsTo('User', 'parent_user_id');
    }
    
}