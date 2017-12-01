<?php
namespace app\index\model;

use think\Model;

class Comment extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'comments_v3';

    protected $autoWriteTimestamp = true;
    
}