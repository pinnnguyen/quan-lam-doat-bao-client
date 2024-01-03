<?php

namespace App\Core\Configs;

/**
 * Redis 缓存配置
 */
class CacheConfig
{
    /**
     * 地址
     */
    const HOST = '127.0.0.1';

    /**
     * 端口
     */
    const PORT = 6379;

    /**
     * 数据库编号 默认 0
     */
    const DATABASE_NUM = 0;
}
