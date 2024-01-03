<?php

namespace App\Http\Controllers\Help;

use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 帮助首页
 */
class IndexController
{
    /**
     * 帮助首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Help/Index/index.twig', [
            'request' => $request,
        ])));
    }


    /**
     * 各帮助Một mô tả chi tiết
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     */
    public function description(TcpConnection $connection, Request $request, int $number)
    {
        return $connection->send(\cache_response($request, \view('Help/Index/description/' . $number . '.twig', [
            'request' => $request,
        ])));
    }
}
