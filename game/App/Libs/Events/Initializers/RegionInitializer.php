<?php

namespace App\Libs\Events\Initializers;

use App\Libs\Helpers;

/**
 * 地区初始化
 */
class RegionInitializer
{
    public static function hook()
    {
        /**
         * 获取所有 地区
         */
        $sql = <<<SQL
SELECT * FROM `regions`;
SQL;

        $npcs = Helpers::queryFetchAll($sql);

        /**
         * 缓存所有 NPC
         */
        cache()->set('regions', array_column($npcs, 'name', 'id'));
    }
}
