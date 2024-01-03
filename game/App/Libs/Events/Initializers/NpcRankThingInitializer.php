<?php

namespace App\Libs\Events\Initializers;

/**
 * NPC 阶层掉落物品初始化
 */
class NpcRankThingInitializer
{
    public static function hook()
    {
        /**
         * 获取所有商店
         */
        $sql = <<<SQL
SELECT `id` FROM `npc_ranks`;
SQL;

        $npc_ranks_st = db()->query($sql);
        $npc_ranks = $npc_ranks_st->fetchAll(\PDO::FETCH_ASSOC);
        $npc_ranks_st->closeCursor();
        $npc_ranks = array_column($npc_ranks, 'id', 'id');

        /**
         * 获取所有商店售卖物品
         */
        $sql = <<<SQL
SELECT `npc_rank_id`, `thing_id` FROM `npc_rank_things`;
SQL;

        $npc_rank_things_st = db()->query($sql);
        $npc_rank_things = $npc_rank_things_st->fetchAll(\PDO::FETCH_ASSOC);
        $npc_rank_things_st->closeCursor();

        $cache_npc_rank_things = [];
        foreach ($npc_rank_things as $npc_rank_thing) {
            if (in_array($npc_rank_thing['npc_rank_id'], $npc_ranks)) {
                $cache_npc_rank_things[$npc_rank_thing['npc_rank_id']][] = $npc_rank_thing['thing_id'];
            }
        }

        cache()->set('npc_rank_things', $cache_npc_rank_things);
    }
}
