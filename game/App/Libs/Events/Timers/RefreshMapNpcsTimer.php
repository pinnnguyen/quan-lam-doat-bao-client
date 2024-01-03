<?php

namespace App\Libs\Events\Timers;

use App\Libs\Helpers;

/**
 * 刷新地图 NPC
 *
 */
class RefreshMapNpcsTimer
{
    public static function hook()
    {

        // echo "RefreshMapNpcsTimer\r\n";
        /**
         * 获取所有的 MapNpc 对象、包括 number 、具体对象
         * 查看对象是否存在，不存在添加，是否需要更新，需要则更新
         *
         */
        $map_npcs = cache()->get('map_npcs');
        // print_r($map_npcs);
        if (!empty($map_npcs)){
            foreach ($map_npcs as $map_npc) {
                foreach ($map_npc as $map_npc_id) {
                    $npc_attrs = cache()->get($map_npc_id);
                    if ($npc_attrs) {
                        if (!$npc_attrs->isFighting and $npc_attrs->isFought) {
                            cache()->set($map_npc_id, Helpers::getMapNpcAttrs($map_npc_id));
                        }
                    } else {
                        cache()->set($map_npc_id, Helpers::getMapNpcAttrs($map_npc_id));
                    }
                }
            }
        }

    }
}
