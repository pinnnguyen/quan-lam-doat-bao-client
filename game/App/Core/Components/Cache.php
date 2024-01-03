<?php

namespace App\Core\Components;

use App\Core\Configs\CacheConfig;
use Redis;

/**
 * Cache 缓存 单例
 */
class Cache
{
    /**
     * 缓存实例 / Redis 实例
     *
     * @var Redis|null
     */
    protected static ?Redis $instance = null;


    /**
     * 获取缓存实例 单例
     *
     * @return Redis
     */
    public static function getInstance(): Redis
    {
        if (static::$instance === null) {
            static::$instance = new Redis();
            static::$instance->connect(CacheConfig::HOST, CacheConfig::PORT);
            //static::$instance->connect('/tmp/redis.sock');
            static::$instance->select(CacheConfig::DATABASE_NUM);
            static::$instance->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        }
        return static::$instance;
    }
}
