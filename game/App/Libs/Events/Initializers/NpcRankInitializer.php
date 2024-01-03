<?php

namespace App\Libs\Events\Initializers;

/**
 * 初始化NPC阶层
 */
class NpcRankInitializer
{
    public static function hook()
    {
        /**
         * 获取所有NPC阶层
         */
        $sql = <<<SQL
SELECT * FROM `npc_ranks`;
SQL;

        $npc_ranks_st = db()->query($sql);
        $npc_ranks = $npc_ranks_st->fetchAll(\PDO::FETCH_ASSOC);
        $npc_ranks_st->closeCursor();

        cache()->set('npc_ranks', array_column($npc_ranks, 'name', 'id'));
    }
}
