<?php
namespace app\index\controller;

use think\Controller;
use think\Response;

class Msg extends Controller
{
    public function index()
    {
        $data = ['c' => 0, 'm' => '', 'd' => []];
        return Response::create($data, 'json')->code(200);
    }

}
