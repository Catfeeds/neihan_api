<?php
namespace app\index\model;

use think\Model;

class UserPromotionTicket extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'users_promotion_ticket';

    protected $autoWriteTimestamp = true;

}