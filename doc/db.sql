CREATE DATABASE `neihanshequ` DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `videos` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT(20) NOT NULL COMMENT '视频分组ID',
    `item_id` BIGINT(20) NOT NULL COMMENT '该记录在第三方平台的ID',
    `video_id` VARCHAR(64) NOT NULL COMMENT '视频的ID',
    `content` VARCHAR(1024) NOT NULL COMMENT '视频的文字描述',
    `category_id` INT(11) NOT NULL COMMENT '分类ID',
    `category_name` VARCHAR(128) NOT NULL COMMENT '分类名称',
    `url` VARCHAR(512) NOT NULL COMMENT '视频或图片的URL',
    `vurl` VARCHAR(512) NOT NULL COMMENT '视频的URL，需要跳转一次才能拿到真正的视频地址',
    `cover_image` VARCHAR(512) NOT NULL COMMENT '视频封面图片',
    `online_time` BIGINT(20) COMMENT '上线时间，可以做排序',
    `user_id` BIGINT(20) NOT NULL COMMENT '发布者ID',
    `user_name` VARCHAR(128) NOT NULL COMMENT '发布者名称',
    `user_avatar` VARCHAR(258) NOT NULL COMMENT '发布者头像',
    `play_count` INT(11) DEFAULT '0' COMMENT '视频播放次数',
    `bury_count` INT(11) DEFAULT '0' COMMENT '被踩次数',
    `repin_count` INT(11) DEFAULT '0' COMMENT '被顶次数',
    `share_count` INT(11) DEFAULT '0' COMMENT '分享次数',
    `digg_count` INT(11) DEFAULT '0' COMMENT '被顶次数',
    `comment_count` INT(11) DEFAULT '0' COMMENT '被顶次数',
    `has_comments` INT(11) DEFAULT '0' COMMENT '是否有置顶评论',
    `comments` TEXT COMMENT '置顶评论列表',
    `top_comments` INT(11) DEFAULT '0' COMMENT '是否爬取了热门评论',
    `is_expired`  INT(11) DEFAULT '0' COMMENT '视频链接是否失效了',
    `check_expire_time` INT(11) DEFAULT '0' COMMENT '是否检测过视频链接失效',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '视频表';
ALTER TABLE `videos` ADD UNIQUE (`video_id`);
ALTER TABLE `videos` ADD COLUMN `c_play_count` INT(11) NOT NULL DEFAULT '0' COMMENT '本记录的播放次数';
ALTER TABLE `videos` ADD COLUMN `c_play_end_count` INT(11) NOT NULL DEFAULT '0' COMMENT '本记录的播放完成次数';
ALTER TABLE `videos` ADD COLUMN `c_bury_count` INT(11) NOT NULL DEFAULT '0' COMMENT '本记录的踩次数';
ALTER TABLE `videos` ADD COLUMN `c_digg_count` INT(11) NOT NULL DEFAULT '0' COMMENT '本记录的点赞次数';
ALTER TABLE `videos` ADD COLUMN `c_share_count` INT(11) NOT NULL DEFAULT '0' COMMENT '本记录的分享次数';
ALTER TABLE `videos` ADD COLUMN `c_comment_count` INT(11) NOT NULL DEFAULT '0' COMMENT '本记录的评论次数';
ALTER TABLE `videos` ADD COLUMN `c_display_count` INT(11) NOT NULL DEFAULT '0' COMMENT '本记录的展示次数';
ALTER TABLE `videos` ADD COLUMN `level` INT(11) NOT NULL DEFAULT '1' COMMENT '视频等级,用4位二进制表示,0001普通、0010热度推荐、0100精品推荐';
ALTER TABLE `videos` ADD COLUMN `display_click_ratio` DECIMAL(8, 3) NOT NULL DEFAULT '0' COMMENT '展示点击率';
ALTER TABLE `videos` ADD COLUMN `display_share_ratio` DECIMAL(8, 3) NOT NULL DEFAULT '0' COMMENT '展示转发率';
ALTER TABLE `videos` ADD COLUMN `hot_ratio` DECIMAL(8, 3) NOT NULL DEFAULT '0' COMMENT '兴趣热度';
ALTER TABLE `videos` ADD COLUMN `online` INT(11) NOT NULL DEFAULT '0' COMMENT '用于上线审核的视频';


