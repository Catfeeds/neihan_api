<?php
namespace app\index\controller;

use think\Controller;
use think\Response;

class Index extends Controller
{
    public function index()
    {
        $data = ['c' => 0, 'm' => '', 'd' => []];
        return Response::create($data, 'json')->code(200);
    }

    public function doc()
    {
        return $this->fetch('doc');
    }

}
