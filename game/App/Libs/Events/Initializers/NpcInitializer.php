<?php

namespace App\Libs\Events\Initializers;

use App\Libs\Objects\NpcRow;
use PDO;

/**
 * 初始化NPC
 */
class NpcInitializer
{
    public static function hook()
    {
        /**
         * 获取所有 NPC
         */
        $sql = <<<SQL
SELECT * FROM `npcs`;
SQL;

        $npcs_st = db()->query($sql);
        $npcs = $npcs_st->fetchAll(PDO::FETCH_CLASS, NpcRow::class);
        $npcs_st->closeCursor();

        /**
         * 缓存所有 NPC
         */
        cache()->set('npcs', array_column($npcs, null, 'id'));
    }
}
