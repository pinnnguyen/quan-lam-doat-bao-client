<?php
/**
 * @date   2022/4/30 19:10
 * @author pinerge@gmail.com
 */
declare(strict_types=1);

namespace App\Http\Controllers\Role;

use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

class ActivityController
{
    /**
     * @param \Workerman\Connection\TcpConnection $connection
     * @param \Workerman\Protocols\Http\Request   $request
     *
     * @return null|bool
     */
    public function index(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Role/Activity/index.twig', [
            'request' => $request,
        ])));
    }
}