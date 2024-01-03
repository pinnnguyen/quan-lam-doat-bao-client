<?php

namespace App\Http\Controllers\Map;

use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * Giết chóc
 */
class KillController
{
    /**
     * Giết chóc NPC
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param string        $map_npc_id
     *
     * @return bool|null
     */
    public function npc(TcpConnection $connection, Request $request, string $map_npc_id)
    {
        // 是否允许战斗
        $map = Helpers::getMapRowByMapId($request->roleRow->map_id);
        if (!$map->is_allow_fight) {
            cache()->rPush('role_messages_' . $request->roleId, '这里不允许战斗！');
            return (new IndexController())->index($connection, $request);
        }
        // 建立战场
        $npc_attrs = Helpers::getMapNpcAttrsByMapNpcId($map_npc_id);
        if (!empty($npc_attrs)) {
            cache()->set('role_battlefield_' . $request->roleId, [
                'id'         => 'role_battlefield_' . $request->roleId,
                'kind'       => 2, //1 => Luận bàn NPC, 2 => Giết chóc NPC, 3 => Luận bàn玩家, 4 => Giết chóc玩家
                'role_id'    => $request->roleId,
                'map_npc_id' => $map_npc_id,
            ]);
            $npc_row = Helpers::getNpcRowByNpcId($npc_attrs->npcId);
        }
        $footprints_come = cache()->lRange('map_footprints_for_come_' . $request->roleRow->map_id, -5, -1);
        $map_footprints = Helpers::clearMyselfFootprint($footprints_come, $request);
        return $connection->send(\cache_response($request, \view('Map/Kill/npc.twig', [
            'request'         => $request,
            'npc'             => $npc_row ?? null,
            'come_footprints' => $map_footprints,
        ])));
    }


    /**
     * Luận bàn NPC
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param string        $map_npc_id
     *
     * @return bool|null
     */
    public function duelNpc(TcpConnection $connection, Request $request, string $map_npc_id)
    {
        /**
         * 判断当前地图是否允许战斗
         *
         */
        $map = Helpers::getMapRowByMapId($request->roleRow->map_id);
        if (!$map->is_allow_fight) {
            cache()->rPush('role_messages_' . $request->roleId, 'Nơi này không cho phép chiến đấu!');
            return (new IndexController())->index($connection, $request);
        }

        /**
         * 获取 NPC 属性
         *
         */
        $npc_attrs = Helpers::getMapNpcAttrsByMapNpcId($map_npc_id);
        if (!empty($npc_attrs)) {
            if (Helpers::getPercent($npc_attrs->hp, $npc_attrs->maxHp) < 50) {
                cache()->rPush('role_messages_' . $request->roleId, $npc_attrs->name . 'Không nghĩ tiếp thu ngươi luận bàn!');
                return (new IndexController())->index($connection, $request);
            }

            $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
            if (Helpers::getPercent($role_attrs->hp, $role_attrs->maxHp) < 50) {
                cache()->rPush('role_messages_' . $request->roleId, $npc_attrs->name . 'Không nghĩ tiếp thu ngươi luận bàn!');
                return (new IndexController())->index($connection, $request);
            }
            /**
             * 建立战场
             *
             */
            cache()->set('role_battlefield_' . $request->roleId, [
                'id'         => 'role_battlefield_' . $request->roleId,
                'kind'       => 1,
                'role_id'    => $request->roleId,
                'map_npc_id' => $map_npc_id,
            ]);
            $npc_row = Helpers::getNpcRowByNpcId($npc_attrs->npcId);
        }
        $footprints_come = cache()->lRange('map_footprints_for_come_' . $request->roleRow->map_id, -5, -1);
        $map_footprints = Helpers::clearMyselfFootprint($footprints_come, $request);
        return $connection->send(\cache_response($request, \view('Map/Kill/duelNpc.twig', [
            'request'         => $request,
            'npc'             => $npc_row ?? null,
            'come_footprints' => $map_footprints,
        ])));
    }
}
