<?php

namespace App\Http\Controllers\Map;

use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * Cho
 */
class GiveController
{
    /**
     * Cho NPC 首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $npc_id
     *
     * @return bool|null
     */
    public function npc(TcpConnection $connection, Request $request, int $npc_id)
    {
        /**
         * 可赠送的所有物品
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `role_id` = $request->roleId AND `is_body` = 0 AND `is_coma` = 0 AND `equipped` = 0;
SQL;

        $role_things = Helpers::queryFetchAll($sql);

        if (!is_array($role_things) or count($role_things) < 1) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Trên người của ngươi không có gì có thể đưa tặng đồ vật.',
            ])));
        }

        foreach ($role_things as $role_thing) {
            if ($role_thing->thing_id != 0) {
                $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
            }
            $role_thing->give_question_url = 'Map/Give/selectThingToNpc/' . $npc_id . '/' . $role_thing->id;
        }

        return $connection->send(\cache_response($request, \view('Map/Give/npc.twig', [
            'request'     => $request,
            'role_things' => $role_things,
            'npc'         => Helpers::getNpcRowByNpcId($npc_id),
        ])));
    }


    /**
     * 选择赠送 NPC 物品
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $npc_id
     * @param int           $role_thing_id
     *
     * @return bool|null
     */
    public function selectThingToNpc(TcpConnection $connection, Request $request, int $npc_id, int $role_thing_id)
    {
        /**
         * 查询赠送的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        if ($role_thing->thing_id != 0) {
            $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
        }
        if ($role_thing->is_letter) {
            /**
             * 书信
             */
            $role_thing->give_url = 'Map/Give/selectLetterToNpc/' . $npc_id . '/' . $role_thing_id;
            $role_thing->back_url = 'Map/Give/npc/' . $npc_id;
            return $connection->send(\cache_response($request, \view('Map/Give/selectLetterToNpcQuestion.twig', [
                'request'    => $request,
                'role_thing' => $role_thing,
                'npc'        => Helpers::getNpcRowByNpcId($npc_id),
            ])));
        } elseif ($role_thing->row->kind == '装备') {
            /**
             * 装备
             */
            $role_thing->give_url = 'Map/Give/selectEquipmentToNpc/' . $npc_id . '/' . $role_thing_id;
            $role_thing->back_url = 'Map/Give/npc/' . $npc_id;
            return $connection->send(\cache_response($request, \view('Map/Give/selectEquipmentToNpcQuestion.twig', [
                'request'    => $request,
                'role_thing' => $role_thing,
                'npc'        => Helpers::getNpcRowByNpcId($npc_id),
            ])));
        } elseif ($role_thing->row->kind == '书籍') {
            /**
             * 装备
             *
             */
            $role_thing->give_url = 'Map/Give/selectBookToNpc/' . $npc_id . '/' . $role_thing_id;
            $role_thing->back_url = 'Map/Give/npc/' . $npc_id;
            return $connection->send(\cache_response($request, \view('Map/Give/selectBookToNpcQuestion.twig', [
                'request'    => $request,
                'role_thing' => $role_thing,
                'npc'        => Helpers::getNpcRowByNpcId($npc_id),
            ])));
        } elseif ($role_thing->thing_id == 213) {
            /**
             * 金钱
             *
             */
            $role_thing->give_url = 'Map/Give/selectMoneyToNpc/' . $npc_id . '/' . $role_thing_id;
            $role_thing->back_url = 'Map/Give/npc/' . $npc_id;
            return $connection->send(\cache_response($request, \view('Map/Give/selectMoneyToNpcQuestion.twig', [
                'request'    => $request,
                'role_thing' => $role_thing,
                'npc'        => Helpers::getNpcRowByNpcId($npc_id),
            ])));
        } elseif (in_array($role_thing->thing_id, [215, 216, 217, 218, 219, 220, 221, 222, 245])) {
            /**
             * 箱子
             *
             */
            $role_thing->give_url = 'Map/Give/selectBoxToNpc/' . $npc_id . '/' . $role_thing_id;
            $role_thing->back_url = 'Map/Give/npc/' . $npc_id;
            return $connection->send(\cache_response($request, \view('Map/Give/selectBoxToNpcQuestion.twig', [
                'request'    => $request,
                'role_thing' => $role_thing,
                'npc'        => Helpers::getNpcRowByNpcId($npc_id),
            ])));
        }
    }