CREATE TABLE IF NOT EXISTS `comments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT(20) NOT NULL COMMENT '发布者ID',
    `user_name` VARCHAR(128) NOT NULL COMMENT '发布者名称',
    `user_avatar` VARCHAR(258) NOT NULL COMMENT '发布者头像',
    `create_time` BIGINT(20) NOT NULL DEFAULT '0' COMMENT '发布时间',
    `content` VARCHAR(1024) NOT NULL COMMENT '内容',
    `digg_count` INT(11) NOT NULL DEFAULT '0' COMMENT '被顶次数',
    `comment_count` INT(11) NOT NULL DEFAULT '0' COMMENT '被评论次数',
    `group_id` BIGINT(20) NOT NULL COMMENT '视频分组ID',
    `item_id` BIGINT(20) NOT NULL COMMENT '该记录在第三方平台的ID',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '视频评论表';
ALTER TABLE `comments` ADD COLUMN `update_time` BIGINT(20) DEFAULT '0';


CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `openid` VARCHAR(64) NOT NULL COMMENT '用户在微信的唯一ID',
    `unionid` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '用户在开放平台的唯一标识符',
    `user_name` VARCHAR(128) NOT NULL COMMENT '用户在微信名称',
    `user_avatar` VARCHAR(512) NOT NULL COMMENT '用户在微信头像',
    `gender`  VARCHAR(16) NOT NULL DEFAULT '' COMMENT '用户性别',
    `country`  VARCHAR(32) NOT NULL DEFAULT '' COMMENT '所在国家',
    `province`  VARCHAR(32) NOT NULL DEFAULT '' COMMENT '所在省份',
    `city`  VARCHAR(32) NOT NULL DEFAULT '' COMMENT '所在城市',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '用户表';


CREATE TABLE IF NOT EXISTS `users_shares` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` VARCHAR(64) NOT NULL COMMENT '用户ID',
    `video_id` VARCHAR(63) NOT NULL DEFAULT '' COMMENT '视频ID',
    `code` VARCHAR(512) NOT NULL DEFAULT '' COMMENT '小程序码的文件地址',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '用户分享记录表';


CREATE TABLE IF NOT EXISTS `users_shares_clicks` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `video_id` VARCHAR(63) NOT NULL DEFAULT '' COMMENT '视频ID',
    `user_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '点击分享链接的用户',
    `from_user_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '生成分享链接的用户',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '用户分享点击记录表';


CREATE TABLE IF NOT EXISTS  `users_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL COMMENT '用户ID',
    `video_id` BIGINT(20) NOT NULL DEFAULT '0' COMMENT '视频ID',
    `type` INT(11) NOT NULL DEFAULT '0' COMMENT '操作类型: 1展示、2播放、3播放完成、4分享、5评论、6点赞',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '用户行为记录表';

CREATE INDEX user_type_video ON users_logs (user_id, type, video_id) USING BTREE;


CREATE TABLE IF NOT EXISTS  `users_formids` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL COMMENT '用户ID',
    `form_id` VARCHAR(64) NOT NULL DEFAULT '0' COMMENT '表单ID,用于消息推送',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '收集用户formid,用于消息推送';


CREATE TABLE IF NOT EXISTS `users_fissions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `video_id` VARCHAR(63) NOT NULL DEFAULT '' COMMENT '视频ID',
    `user_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '点击分享链接的用户',
    `from_user_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '生成分享链接的用户',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '用户裂变记录表';


CREATE TABLE IF NOT EXISTS  `videos_display_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL COMMENT '用户ID',
    `video_id` BIGINT(20) NOT NULL DEFAULT '0' COMMENT '视频ID',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '视频展示记录表';



CREATE TABLE IF NOT EXISTS  `users_stores` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL COMMENT '用户ID',
    `video_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '视频ID',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '用户收藏';


