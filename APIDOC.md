# 内涵段子接口文档
## 列表页
* 请求方式：GET
* 请求地址：/api/video/get-list/
* 请求参数：

| 代码     | 类型     |  说明 | 必须|默认值|
| ------- | -------- |------|------|----|
|p        | int      | 页码  | 否   | 1  |
|n        | int      | 每页记录数 | 否| 20|
|order       | string | 排序方式, 目前只支持comment、share、online| 否| |
|t       | int      | 时间戳, 用于加密校验| 是| |
|s     | string      | MD5(t), 简单的检验码, 全大写 | 是| |
|skip_encryt      | int      | 为1时跳过校验, 线上慎用| 否| |


* 返回值

<pre>
{
    'c': 0,         //非0为请求出错，错误信息请看m
    'm': 'xxxxxx',  //c不为0是的错误信息
    'd': [
        {
            'item_id': 1234567,             //视频唯一ID
            'content': 'xxxxxxxxxxx',       //视频描述
            'online_time': 1234556778,      //视频上线时间，格式为时间戳
            'category_name': 'xxxxxxxx',  //视频标签/分类
            'url': 'http://xxxxx',          //视频地址
            'user_name': 'xxxxx',           //发布者名称
            'user_avatar': 'http://xxxxx',  //发布者头像
            'play_count': 1234,             //播放次数        
            'digg_count': 234,              //被顶次数
            'bury_count': 222,              //被踩次数
            'share_count': 456,             //被分享次数
            'comment_count': 12344,        //被评论次数
            'top_comments': [               //热门评论
                {
                    'user_name': 'xxxx',      //热门评论者名称
                    'user_avatar': 'http://xxxxxx',   //热门评论者头像
                    'create_time': 123456789,         //热门评论创建时间
                    'digg_count': 1234,               //评论被顶次数
                    'content': 'xxxxxxxx',            //评论内容
                    'comment_count': 234             //该评论被评论的次数
                }
            ]
        }
    ]
}
</pre>

