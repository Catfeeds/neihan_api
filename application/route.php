<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Route;

# Route::rule('video/get-list','index/Video/index');

Route::rule('__miss__', 'index/Error/index');

Route::rule('api/doc/$','index/Index/doc');
Route::rule('api/videos/$','index/Video/index');
Route::rule('api/video/detail/$','index/Video/detail');
Route::rule('api/video/count/$','index/Video/count');
Route::rule('api/video/share/$','index/Video/share');
Route::rule('api/video/comments/$','index/Video/comment');
Route::rule('api/comment/count/$','index/Comment/count');
Route::rule('api/user/init/$','index/User/init');
Route::rule('api/user/update/$','index/User/update');
Route::rule('api/user/info/$','index/User/info');
Route::rule('api/user/click/sharelink/$','index/User/click_share_link');
