<?php

use App\Core\Components\App;
use App\Core\Configs\FlushConfig;
use App\Core\Configs\ServerConfig;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Timer;
use Workerman\Worker;
use App\Libs\Helpers;

require_once __DIR__ . '/vendor/autoload.php';

$worker = new Worker('http://' . ServerConfig::HOST . ':' . ServerConfig::PORT);

$worker->count = ServerConfig::WORKER_NUM;
$worker::$logFile = ServerConfig::LOG_FILE;
$worker::$stdoutFile = ServerConfig::STDOUT_FILE;

$worker->onWorkerStart = function (Worker $worker) {
    $worker->name = sprintf('Worker-%02d', $worker->id + 1);
    \App\Libs\Events\Initializers\ProcessInitializer::hook();
    Timer::add(FlushConfig::KEEP_ALIVE, [\App\Libs\Events\Timers\KeepAliveTimer::class, 'hook']);
    // Timer::add(120, function () {
    //     gc_collect_cycles();
    //     gc_mem_caches();
    // });
};

$worker->onMessage = function (TcpConnection $connection, Request $request) {
    $request->startMicroTime = microtime(true);
    $next = function (TcpConnection $connection, Request $request) {
        return (new App())->run($connection, $request);
    };
    //Helpers::log_message("连接回调");
    foreach (ServerConfig::MIDDLEWARES as $middleware) {
        $next = function (TcpConnection $connection, Request $request) use ($next, $middleware) {
            //Helpers::log_message("连接回调");
            return (new $middleware)->handle($connection, $request, $next);
        };
    }
    return $next($connection, $request);
};

$worker::runAll();