CREATE TABLE IF NOT EXISTS  `settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `online` INT(11) NOT NULL COMMENT 'API是否上线',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT 'API配置表';


CREATE TABLE IF NOT EXISTS  `messages` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `app` VARCHAR(64) NOT NULL COMMENT '小程序名称',
    `from_user_id` INT(11) NOT NULL COMMENT '来源用户ID',
    `group_id` VARCHAR(64) NOT NULL COMMENT '视频ID',
    `title` VARCHAR(1024) NOT NULL DEFAULT '' COMMENT '自定义标题，为空时取视频的标题',
    `comment` VARCHAR(1024) NOT NULL DEFAULT '' COMMENT '自定义评论，为空时取视频的热门评论',
    `send_time` VARCHAR(64) NOT NULL COMMENT '推送时间',
    `formid_level` INT(11) NOT NULL COMMENT '1所有有formid的用户;2formid数大于2的用户',
    `is_send` INT(11) NOT NULL DEFAULT '0' COMMENT '是否已发送',
    `send_member` INT(11) NOT NULL DEFAULT '0' COMMENT '成功发送的用户数',
    `active_member` INT(11) NOT NULL DEFAULT '0' COMMENT '直接激活的用户数',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '定时消息推送任务表';


CREATE TABLE IF NOT EXISTS `messages_send_detail` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `message_id` INT(11) NOT NULL COMMENT '推送消息ID',
    `from_user_id` INT(11) NOT NULL COMMENT '来源用户ID',
    `group_id` VARCHAR(64) NOT NULL COMMENT '视频ID',
    `user_id` VARCHAR(64) NOT NULL COMMENT '送达用户ID',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '定时消息推送任务送达列表';


CREATE TABLE IF NOT EXISTS `messages_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `interval` INT(11) NOT NULL COMMENT '时间间隔, 单位分钟',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '黏性用户消息推送配置';


CREATE TABLE IF NOT EXISTS `messages_tasks` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL COMMENT '推送的用户ID',
    `date` DATE COMMENT '任务日期',
    `send_time` DATETIME COMMENT '发送时间',
    `is_sended` INT(11) NOT NULL COMMENT '是否已发送: 0未发送1已发送',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '黏性用户消息推送配置';



#### 代理相关
CREATE TABLE IF NOT EXISTS `settings_promotion` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `ticket` DECIMAL(8,2) NOT NULL DEFAULT '1.0' COMMENT '代理门票支一元',
    `commission_lv1` DECIMAL(8,2) NOT NULL DEFAULT '1.0' COMMENT '一级代理佣金',
    `commission_lv2` DECIMAL(8,2) NOT NULL DEFAULT '0.5' COMMENT '二级代理佣金',
    `commission_lv3` DECIMAL(8,2) NOT NULL DEFAULT '0.3' COMMENT '三级代理佣金',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '代理相关配置';


CREATE TABLE IF NOT EXISTS `videos_promotion` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `group_id` VARCHAR(64) NOT NULL COMMENT '视频ID',
    `status` INT(11) NOT NULL COMMENT '状态：0不可用, 1可用',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '代理分享用的视频池';


CREATE TABLE IF NOT EXISTS `users_promotion_grid` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `parent_user_id` INT(11) NOT NULL COMMENT '上级用户ID',
    `level` INT(11) NOT NULL DEFAULT '1' COMMENT '代理等级，1，2，3',
    `user_id` INT(11) NOT NULL  COMMENT '用户ID',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '代理用户表分级表';

CREATE TABLE IF NOT EXISTS `users_promotion` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `parent_user_id` INT(11) NOT NULL DEFAULT 0 COMMENT '上级用户ID, 顶级用户为0',
    `user_id` INT(11) NOT NULL COMMENT '用户ID',
    `status` INT(11) NOT NULL COMMENT '状态：0不可用, 1可用',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '代理用户表';

CREATE TABLE IF NOT EXISTS `users_promotion_balance` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL COMMENT '用户ID',
    `commission` DECIMAL(11,2) NOT NULL DEFAULT '0.00' COMMENT '累计佣金',
    `commission_avail` DECIMAL(11,2) NOT NULL DEFAULT '0.00' COMMENT '可提佣金',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '代理用户佣金表';

