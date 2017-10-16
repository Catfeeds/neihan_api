<?php
namespace app\index\controller;

use think\Response;

class Error
{
    public function index()
    {
        $data = ['c' => -1024, 'm' => 'page not found', 'd' => []];
        return Response::create($data, 'json')->code(200);
    }

}
