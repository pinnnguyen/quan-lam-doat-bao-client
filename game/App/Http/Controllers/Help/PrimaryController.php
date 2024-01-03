<?php

namespace App\Http\Controllers\Help;

use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 新手教入门程
 */
class PrimaryController
{
    /**
     * 入门教程首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Help/Primary/index.twig', [
            'request' => $request,
        ])));
    }


    /**
     * 入门教程详细内容
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $topic
     * @param int           $number
     *
     * @return bool|null
     */
    public function description(TcpConnection $connection, Request $request, int $topic, int $number)
    {
        return $connection->send(\cache_response($request, \view('Help/Primary/' . $topic . '/' . $number . '.twig', [
            'request' => $request,
        ])));
    }
}
