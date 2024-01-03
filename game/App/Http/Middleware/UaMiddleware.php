<?php

namespace App\Http\Middleware;

use App\Core\Configs\ServerConfig;
use App\Http\Controllers\Error\HttpController;
use Closure;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * UA 检测
 *
 */
class UaMiddleware
{
    /**
     * UA 检测中间件
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param Closure       $next
     *
     * @return mixed
     */
    public function handle(TcpConnection $connection, Request $request, Closure $next): mixed
    {
        if (ServerConfig::UA_LIMIT) {
            /**
             * 检测 UA
             */
            $user_agent = $request->header('user-agent');
            if (empty($user_agent)) {
                return (new HttpController())->badRequest($connection, $request);
            }

            /**
             * 获取 UA 信息
             */
            $request->userAgent = cache()->get('user_agent_' . $user_agent);
            if (empty($request->userAgent)) {
                return (new HttpController())->badRequest($connection, $request);
            }

            /**
             * Xem xét点击数量
             */
            $request->userAgent->today_click += 1;
            if ($request->userAgent->today_click >= $request->userAgent->everyday_click) {
                return (new HttpController())->badRequest($connection, $request);
            }

            $response = $next($connection, $request);

            cache()->set('user_agent_' . $user_agent, $request->userAgent);

            return $response;
        }
        return $next($connection, $request);
    }
}