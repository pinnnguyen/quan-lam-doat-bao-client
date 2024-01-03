<?php

namespace App\Http\Controllers\Role;

use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 社交
 *
 */
class ContactController
{
    /**
     * 社交首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Role/Contact/index.twig', [
            'request' => $request,
        ])));
    }
}
