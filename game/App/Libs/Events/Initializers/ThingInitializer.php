<?php

namespace App\Libs\Events\Initializers;

use App\Libs\Objects\ThingRow;
use PDO;

/**
 * 初始化物品
 */
class ThingInitializer
{
    public static function hook()
    {
        /**
         * 获取所有物品
         */
        $sql = <<<SQL
SELECT * FROM `things`;
SQL;

        $things_st = db()->query($sql);
        $things = $things_st->fetchAll(PDO::FETCH_CLASS, ThingRow::class);
        $things_st->closeCursor();

        /**
         * 缓存所有物品
         */
        cache()->set('things', array_column($things, null, 'id'));
    }
}
