<?php

namespace App\Http\Controllers\Func;

use App\Libs\Attrs\FlushRoleAttrs;
use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 修理
 *
 */
class FixController
{
    /**
     * 修理首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        /**
         * 获取
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `role_id` = $request->roleId AND `equipped` = 1;
SQL;

        $things = Helpers::queryFetchAll($sql);

        if (is_array($things)) {
            $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
            $_sql = '';
            foreach ($things as $key => $thing) {
                $thing->row = Helpers::getThingRowByThingId($thing->thing_id);
                if ($thing->id == $role_attrs->weaponRoleThingId and $thing->durability != $role_attrs->weaponDurability) {
                    $_sql .= <<<SQL
UPDATE `role_things` SET `durability` = $role_attrs->weaponDurability WHERE `id` = $thing->id;
SQL;

                    $thing->durability = $role_attrs->weaponDurability;
                } elseif ($thing->id == $role_attrs->clothesRoleThingId and $thing->durability != $role_attrs->clothesDurability) {
                    $_sql .= <<<SQL
UPDATE `role_things` SET `durability` = $role_attrs->clothesDurability WHERE `id` = $thing->id;
SQL;

                    $thing->durability = $role_attrs->clothesDurability;
                } elseif ($thing->id == $role_attrs->armorRoleThingId and $thing->durability != $role_attrs->armorDurability) {
                    $_sql .= <<<SQL
UPDATE `role_things` SET `durability` = $role_attrs->armorDurability WHERE `id` = $thing->id;
SQL;

                    $thing->durability = $role_attrs->armorDurability;
                } elseif ($thing->id == $role_attrs->shoesRoleThingId and $thing->durability != $role_attrs->shoesDurability) {
                    $_sql .= <<<SQL
UPDATE `role_things` SET `durability` = $role_attrs->shoesDurability WHERE `id` = $thing->id;
SQL;

                    $thing->durability = $role_attrs->shoesDurability;
                }
                if ($thing->status < 1 or $thing->durability >= $thing->row->max_durability) {
                    unset($things[$key]);
                    continue;
                }
                $thing->questionUrl = 'Func/Fix/question/' . $thing->id;
            }

            if ($_sql !== '') {
                Helpers::execSql($sql);
            }
        }

        return $connection->send(\cache_response($request, \view('Func/Fix/index.twig', [
            'request' => $request,
            'things'  => $things,
        ])));
    }


    /**
     * 询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_thing_id
     *
     * @return bool|null
     */
    public function question(TcpConnection $connection, Request $request, int $role_thing_id)
    {
        /**
         * 获取
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $thing = Helpers::queryFetchObject($sql);

        $thing->row = Helpers::getThingRowByThingId($thing->thing_id);

        $price = ($thing->row->max_durability - $thing->durability) * 25;

        return $connection->send(\cache_response($request, \view('Func/Fix/question.twig', [
            'request' => $request,
            'thing'   => $thing,
            'price'   => Helpers::getHansMoney($price),
            'fixUrl'  => 'Func/Fix/fix/' . $role_thing_id,
        ])));
    }


    /**
     * 修理
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_thing_id
     *
     * @return bool|null
     */
    public function fix(TcpConnection $connection, Request $request, int $role_thing_id)
    {
        /**
         * 获取
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $thing = Helpers::queryFetchObject($sql);

        $thing->row = Helpers::getThingRowByThingId($thing->thing_id);

        $price = ($thing->row->max_durability - $thing->durability) * 25;

        /**
         * Xem xét随身金钱
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

        $money = Helpers::queryFetchObject($sql);
        if (empty($money) or $money->number < $price) {
            return $connection->send(\cache_response($request, \view('Func/Fix/message.twig', [
                'request' => $request,
                'message' => 'Vị này đại hiệp, ngài giống như không có mang đủ ngân lượng đi!',
            ])));
        }

        /**
         * 修理
         */
        $sql = <<<SQL
UPDATE `role_things` SET `status` = `status` - 1, `durability` = {$thing->row->max_durability} WHERE `id` = $role_thing_id;
SQL;

        if ($money->number > $price) {
            $sql .= <<<SQL
UPDATE `role_things` SET `number` = `number` - $price WHERE `id` = $money->id;
SQL;

        } else {
            $sql .= <<<SQL
DELETE FROM `role_things` WHERE `id` = $money->id;
SQL;

        }
        Helpers::execSql($sql);
        FlushRoleAttrs::fromRoleEquipmentByRoleId($request->roleId);

        return $connection->send(\cache_response($request, \view('Func/Fix/fix.twig', [
            'request' => $request,
            'thing'   => $thing,
            'price'   => Helpers::getHansMoney($price),
        ])));
    }
}
