<?php

namespace App\Libs\Events\Initializers;

use App\Core\Configs\CacheConfig;
use App\Core\Configs\DBConfig;
use App\Core\Configs\ServerConfig;
use PDO;
use Redis;

/**
 * 主进程初始化
 *
 */
class MainProcessInitializer
{
    public static function hook()
    {
        date_default_timezone_set(ServerConfig::TIME_ZONE);
        $pdo = new PDO('mysql:dbname=' . DBConfig::DATABASE . ';unix_socket=/tmp/mysql.sock' . ';charset=utf8mb4',
            DBConfig::USERNAME, DBConfig::PASSWORD);
        $redis = new Redis();
        $redis->connect('/tmp/redis.sock');
        $redis->select(CacheConfig::DATABASE_NUM);
        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        $redis->flushDB();
        ConsecutiveMissionInitializer::hook($pdo, $redis);
        DeliverLetterInitializer::hook($pdo, $redis);
        EquipmentKindInitializer::hook($pdo, $redis);
        MapInitializer::hook($pdo, $redis);
        NpcInitializer::hook($pdo, $redis);
        NpcRankInitializer::hook($pdo, $redis);
        NpcRankThingInitializer::hook($pdo, $redis);
        RegionInitializer::hook($pdo, $redis);
        SectInitializer::hook($pdo, $redis);
        SettingInitializer::hook($pdo, $redis);
        ShopInitializer::hook($pdo, $redis);
        SkillInitializer::hook($pdo, $redis);
        ThingInitializer::hook($pdo, $redis);
        XinfaAttackTrickInitializer::hook($pdo, $redis);
        XinfaHpTrickInitializer::hook($pdo, $redis);
        XinfaInitializer::hook($pdo, $redis);
        XinfaMpTrickInitializer::hook($pdo, $redis);
        MapNpcInitializer::hook($pdo, $redis);
    }
}
