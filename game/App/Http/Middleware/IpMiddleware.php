<?php

namespace App\Http\Middleware;

use App\Core\Configs\ServerConfig;
use Closure;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * IP 检测
 *
 */
class IpMiddleware
{
    /**
     * IP 检测中间件
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param Closure       $next
     *
     * @return mixed
     */
    public function handle(TcpConnection $connection, Request $request, Closure $next): mixed
    {
        if (ServerConfig::IP_LIMIT) {
            /**
             * 检测 IP
             *
             */
            return $next($connection, $request);
        }
        return $next($connection, $request);
    }
}