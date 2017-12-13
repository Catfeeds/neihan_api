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

Route::rule('pay/$', 'index/Pay/index');
Route::rule('pay/jump/$', 'index/Pay/jump');
Route::rule('pay/success/$', 'index/Pay/page');
Route::rule('pay/notify/$', 'index/Pay/notify');

Route::rule('api/doc/$','index/Index/doc');
Route::rule('api/msg/$', 'index/Msg/index');
Route::rule('api/msg/mp/$', 'index/Msg/mp');

Route::rule('api/settings/$', 'index/Common/index');
Route::rule('api/settings/promo/$', 'index/Common/promotion');
Route::rule('api/videos/$','index/Video/index');
Route::rule('api/videos/promo/$','index/Video/promotion');
Route::rule('api/videos/store/$','index/Video/store_list');
Route::rule('api/video/detail/$','index/Video/detail');
Route::rule('api/video/count/$','index/Video/count');
Route::rule('api/video/share/$','index/Video/share');
Route::rule('api/video/comment/$','index/Video/comment');
Route::rule('api/video/store/$','index/Video/store');
Route::rule('api/comment/count/$','index/Comment/count');
Route::rule('api/user/init/$','index/User/init');
Route::rule('api/user/update/$','index/User/update');
Route::rule('api/user/info/$','index/User/info');
Route::rule('api/user/promo/$','index/User/promotion');
Route::rule('api/user/promo/init/$','index/User/promotion_init');
Route::rule('api/user/promo/qrcode/$','index/User/promotion_qrcode');
Route::rule('api/user/prepay/$','index/User/promotion_prepay');
Route::rule('api/user/pay/callback/$','index/User/promotion_pay_callback');
Route::rule('api/user/pay/transfer/$','index/User/transfer');
Route::rule('api/user/click/sharelink/$','index/User/click_share_link');
Route::rule('api/user/share/group/$','index/User/share_group');
Route::rule('api/user/form/$','index/User/formid');
Route::rule('api/jump/$', 'index/Common/jump');


# Route::rule('api/refresh_qrcode/$', 'index/User/refresh_qrcode');
