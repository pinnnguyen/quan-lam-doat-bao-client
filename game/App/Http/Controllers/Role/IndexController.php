<?php

namespace App\Http\Controllers\Role;

use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 状态 选项
 *
 */
class IndexController
{
    /**
     * 首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Role/Index/index.twig', [
            'request' => $request,
        ])));
    }
}