<?php
namespace app\index\model;

use think\Model;

class UserPromotionBalance extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'users_promotion_balance';

    protected $autoWriteTimestamp = true;

    public function user()
    {
        return $this->belongsTo('User');
    }

    public function agent()
    {
        return $this->hasMany('UserPromotionGrid', 'parent_user_id', 'user_id');
    }
    
}