CREATE TABLE IF NOT EXISTS `users_promotion_ticket` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL COMMENT '用户ID',
    `orderid` VARCHAR(64) NOT NULL COMMENT '支付订单ID,自行生成',
    `rel_orderid` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '支付订单ID, 微信返回',
    `amount` DECIMAL(11,2) NOT NULL DEFAULT '0.00' COMMENT '支付金额',
    `status` INT(11) NOT NULL COMMENT '状态：0支付中, 1成功, 2失败',
    `errmsg` VARCHAR(512) NOT NULL DEFAULT '' COMMENT '支付失败的消息',
    `ext` VARCHAR(1024) NOT NULL DEFAULT '' COMMENT '支付服务器返回的信息',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '代理用户支付表，一元';

CREATE TABLE IF NOT EXISTS `users_withdraw` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL COMMENT '用户ID',
    `orderid` VARCHAR(64) NOT NULL COMMENT '支付订单ID,自行生成',
    `rel_orderid` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '支付订单ID, 微信返回',
    `amount` DECIMAL(11,2) NOT NULL DEFAULT '0.00' COMMENT '支付金额',
    `status` INT(11) NOT NULL COMMENT '状态：0支付中, 1成功, 2失败',
    `errmsg` VARCHAR(512) NOT NULL DEFAULT '' COMMENT '支付失败的消息',
    `ext` VARCHAR(1024) NOT NULL DEFAULT '' COMMENT '支付服务器返回的信息',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '代理用户提现表';



CREATE TABLE IF NOT EXISTS `wechat_order` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `appid` VARCHAR(64) NOT NULL COMMENT '小程序ID',
    `mch_id` VARCHAR(64) NOT NULL COMMENT '商户号',
    `device_info` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '微信支付分配的终端设备号',
    `nonce_str` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '随机字符串，不长于32位',
    `sign` VARCHAR(256) NOT NULL DEFAULT '' COMMENT '签名',
    `sign_type` VARCHAR(32) NOT NULL DEFAULT 'MD5' COMMENT '签名类型，目前支持HMAC-SHA256和MD5，默认为MD5', 
    `result_code` VARCHAR(32) NOT NULL COMMENT '业务结果, SUCCESS/FAIL',
    `err_code` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '错误返回的信息描述',
    `err_code_des` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '错误返回的信息描述',
    `openid` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '用户在商户appid下的唯一标识',
    `is_subscribe` VARCHAR(8) NOT NULL DEFAULT '' COMMENT '用户是否关注公众账号，Y-关注，N-未关注，仅在公众账号类型支付有效',
    `trade_type` VARCHAR(16) NOT NULL DEFAULT '' COMMENT '交易类型, JSAPI、NATIVE、APP',
    `bank_type` VARCHAR(16) NOT NULL DEFAULT '' COMMENT '银行类型',
    `total_fee` INT(11) NOT NULL DEFAULT '0' COMMENT '订单总金额，单位为分',
    `settlement_total_fee` INT(11) NOT NULL DEFAULT '0' COMMENT '应结订单金额=订单金额-非充值代金券金额，应结订单金额<=订单金额',
    `fee_type` VARCHAR(8) NOT NULL DEFAULT 'CNY' COMMENT '货币类型，符合ISO4217标准的三位字母代码，默认人民币：CNY，其他值列表详见',
    `cash_fee` INT(11) NOT NULL DEFAULT '0' COMMENT '现金支付金额订单现金支付金额，详见',
    `cash_fee_type` VARCHAR(8) NOT NULL DEFAULT 'CNY' COMMENT '货币类型，符合ISO4217标准的三位字母代码，默认人民币：CNY，其他值列表详见',
    `coupon_fee` INT(11) NOT NULL DEFAULT '0' COMMENT '代金券金额<=订单金额，订单金额-代金券金额=现金支付金额',
    `coupon_count` INT(11) NOT NULL DEFAULT '0' COMMENT '代金券使用数量',
    `transaction_id` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '微信支付订单号',
    `out_trade_no` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '商户系统的订单号，与请求一致',
    `attach` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '商家数据包，原样返回',
    `time_end` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '支付完成时间，格式为yyyyMMddHHmmss',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '微信支付回调表';

