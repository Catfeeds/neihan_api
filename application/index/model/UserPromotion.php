<?php
namespace app\index\model;

use think\Model;

class UserPromotion extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'users_promotion';

    protected $autoWriteTimestamp = true;

    public function user()
    {
        return $this->belongsTo('User');
    }
    
}