    /**
     * 赠送书籍
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $npc_id
     * @param int           $role_thing_id
     *
     * @return bool|null
     */
    public function selectBookToNpc(TcpConnection $connection, Request $request, int $npc_id, int $role_thing_id)
    {
        /**
         * 查询赠送的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);

        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
        $role_thing->back_url = 'Map/Give/npc/' . $npc_id;
        return $connection->send(\cache_response($request, \view('Map/Give/selectBookToNpc.twig', [
            'request'    => $request,
            'role_thing' => $role_thing,
            'npc'        => Helpers::getNpcRowByNpcId($npc_id),
        ])));
    }


    /**
     * 赠送 NPC 装备
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $npc_id
     * @param int           $role_thing_id
     *
     * @return bool|null
     */
    public function selectEquipmentToNpc(TcpConnection $connection, Request $request, int $npc_id, int $role_thing_id)
    {
        /**
         * 查询赠送的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);

        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
        $role_thing->back_url = 'Map/Give/npc/' . $npc_id;
        return $connection->send(\cache_response($request, \view('Map/Give/selectEquipmentToNpc.twig', [
            'request'    => $request,
            'role_thing' => $role_thing,
            'npc'        => Helpers::getNpcRowByNpcId($npc_id),
        ])));
    }


    /**
     * 赠送 NPC 金钱
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $npc_id
     * @param int           $role_thing_id
     *
     * @return bool|null
     */
    public function selectMoneyToNpc(TcpConnection $connection, Request $request, int $npc_id, int $role_thing_id)
    {
        /**
         * 查询赠送的物品
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);

        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
        $role_thing->back_url = 'Map/Give/npc/' . $npc_id;
        return $connection->send(\cache_response($request, \view('Map/Give/selectMoneyToNpc.twig', [
            'request'    => $request,
            'role_thing' => $role_thing,
            'npc'        => Helpers::getNpcRowByNpcId($npc_id),
        ])));
    }


    /**
     * 赠送 NPC 金钱
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $npc_id
     * @param int           $role_thing_id
     *
     * @return bool|null
     */
    public function selectBoxToNpc(TcpConnection $connection, Request $request, int $npc_id, int $role_thing_id)
    {
        /**
         * 查询赠送的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);

        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
        $role_thing->back_url = 'Map/Give/npc/' . $npc_id;
        return $connection->send(\cache_response($request, \view('Map/Give/selectBoxToNpc.twig', [
            'request'    => $request,
            'role_thing' => $role_thing,
            'npc'        => Helpers::getNpcRowByNpcId($npc_id),
        ])));
    }


    /**
     * 赠送 NPC 书信
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $npc_id
     * @param int           $role_thing_id
     *
     * @return bool|null
     */
    public function selectLetterToNpc(TcpConnection $connection, Request $request, int $npc_id, int $role_thing_id)
    {
        /**
         * 查询赠送的物品
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);

        /**
         * 检查是否是目标NPC
         */
        if ($role_thing->letter_npc_id != $npc_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Này không phải ngươi muốn đưa thư từ.',
            ])));
        }

        /**
         * 删除书信
         */
        $sql = <<<SQL
DELETE FROM `role_things` WHERE `id` = $role_thing_id;
SQL;


        Helpers::execSql($sql);


        /**
         * 获得奖励
         */
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->experience < 10000) {
            $role_thing->experience = Helpers::getHansExperience(200);
            $role_attrs->experience += 200;
            $role_thing->qianneng = Helpers::getHansNumber(5);
            $role_attrs->qianneng += 5;
        } elseif ($role_attrs->experience < 20000) {
            $role_thing->experience = Helpers::getHansExperience(400);
            $role_attrs->experience += 400;
            $role_thing->qianneng = Helpers::getHansNumber(10);
            $role_attrs->qianneng += 10;
        } elseif ($role_attrs->experience < 30000) {
            $role_thing->experience = Helpers::getHansExperience(600);
            $role_attrs->experience += 600;
            $role_thing->qianneng = Helpers::getHansNumber(15);
            $role_attrs->qianneng += 15;
        } else {
            $role_thing->experience = Helpers::getHansExperience(800);
            $role_attrs->experience += 800;
            $role_thing->qianneng = Helpers::getHansNumber(20);
            $role_attrs->qianneng += 20;
        }

        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);

        return $connection->send(\cache_response($request, \view('Map/Give/selectLetterToNpc.twig', [
            'request'    => $request,
            'role_thing' => $role_thing,
            'npc'        => Helpers::getNpcRowByNpcId($npc_id),
        ])));
    }
}
