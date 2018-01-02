<?php
namespace app\index\model;

use think\Model;

class UserSignLog extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'users_sign_log';

    protected $autoWriteTimestamp = true;
    
}