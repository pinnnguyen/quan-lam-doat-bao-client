<?php

namespace App\Libs\Events\Initializers;

use App\Libs\Objects\MapRow;
use PDO;

/**
 * 初始化地图
 */
class MapInitializer
{
    public static function hook()
    {
        /**
         * 获取所有地图
         */
        $sql = <<<SQL
SELECT * FROM `maps`;
SQL;

        $maps_st = db()->query($sql);
        $maps = $maps_st->fetchAll(PDO::FETCH_CLASS, MapRow::class);
        $maps_st->closeCursor();

        /**
         * 缓存所有地图
         */
        cache()->set('maps', array_column($maps, null, 'id'));
    }
}
