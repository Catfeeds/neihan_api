<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Response;
use think\Loader;
use think\Db;
use think\Config;

use app\index\model\Setting;
use app\index\model\SettingPromotion;
use app\index\model\UserJump;


class Common extends Controller
{
    public function _initialize()
    {
        $request = Request::instance();
        $comconfig = Config::get('comconfig');

        $this->app_code = 'neihan_1';
        foreach ($comconfig['domain_settings'] as $key => $value) {
            if(strrpos($request->domain(), $key) !== false) {
                $this->app_code = $value;
                break;
            }
        }
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $comconfig = Config::get('comconfig');
        $request = Request::instance();
        $data = ['c' => 0, 'm'=> '', 'd' => []];

        $app_code = 'neihan_1';
        $version = $request->get('version');
        if(empty($version)) {
            $version = '10000';
        }
        foreach ($comconfig['domain_settings'] as $key => $value) {
            if(strrpos($request->domain(), $key) !== false) {
                $app_code = $value;
                break;
            }
        }

        $settings = New Setting;
        $result = $settings->where('app_code', $app_code)
            ->where('version', $version)
            ->limit(1)
            ->order('id', 'desc')
            ->select();

        if(!empty($result)) {
            $data['d'] = [
                'version' => $result[0]->version,
                'online' => $result[0]->online,
                'auth' => $result[0]->auth,
                'share' => $result[0]->share,
                'touch' => $result[0]->touch,
                'replay_share' => $result[0]->replay_share,
                'share_interval' => $result[0]->share_interval,
                'isTo' => intval($result[0]->auto_jump),
                'appId' => strval($result[0]->jump_appid),
                'path' => strval($result[0]->jump_appid_path),
                'extrData' => strval($result[0]->jump_extra_data)
            ];
        } else {
            $data['d'] = [
                'version' => $version,
                'online' => 0,
                'auth' => 0,
                'share' => 0,
                'touch' => 0,
                'replay_share' => 0,
                'share_interval' => 0,
                'isTo' => 0,
                'appId' => 0,
                'path' => 0,
                'extrData' => 0
            ];
        }

        return Response::create($data, 'json')->code(200);
    }

    public function promotion()
    {
        try {
            $data = ['c' => 0, 'm'=> '', 'd' => []];

            $settings = SettingPromotion::get(1);
            $data['d'] = [
                'ticket' => floatval($settings->ticket),
                'golden_ticket' => floatval($settings->golden_ticket),
                'commission_lv1' => floatval($settings->commission_lv1),
                'commission_lv2' => floatval($settings->commission_lv2),
                'commission_lv3' => floatval($settings->commission_lv3)
            ];

        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }
        return Response::create($data, 'json')->code(200);
    }

    public function jump()
    {
        try {
            $data = ['c' => 0, 'm'=> '', 'd' => []];
            $request = Request::instance();
            $user_id = $request->param('user_id');

            if(empty($user_id)) {
                $data['c'] = -1024;
                $data['m'] = 'Arg Missing';
                return Response::create($data, 'json')->code(200);
            }
            $j = new UserJump;
            $j->data([
                'user_id' => $user_id
            ]);
            $j->save();

        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }
        return Response::create($data, 'json')->code(200);
    }

    public function qrcode()
    {
        try {
            $data = ['c' => 0, 'm'=> '', 'd' => []];

            $qrcode = $this->_generate_qrcode(0);
            $data['d']['code'] = Request::instance()->domain().$qrcode[0];
        } catch (Exception $e) {
            $data = ['c' => -1024, 'm'=> $e->getMessage(), 'd' => []];
        }
        return Response::create($data, 'json')->code(200);
    }

    private function _get_ticket($wx_token, $user_id)
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

    private function _generate_qrcode($user_id) 
    {
        $token  = $this->_access_token('neihan_mp');
        $ticket = $this->_get_ticket($token, $user_id);

        $api = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.urlencode($ticket['ticket']);
        $resp = curl_get($api);

        $code_filename = 'mp'.strval($user_id).strval(time());
        $codefile = './static/code/'.$code_filename.'.png';
        file_put_contents($codefile, $resp);


        $file = 'static/image/p3.png';
        $file_1 = substr($codefile, 2);
        $outfile = "static/code/p-".$code_filename.".jpeg";

        // 加载水印以及要加水印的图像
        $stamp = imagecreatefromjpeg($file_1);
        $im = imagecreatefrompng($file);

        // 设置水印图像的外边距，并且获取水印图像的尺寸
        $marge_right = 0;
        $marge_bottom = 0;
        $sx = imagesx($stamp);
        $sy = imagesy($stamp);

        // 利用图像的宽度和水印的外边距计算位置，并且将水印复制到图像上

        imagecopy($im, $stamp, 160, 670, 0, 0, $sx, $sy);

        // 输出图像并释放内存
        imagejpeg($im, $outfile, 100, NULL);
        imagedestroy($im);

        return ['/'.$outfile, $ticket['ticket']];
    }

    private function _access_token($app_code='')
    {
        try {
            $is_expired = true;

            $access_token = [];
            if(empty($app_code)) {
                $app_code = $this->app_code;
            }
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
        } catch (Exception $e) {
            $access_token = [];
        }
        return $access_token;
    }

}
