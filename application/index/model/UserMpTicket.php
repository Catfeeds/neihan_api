<?php
namespace app\index\model;

use think\Model;

class UserMpTicket extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'users_mp_ticket';

    protected $autoWriteTimestamp = true;

}