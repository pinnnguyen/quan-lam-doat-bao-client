<?php

namespace App\Http\Middleware;

use App\Core\Configs\GameConfig;
use App\Http\Controllers\Error\HttpController;
use App\Http\Controllers\User\LoginController;
use App\Http\Controllers\User\RegController;
use App\Libs\Helpers;
use Closure;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 路由检测
 *
 */
class RouteMiddleware
{
    /**
     * 路由检测中间件
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param Closure       $next
     *
     * @return mixed
     */
    public function handle(TcpConnection $connection, Request $request, Closure $next): mixed
    {
//        cache()->incr('ip_' . $connection->getRemoteIp());
//         $pipeline = cache()->pipeline();
//         $pipeline->incr('requests');
//         for ($i = 0; $i < 50; $i++) {
//             $pipeline->get('requests');
//         }
//         $pipeline->exec();
        //Helpers::log_message("请求path:".$request->path());
        if ($request->path() === '/' . GameConfig::PATH) {

            /**
             * Success
             *
             */
            return $next($connection, $request);
        } elseif ($request->path() === '/login' || $request->path() === '/' || $request->path() === '') {

            /**
             * 登录
             *
             */
            return (new LoginController())->index($connection, $request);
        } elseif ($request->path() === '/reg184235') {

            /**
             * 注册
             *
             */
            return (new RegController())->tip($connection, $request);
        } elseif ($request->path() === '/reg') {

            /**
             * 注册
             *
             */
            return (new RegController())->index($connection, $request);
        } elseif ($request->path() === '/c.css') {
            $css = <<<CSS
@media (min-width: 768px) {
    div {
        margin: 0 auto;
        width: 414px;
    }
}

* {
    /*word-break: break-all;*/
    font-family: Arial;
    /*text-align: justify;*/
    word-wrap: break-word;
    text-decoration: none;
}

body {
    font-size: 18px;
}

a:link, a:visited, a:active, button.link {
    color: #0000ee;
    margin: 5px;
    padding: 5px;
    border: 1px solid;
    border-radius: 5px;
    display: inline-block;
    width: fit-content;
}
}

a:hover, a:focus, button.link:focus {
    color: #ee0000;
}

button.link {
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    font-size: 18px;
}

button.link:focus {
    outline-style: dotted;
    outline-width: 0;
}
CSS;

            return $connection->send(\response($css, 200, [
                'Content-Type'  => 'text/css; charset=utf-8',
                'Cache-Control' => 'public, max-age=14400, must-revalidate',
            ]));

            /**
             * CSS
             *
             */
        } else {

            /**
             * Error
             *
             */
            return (new HttpController())->notFound($connection, $request);
        }
    }
}
