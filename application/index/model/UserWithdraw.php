<?php
namespace app\index\model;

use think\Model;

class UserWithdraw extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'users_withdraw';

    protected $autoWriteTimestamp = true;
    
}