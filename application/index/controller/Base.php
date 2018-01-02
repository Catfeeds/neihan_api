<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Response;
use think\Loader;
use think\Db;
use think\Config;
use think\Log;

use app\index\model\User;
use app\index\model\UserMp;
use app\index\model\UserFission;
use app\index\model\UserPointLog;
use app\index\model\WxToken;


class Base extends Controller
{
    public function _initialize()
    {
        $request = Request::instance();
        $this->comconfig = Config::get('comconfig');

        $this->app_code = 'neihan_1';
        foreach ($this->comconfig['domain_settings'] as $key => $value) {
            if(strrpos($request->domain(), $key) !== false) {
                $this->app_code = $value;
                break;
            }
        }
        $sentry = new \Raven_Client($this->comconfig['sentry_url']);
        $this->error_handler = new \Raven_ErrorHandler($sentry);
        $this->error_handler->registerExceptionHandler();
        $this->error_handler->registerErrorHandler();
        $this->error_handler->registerShutdownFunction();
    }

    public function timestamp_url($url)
    {
        return str_replace('timestamp', strval(time()).'.'.strval(rand(10, 60)), $url);
    }

    protected function _access_token($app_code='')
    {
        try {

            if(empty($app_code)) {
                $app_code = $this->app_code;
            }
            $is_expired = true;

            $access_token = [];
            $access_token_file = './../application/extra/access_token_'.$app_code.'.txt';
            if(file_exists($access_token_file)) {
                $access_token = json_decode(file_get_contents($access_token_file), true);
            }
            if(!empty($access_token)) {
                if($access_token['expires_time'] - time() - 1000 > 0) {
                    $is_expired = false;
                }
            }

            if($is_expired) {
                $wxconfig = Config::get('wxconfig');
                $resp = curl_get($wxconfig['token_apis'][$app_code]);
                if(!empty($resp)) {
                    $access_token = json_decode($resp, true);
                    if(array_key_exists('expires_in', $access_token)) {
                        $access_token['expires_time'] = intval($access_token['expires_in']) + time();
                        file_put_contents($access_token_file, json_encode($access_token));
                    }
                }
            }

            $wxtoken = WxToken::get(['app_code' => $app_code]);
            if(empty($wxtoken)) {
                $wxtoken = new WxToken;
                $insert_data = $access_token;
                $insert_data['app_code'] = $app_code;
                $wxtoken->data($insert_data);
                $wxtoken->save();
            } else {
                if($wxtoken->expires_time < $access_token['expires_time']) {
                    $wxtoken->expires_time = $access_token['expires_time'];
                    $wxtoken->save();
                }
            }


        } catch (Exception $e) {
            $access_token = [];
        }
        return $access_token;
    }

    protected function _get_ticket($wx_token, $user_id)
    {
        $api = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$wx_token['access_token'];
        $req_data = [
            'action_name'=> 'QR_LIMIT_SCENE',
            'action_info'=> [
                'scene'=> ['scene_id'=> $user_id]
            ]
        ];
        $resp = curl_post($api, json_encode($req_data));
        $resp = json_decode($resp, true);
        return $resp;
    }


    protected function _generate_qrcode($user_id) 
    {
        $token  = $this->_access_token('neihan_mp');
        $ticket = $this->_get_ticket($token, $user_id);

        $api = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.urlencode($ticket['ticket']);
        $resp = curl_get($api);

        $code_filename = 'mp'.strval($user_id).strval(time());
        $codefile = './static/code/'.$code_filename.'.png';
        file_put_contents($codefile, $resp);

        $user = User::get($user_id);
        $usermp = UserMp::get($user->user_mp_id);


        $file = 'static/image/p4.png';
        $file_1 = substr($codefile, 2);
        $outfile = "static/code/p-".$code_filename.".jpeg";

        // 加载水印以及要加水印的图像
        $stamp = imagecreatefromjpeg($file_1);
    
        $im = imagecreatefrompng($file);

        // 设置水印图像的外边距，并且获取水印图像的尺寸
        imagecopy($im, $stamp, 160, 580, 0, 0, imagesx($stamp), imagesy($stamp));

        if(!empty($usermp->user_avatar)) {
            $stamp2 = $this->_headimgurl($usermp->user_avatar, 64, 64);
            imagecopy($im, $stamp2, 200, 1025, 0, 0, imagesx($stamp2), imagesy($stamp2));
        }
        
        $len = mb_strlen($usermp->user_name)/3*16;
        $color = imagecolorallocate($im, 0, 0, 0); // 文字颜色
        imagettftext($im, 16, 0, 330, 1045, $color, "static/sst.TTF", $usermp->user_name);

        // 输出图像并释放内存
        imagejpeg($im, $outfile, 100, NULL);
        imagedestroy($im);
        imagedestroy($stamp);

        return ['/'.$outfile, $ticket['ticket']];
    }

    protected function _headimgurl($url, $w, $h)
    {
        $src = imagecreatefromstring(curl_get($url));
        $lw = imagesx($src);//二维码图片宽度
        $lh = imagesy($src);//二维码图片高度
        $newpic = imagecreatetruecolor($w,$h);
        $sss = imagecreatetruecolor($w,$h);
        imagecopyresampled($sss, $src, 0, 0, 0, 0, $w, $w, $lw, $lh);
        imagealphablending($newpic,false);
        $transparent = imagecolorallocatealpha($newpic, 0, 0, 0, 127);
        $r=$w/2;
        for($x=0;$x<$w;$x++){
            for($y=0;$y<$h;$y++){
                $c = imagecolorat($sss,$x,$y);
                $_x = $x - $w/2;
                $_y = $y - $h/2;
                if((($_x*$_x) + ($_y*$_y)) < ($r*$r)){
                    imagesetpixel($newpic,$x,$y,$c);
                }else{
                    imagesetpixel($newpic,$x,$y,$transparent);
                }
            }
        }
        return $newpic;
    }

    protected function _add_user_point($user_id, $ptype) 
    {
        if($this->comconfig['point_lock']) {
            return '';
        }
        $user = User::get($user_id);
        if(empty($user) || $user->promotion < 3) {
            return '';
        }
        $point = $this->comconfig['pcode'][$ptype];
        $logtype = [$ptype];
        if($point['max_share']) {
            $logtype[] = $point['max_share'];
        }
        $today_point = UserPointLog::where('user_id', $user_id)
            ->where('date', date('Y-m-d'))
            ->where('type', 'in', $logtype)
            ->sum('point');
        if($point['max'] <= $today_point) {
            return '';
        }

        Db::startTrans();
        try{
            Db::execute('INSERT INTO users_point_log (`user_id`, `date`, `point`, `type`, `create_time`, `update_time`) VALUES (?, ?, ?, ?, ?, ?)',[$user_id, date('Y-m-d'), $point['point'][0], $ptype, time(), time()]);
            Db::execute('UPDATE users_promotion_balance SET point=point+?, point_avail=point_avail+? WHERE user_id=?', [intval($point['point'][0]), $point['point'][0], $user_id]);
            Db::commit();    
        } catch (\Exception $e) {
            Db::rollback();
            print_r($e);
            throw new \Exception("something error", -1024, $e);
        }
    }

    protected function _add_parent_user_point($user_id, $ptype)
    {
        $user_fission = UserFission::get(['user_id' => $user_id]);
        if(empty($user_fission)) {
            return '';
        }
        $this->_add_user_point($user_fission->from_user_id, $ptype);
    }

}
