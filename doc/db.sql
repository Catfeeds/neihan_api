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
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '用户表';


CREATE TABLE IF NOT EXISTS `users_shares` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` VARCHAR(64) NOT NULL COMMENT '用户ID',
    `code` VARCHAR(512) NOT NULL DEFAULT '' COMMENT '小程序码的文件地址',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '用户分享记录表';


CREATE TABLE IF NOT EXISTS `users_shares_clicks` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `share_id` VARCHAR(64) NOT NULL COMMENT 'users_shares表的主键',
    `user_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '点击分享链接的用户',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '用户分享点击记录表';