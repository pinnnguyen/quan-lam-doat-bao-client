<?php

namespace App\Core\Configs;

use App\Http\Middleware\CmdMiddleware;
use App\Http\Middleware\RouteMiddleware;

/**
 * 服务器配置
 */
class ServerConfig
{
    /**
     * 服务器名称（在 Headers 里显示，可以随意更改，不能有中文）
     *
     */
    const NAME = 'Fucker';

    /**
     * 服务器版本（在 Headers 里显示，可以随意更改，不能有中文）
     *
     */
    const VERSION = '1.0.24';

    /**
     * 服务器地址（外网：0.0.0.0，内网：127.0.0.1，ipv6：[::]）
     *
     */
    const HOST = '0.0.0.0';

    /**
     * 服务器端口（端口范围：1-65535，建议 8000 以上，不常用的端口）
     *
     */
    const PORT = '9999';

    /**
     * Worker 数量（建议为服务器核心数的 2 至 3 倍，例如：单核 2-3，双核 4-6，四核 8-12）
     *
     */
    const WORKER_NUM = 4;

    /**
     * HTTP 中间件（后面的先执行）
     *
     */
    const MIDDLEWARES = [
        CmdMiddleware::class,
        RouteMiddleware::class,
        //        UaMiddleware::class,
        //        IpMiddleware::class,
    ];

    /**
     * 全局时区
     *
     */
    const TIME_ZONE = 'Asia/Shanghai';

    /**
     * 是否启用 UA 限流，需要分配给用户专属 UA
     *
     */
    const UA_LIMIT = false;

    /**
     * 是否Mở ra IP 限制
     *
     */
    const IP_LIMIT = false;

    /**
     * 允许单个 IP 最大在线用户数量（Mở ra此项需要先启动 IP 限制）
     *
     */
    const IP_LIMIT_NUM = 3;

    /**
     * Log File 框架日志文件
     *
     */
    const LOG_FILE = __DIR__ . '/../../Runtime/.log.txt';

    /**
     * stdout 文件
     *
     */
    const STDOUT_FILE = __DIR__ . '/../../Runtime/.stdout.txt';

    /**
     * 启动时间
     *
     * @var float
     */
    public static float $startMicroTime = 0.0;
}
