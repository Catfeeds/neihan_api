<?php
namespace app\index\model;

use think\Model;

class MessageSetting extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'messages_settings';

    protected $autoWriteTimestamp = true;
    
}