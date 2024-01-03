<?php

namespace App\Http\Controllers\Role;

use App\Libs\Attrs\FlushRoleAttrs;
use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * Đồ Đặc Biệt
 *
 */
class SpecialThingController
{
    /**
     * 首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        $sql = <<<SQL
SELECT * FROM `role_djs` WHERE `role_id` = $request->roleId;
SQL;

        $djs = Helpers::queryFetchAll($sql);

        if (is_array($djs)) {
            foreach ($djs as $dj) {
                $dj->row = ShopController::$djs[$dj->dj_id];
                $dj->viewUrl = 'Role/SpecialThing/view/' . $dj->id;
                if ($dj->dj_id == 7 and $dj->map_id != 0) {
                    /**
                     * 传送石
                     *
                     */
                    $map = Helpers::getMapRowByMapId($dj->map_id);
                    $dj->row['name'] = $dj->row['name'] . '【' . Helpers::getRegion($map->region_id) . '-' . $map->name . '】' . '传送';
                    $dj->viewUrl = 'Role/SpecialThing/chuansongshi/' . $dj->id;
                }
            }
        }
        return $connection->send(\cache_response($request, \view('Role/SpecialThing/index.twig', [
            'request' => $request,
            'djs'     => $djs,
        ])));
    }


    /**
     * Xem xét道具
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_dj_id
     *
     * @return bool|null
     */
    public function view(TcpConnection $connection, Request $request, int $role_dj_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_djs` WHERE `id` = $role_dj_id;
SQL;

        $dj = Helpers::queryFetchObject($sql);

        if (empty($dj)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $dj->row = ShopController::$djs[$dj->dj_id];
        if (in_array($dj->dj_id, [1, 2, 3, 4, 5, 6, 7, 17, 18, 19, 20, 21, 22, 23,])) {
            $dj->useUrl = 'Role/SpecialThing/use/' . $role_dj_id;
        }
        return $connection->send(\cache_response($request, \view('Role/SpecialThing/view.twig', [
            'request' => $request,
            'dj'      => $dj,
        ])));
    }


    /**
     * 补金石
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_dj_id
     * @param int           $role_thing_id
     *
     * @return bool|null
     */
    public function bujinshi(TcpConnection $connection, Request $request, int $role_dj_id, int $role_thing_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_djs` WHERE `id` = $role_dj_id;
SQL;

        $dj = Helpers::queryFetchObject($sql);

        if (empty($dj)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }

        $dj->row = ShopController::$djs[$dj->dj_id];
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        if (empty($role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
        $role_thing->statusString = str_repeat('*', $role_thing->status);
        $role_thing->fixUrl = 'Role/SpecialThing/bujinshiPost/' . $role_dj_id . '/' . $role_thing_id;
        return $connection->send(\cache_response($request, \view('Role/SpecialThing/bujinshiPost.twig', [
            'request'    => $request,
            'dj'         => $dj,
            'role_thing' => $role_thing,
        ])));
    }


    /**
     * 补金石
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_dj_id
     * @param int           $role_thing_id
     *
     * @return bool|null
     */
    public function bujinshiPost(TcpConnection $connection, Request $request, int $role_dj_id, int $role_thing_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_djs` WHERE `id` = $role_dj_id;
SQL;

        $dj = Helpers::queryFetchObject($sql);

        if (empty($dj)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }

        $dj->row = ShopController::$djs[$dj->dj_id];
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        if (empty($role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
        if ($dj->number <= 1) {
            $sql = <<<SQL
DELETE FROM `role_djs` WHERE `id` = $role_dj_id;
SQL;

        } else {
            $sql = <<<SQL
UPDATE `role_djs` SET `number` = `number` - 1 WHERE `id` = $role_dj_id;
SQL;

        }
        $log = '【使用道具】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') . '】使用道具【' .
            $dj->row['name'] . '】，修理装备：' . $role_thing->row->name . '（ID:' . $role_thing->id . '）。';
        $sql .= <<<SQL
UPDATE `role_things` SET `durability` = {$role_thing->row->max_durability}, `status` = 4 WHERE `id` = $role_thing_id;
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;

        Helpers::execSql($sql);
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if (in_array($role_thing_id, [
            $role_attrs->weaponRoleThingId, $role_attrs->armorRoleThingId,
            $role_attrs->clothesRoleThingId, $role_attrs->shoesRoleThingId,
        ])) {
            FlushRoleAttrs::fromRoleEquipmentByRoleId($request->roleId);
        }
        return $connection->send(\cache_response($request, \view('Role/SpecialThing/message.twig', [
            'request' => $request,
            'message' => 'Ngươi cầm lấy một khối bổ kim thạch leng keng leng keng một trận gõ,' . $role_thing->row->name . 'Trở nên mới tinh như lúc ban đầu.',
        ])));
    }


    /**
     * 补金石（精华）
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_dj_id
     * @param int           $role_thing_id
     *
     * @return bool|null
     */
    public function bujinshiJinghua(TcpConnection $connection, Request $request, int $role_dj_id, int $role_thing_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_djs` WHERE `id` = $role_dj_id;
SQL;

        $dj = Helpers::queryFetchObject($sql);

        if (empty($dj)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }

        $dj->row = ShopController::$djs[$dj->dj_id];
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        if (empty($role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
        $role_thing->statusString = str_repeat('*', $role_thing->status);
        $role_thing->fixUrl = 'Role/SpecialThing/bujinshiJinghuaPost/' . $role_dj_id . '/' . $role_thing_id;
        return $connection->send(\cache_response($request, \view('Role/SpecialThing/bujinshiJinghuaPost.twig', [
            'request'    => $request,
            'dj'         => $dj,
            'role_thing' => $role_thing,
        ])));
    }


    /**
     * 补金石（精华）
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_dj_id
     * @param int           $role_thing_id
     *
     * @return bool|null
     */
    public function bujinshiJinghuaPost(TcpConnection $connection, Request $request, int $role_dj_id, int $role_thing_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_djs` WHERE `id` = $role_dj_id;
SQL;

        $dj = Helpers::queryFetchObject($sql);

        if (empty($dj)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }

        $dj->row = ShopController::$djs[$dj->dj_id];
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        if (empty($role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
        if ($dj->times <= 1) {
            $sql = <<<SQL
DELETE FROM `role_djs` WHERE `id` = $role_dj_id;
SQL;

        } else {
            $sql = <<<SQL
UPDATE `role_djs` SET `times` = `times` - 1 WHERE `id` = $role_dj_id;
SQL;

        }
        $log = '【使用道具】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') . '】使用道具【' .
            $dj->row['name'] . '】，修理装备：' . $role_thing->row->name . '（ID:' . $role_thing->id . '）。';
        $sql .= <<<SQL
UPDATE `role_things` SET `durability` = {$role_thing->row->max_durability}, `status` = 4 WHERE `id` = $role_thing_id;
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;

        Helpers::execSql($sql);
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if (in_array($role_thing_id, [
            $role_attrs->weaponRoleThingId, $role_attrs->armorRoleThingId,
            $role_attrs->clothesRoleThingId, $role_attrs->shoesRoleThingId,
        ])) {
            FlushRoleAttrs::fromRoleEquipmentByRoleId($request->roleId);
        }
        return $connection->send(\cache_response($request, \view('Role/SpecialThing/message.twig', [
            'request' => $request,
            'message' => 'Ngươi cầm lấy một khối bổ kim thạch ( tinh hoa ) leng keng leng keng một trận gõ,' . $role_thing->row->name . 'Trở nên mới tinh như lúc ban đầu.',
        ])));
    }


    /**
     * 传送石
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_dj_id
     *
     * @return bool|null
     */
    public function chuansongshi(TcpConnection $connection, Request $request, int $role_dj_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_djs` WHERE `id` = $role_dj_id;
SQL;

        $dj = Helpers::queryFetchObject($sql);

        if (empty($dj)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }

        $dj->row = ShopController::$djs[$dj->dj_id];
        $map = Helpers::getMapRowByMapId($dj->map_id);
        return $connection->send(\cache_response($request, \view('Role/SpecialThing/chuansongshi.twig', [
            'request' => $request,
            'dj'      => $dj,
            'map'     => $map,
            'region'  => Helpers::getRegion($map->region_id),
            'cs_url'  => 'Role/SpecialThing/chuansongshiPost/' . $role_dj_id,
        ])));
    }


    /**
     * 传送石
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_dj_id
     *
     * @return bool|null
     */
    public function chuansongshiPost(TcpConnection $connection, Request $request, int $role_dj_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_djs` WHERE `id` = $role_dj_id;
SQL;

        $dj = Helpers::queryFetchObject($sql);

        if (empty($dj)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }

        $dj->row = ShopController::$djs[$dj->dj_id];
        if ($dj->times <= 1) {
            $sql = <<<SQL
DELETE FROM `role_djs` WHERE `id` = $role_dj_id;
SQL;

        } else {
            $sql = <<<SQL
UPDATE `role_djs` SET `times` = `times` - 1 WHERE `id` = $role_dj_id;
SQL;

        }
        $log = '【使用道具】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') . '】使用道具【' .
            $dj->row['name'] . '】，剩余' . $dj->times . '次。';
        $sql .= <<<SQL
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;
        Helpers::execSql($sql);
        cache()->rPush('role_messages_' . $request->roleId, 'Truyền tống thạch thượng phù văn sáng lên, một trận choáng váng, ngươi tới mục đích địa.');
        return (new \App\Http\Controllers\Map\IndexController())->delivery($connection, $request, $dj->map_id);
    }


    /**
     * 使用道具
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_dj_id
     *
     * @return bool|null
     */
    public function use(TcpConnection $connection, Request $request, int $role_dj_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_djs` WHERE `id` = $role_dj_id;
SQL;

        $dj = Helpers::queryFetchObject($sql);

        if (empty($dj)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $dj->row = ShopController::$djs[$dj->dj_id];


        //
        //
        //
        //
        //
        //
        //
        //
        //
        //


        /**
         * 双倍Nội lực丹
         *
         */
        if ($dj->dj_id == 1) {
            $sql = <<<SQL
SELECT `double_qianneng` FROM `roles` WHERE `id` = $request->roleId;
SQL;

            $double_qianneng = Helpers::queryFetchObject($sql);
            if ($double_qianneng->double_qianneng > time()) {
                $double_qianneng = $double_qianneng->double_qianneng + 3600 * 2;
            } else {
                $double_qianneng = time() + 3600 * 2;
            }
            $request->roleRow->double_qianneng = $double_qianneng;
            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
            $log = '【使用道具】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') . '】使用道具【' .
                $dj->row['name'] . '】，双倍Nội lực时间延期至：' . date('Y-m-d H:i:s', $double_qianneng) . '。';
            $sql = <<<SQL
UPDATE `roles` SET `double_qianneng` = $double_qianneng WHERE `id` = $request->roleId;
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;

            if ($dj->number < 2) {
                $sql .= <<<SQL
DELETE FROM `role_djs` WHERE `id` = $role_dj_id;
SQL;

            } else {
                $number = $dj->number - 1;
                $sql .= <<<SQL
UPDATE `role_djs` SET `number` = $number WHERE `id` = $role_dj_id;
SQL;

            }

            Helpers::execSql($sql);

            return $connection->send(\cache_response($request, \view('Role/SpecialThing/message.twig', [
                'request' => $request,
                'message' => 'Ngươi cầm lấy một viên gấp đôi tiềm năng đan ăn đi xuống, 2 tiếng đồng hồ nội chiến đấu có thể đạt được gấp đôi tiềm năng.',
            ])));
        }


        //
        //
        //
        //
        //
        //
        //
        //
        //
        //


        /**
         * 双倍Tu hành丹
         *
         */
        if ($dj->dj_id == 2) {
            $sql = <<<SQL
SELECT `double_experience` FROM `roles` WHERE `id` = $request->roleId;
SQL;

            $double_experience = Helpers::queryFetchObject($sql);
            if ($double_experience->double_experience > time()) {
                $double_experience = $double_experience->double_experience + 3600 * 2;
            } else {
                $double_experience = time() + 3600 * 2;
            }
            $request->roleRow->double_experience = $double_experience;
            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
            $log = '【使用道具】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') . '】使用道具【' .
                $dj->row['name'] . '】，双倍Tu vi时间延期至：' . date('Y-m-d H:i:s', $double_experience) . '。';
            $sql = <<<SQL
UPDATE `roles` SET `double_experience` = $double_experience WHERE `id` = $request->roleId;
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;

            if ($dj->number < 2) {
                $sql .= <<<SQL
DELETE FROM `role_djs` WHERE `id` = $role_dj_id;
SQL;

            } else {
                $number = $dj->number - 1;
                $sql .= <<<SQL
UPDATE `role_djs` SET `number` = $number WHERE `id` = $role_dj_id;
SQL;

            }

            Helpers::execSql($sql);

            return $connection->send(\cache_response($request, \view('Role/SpecialThing/message.twig', [
                'request' => $request,
                'message' => 'Ngươi cầm lấy một viên gấp đôi tu hành đan ăn đi xuống, 2 tiếng đồng hồ nội chiến đấu có thể đạt được gấp đôi tu hành.',
            ])));
        }


        //
        //
        //
        //
        //
        //
        //
        //
        //
        //


        /**
         * 双倍Nội lực丹（精华）
         *
         */
        if ($dj->dj_id == 3) {
            $sql = <<<SQL
SELECT `double_qianneng` FROM `roles` WHERE `id` = $request->roleId;
SQL;

            $double_qianneng = Helpers::queryFetchObject($sql);
            if ($double_qianneng->double_qianneng > time()) {
                $double_qianneng = $double_qianneng->double_qianneng + 3600 * 12;
            } else {
                $double_qianneng = time() + 3600 * 12;
            }
            $request->roleRow->double_qianneng = $double_qianneng;
            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
            $log = '【使用道具】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') . '】使用道具【' .
                $dj->row['name'] . '】，双倍Nội lực时间延期至：' . date('Y-m-d H:i:s', $double_qianneng) . '。';
            $sql = <<<SQL
UPDATE `roles` SET `double_qianneng` = $double_qianneng WHERE `id` = $request->roleId;
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;

            if ($dj->number < 2) {
                $sql .= <<<SQL
DELETE FROM `role_djs` WHERE `id` = $role_dj_id;
SQL;

            } else {
                $number = $dj->number - 1;
                $sql .= <<<SQL
UPDATE `role_djs` SET `number` = $number WHERE `id` = $role_dj_id;
SQL;

            }

            Helpers::execSql($sql);

            return $connection->send(\cache_response($request, \view('Role/SpecialThing/message.twig', [
                'request' => $request,
                'message' => 'Ngươi cầm lấy một viên gấp đôi tiềm năng đan ( tinh hoa ) ăn đi xuống, 12 tiếng đồng hồ nội chiến đấu có thể đạt được gấp đôi tiềm năng.',
            ])));
        }


        //
        //
        //
        //
        //
        //
        //
        //
        //
        //


        /**
         * 双倍Tu hành丹（精华）
         *
         */
        if ($dj->dj_id == 4) {
            $sql = <<<SQL
SELECT `double_experience` FROM `roles` WHERE `id` = $request->roleId;
SQL;

            $double_experience = Helpers::queryFetchObject($sql);
            if ($double_experience->double_experience > time()) {
                $double_experience = $double_experience->double_experience + 3600 * 12;
            } else {
                $double_experience = time() + 3600 * 12;
            }
            $request->roleRow->double_experience = $double_experience;
            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
            $log = '【使用道具】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') . '】使用道具【' .
                $dj->row['name'] . '】，双倍Tu vi时间延期至：' . date('Y-m-d H:i:s', $double_experience) . '。';
            $sql = <<<SQL
UPDATE `roles` SET `double_experience` = $double_experience WHERE `id` = $request->roleId;
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;

            if ($dj->number < 2) {
                $sql .= <<<SQL
DELETE FROM `role_djs` WHERE `id` = $role_dj_id;
SQL;

            } else {
                $number = $dj->number - 1;
                $sql .= <<<SQL
UPDATE `role_djs` SET `number` = $number WHERE `id` = $role_dj_id;
SQL;

            }

            Helpers::execSql($sql);

            return $connection->send(\cache_response($request, \view('Role/SpecialThing/message.twig', [
                'request' => $request,
                'message' => 'Ngươi cầm lấy một viên gấp đôi tu hành đan ( tinh hoa ) ăn đi xuống, 12 tiếng đồng hồ nội chiến đấu có thể đạt được gấp đôi tu hành.',
            ])));
        }

        //
        //
        //
        //
        //
        //
        //
        //
        //
        //

        /**
         * 补金石
         *
         */
        if ($dj->dj_id == 5) {
            /**
             * 获取装备
             *
             */
            $sql = <<<SQL
SELECT * FROM `role_things` WHERE `role_id` = $request->roleId;
SQL;

            $role_things = Helpers::queryFetchAll($sql);
            if (!is_array($role_things)) {
                return $connection->send(\cache_response($request, \view('Role/SpecialThing/message.twig', [
                    'request' => $request,
                    'message' => 'Trên người của ngươi không có trang bị.',
                ])));
            }
            foreach ($role_things as $key => $role_thing) {
                $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
                if ($role_thing->row->kind !== '装备') {
                    unset($role_things[$key]);
                }
                $role_thing->viewUrl = 'Role/SpecialThing/bujinshi/' . $role_dj_id . '/' . $role_thing->id;
            }
            if (empty($role_things)) {
                return $connection->send(\cache_response($request, \view('Role/SpecialThing/message.twig', [
                    'request' => $request,
                    'message' => 'Trên người của ngươi không có trang bị.',
                ])));
            }
            return $connection->send(\cache_response($request, \view('Role/SpecialThing/bujinshi.twig', [
                'request'     => $request,
                'role_things' => $role_things,
            ])));
        }

        //
        //
        //
        //
        //
        //
        //
        //
        //
        //


        /**
         * 补金石（精华）
         *
         */
        if ($dj->dj_id == 6) {
            /**
             * 获取装备
             *
             */
            $sql = <<<SQL
SELECT * FROM `role_things` WHERE `role_id` = $request->roleId;
SQL;

            $role_things = Helpers::queryFetchAll($sql);
            if (!is_array($role_things)) {
                return $connection->send(\cache_response($request, \view('Role/SpecialThing/message.twig', [
                    'request' => $request,
                    'message' => 'Trên người của ngươi không có trang bị.',
                ])));
            }
            foreach ($role_things as $key => $role_thing) {
                $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
                if ($role_thing->row->kind !== '装备') {
                    unset($role_things[$key]);
                }
                $role_thing->viewUrl = 'Role/SpecialThing/bujinshiJinghua/' . $role_dj_id . '/' . $role_thing->id;
            }
            if (empty($role_things)) {
                return $connection->send(\cache_response($request, \view('Role/SpecialThing/message.twig', [
                    'request' => $request,
                    'message' => 'Trên người của ngươi không có trang bị.',
                ])));
            }
            return $connection->send(\cache_response($request, \view('Role/SpecialThing/bujinshiJinghua.twig', [
                'request'     => $request,
                'role_things' => $role_things,
            ])));
        }

        //
        //
        //
        //
        //
        //
        //
        //
        //
        //


        /**
         * 传送石
         *
         */
        if ($dj->dj_id == 7) {
            /**
             * 标记地图
             *
             */
            $sql = <<<SQL
UPDATE `role_djs` SET `map_id` = {$request->roleRow->map_id} WHERE `id` = $role_dj_id;
SQL;

            Helpers::execSql($sql);
            $map = Helpers::getMapRowByMapId($request->roleRow->map_id);
            return $connection->send(\cache_response($request, \view('Role/SpecialThing/message.twig', [
                'request' => $request,
                'message' => 'Ngươi đã thành công đánh dấu trước mặt bản đồ, địa điểm 【' . Helpers::getRegion($map->region_id) . '】-【' . $map->name . '】。',
            ])));
        }

        //
        //
        //
        //
        //
        //
        //
        //
        //
        //


        /**
         * 免死金牌
         *
         */
        if ($dj->dj_id == 17) {
            $sql = <<<SQL
SELECT `no_kill`, `no_kill_times` FROM `roles` WHERE `id` = $request->roleId;
SQL;

            $no_kill = Helpers::queryFetchObject($sql);
            if ($no_kill->no_kill > time()) {
                return $connection->send(\cache_response($request, \view('Role/SpecialThing/message.twig', [
                    'request' => $request,
                    'message' => 'Ngươi miễn tử kim bài hiệu quả còn ở, xin đừng lặp lại sử dụng.',
                ])));
            }
            if ($no_kill->no_kill - 3600 * 2 < strtotime(date('Y-m-d', time()))) {
                $no_kill_times = 0;
            } else {
                $no_kill_times = $no_kill->no_kill_times;
            }
            if ($no_kill_times >= 3) {
                return $connection->send(\cache_response($request, \view('Role/SpecialThing/message.twig', [
                    'request' => $request,
                    'message' => 'Ngươi hôm nay đã tích lũy sử dụng miễn tử kim bài 3 thứ, không thể lại sử dụng.',
                ])));
            }
            $no_kill = time() + 3600 * 2;
            $no_kill_times += 1;

            $request->roleRow->no_kill = $no_kill;
            $request->roleRow->no_kill_times = $no_kill_times;
            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
            $log = '【使用道具】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') . '】使用道具【' .
                $dj->row['name'] . '】，免死金牌时间延期至：' . date('Y-m-d H:i:s', $no_kill) . '。';
            $sql = <<<SQL
UPDATE `roles` SET `no_kill` = $no_kill, `no_kill_times` = $no_kill_times WHERE `id` = $request->roleId;
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;

            if ($dj->number < 2) {
                $sql .= <<<SQL
DELETE FROM `role_djs` WHERE `id` = $role_dj_id;
SQL;

            } else {
                $number = $dj->number - 1;
                $sql .= <<<SQL
UPDATE `role_djs` SET `number` = $number WHERE `id` = $role_dj_id;
SQL;

            }

            Helpers::execSql($sql);
            return $connection->send(\cache_response($request, \view('Role/SpecialThing/message.twig', [
                'request' => $request,
                'message' => 'Ngươi sử dụng miễn tử kim bài, 2 tiếng đồng hồ nội có thể miễn tao giết chóc.',
            ])));
        }

        //
        //
        //
        //
        //
        //
        //
        //
        //
        //


        /**
         * 双倍心法丹
         *
         */
        if ($dj->dj_id == 18) {
            $sql = <<<SQL
SELECT `double_xinfa` FROM `roles` WHERE `id` = $request->roleId;
SQL;

            $double_xinfa = Helpers::queryFetchObject($sql);
            if ($double_xinfa->double_xinfa > time()) {
                $double_xinfa = $double_xinfa->double_xinfa + 3600 * 2;
            } else {
                $double_xinfa = time() + 3600 * 2;
            }
            $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
            $request->roleRow->double_xinfa = $double_xinfa;
            $role_attrs->double_xinfa = $double_xinfa;
            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
            Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
            $log = '【使用道具】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') . '】使用道具【' .
                $dj->row['name'] . '】，双倍心法时间延期至：' . date('Y-m-d H:i:s', $double_xinfa) . '。';
            $sql = <<<SQL
UPDATE `roles` SET `double_xinfa` = $double_xinfa WHERE `id` = $request->roleId;
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;

            if ($dj->number < 2) {
                $sql .= <<<SQL
DELETE FROM `role_djs` WHERE `id` = $role_dj_id;
SQL;

            } else {
                $number = $dj->number - 1;
                $sql .= <<<SQL
UPDATE `role_djs` SET `number` = $number WHERE `id` = $role_dj_id;
SQL;

            }

            Helpers::execSql($sql);
            return $connection->send(\cache_response($request, \view('Role/SpecialThing/message.twig', [
                'request' => $request,
                'message' => 'Ngươi cầm lấy một viên gấp đôi tâm pháp đan ăn đi xuống, 2 tiếng đồng hồ nội chiến đấu có thể đạt được gấp đôi tâm pháp kinh nghiệm.',
            ])));
        }

        //
        //
        //
        //
        //
        //
        //
        //
        //
        //


        /**
         * 双倍心法丹（精华）
         *
         */
        if ($dj->dj_id == 19) {
            $sql = <<<SQL
SELECT `double_xinfa` FROM `roles` WHERE `id` = $request->roleId;
SQL;

            $double_xinfa = Helpers::queryFetchObject($sql);
            if ($double_xinfa->double_xinfa > time()) {
                $double_xinfa = $double_xinfa->double_xinfa + 3600 * 12;
            } else {
                $double_xinfa = time() + 3600 * 12;
            }
            $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
            $request->roleRow->double_xinfa = $double_xinfa;
            $role_attrs->double_xinfa = $double_xinfa;
            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
            Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
            $log = '【使用道具】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') . '】使用道具【' .
                $dj->row['name'] . '】，双倍心法时间延期至：' . date('Y-m-d H:i:s', $double_xinfa) . '。';
            $sql = <<<SQL
UPDATE `roles` SET `double_xinfa` = $double_xinfa WHERE `id` = $request->roleId;
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;

            if ($dj->number < 2) {
                $sql .= <<<SQL
DELETE FROM `role_djs` WHERE `id` = $role_dj_id;
SQL;

            } else {
                $number = $dj->number - 1;
                $sql .= <<<SQL
UPDATE `role_djs` SET `number` = $number WHERE `id` = $role_dj_id;
SQL;

            }

            Helpers::execSql($sql);
            return $connection->send(\cache_response($request, \view('Role/SpecialThing/message.twig', [
                'request' => $request,
                'message' => 'Ngươi cầm lấy một viên gấp đôi tâm pháp đan ( tinh hoa ) ăn đi xuống, 12 tiếng đồng hồ nội chiến đấu có thể đạt được gấp đôi tâm pháp kinh nghiệm.',
            ])));
        }

        //
        //
        //
        //
        //
        //
        //
        //
        //
        //


        /**
         * 三倍心法丹
         *
         */
        if ($dj->dj_id == 20) {
            $sql = <<<SQL
SELECT `triple_xinfa` FROM `roles` WHERE `id` = $request->roleId;
SQL;

            $triple_xinfa = Helpers::queryFetchObject($sql);
            if ($triple_xinfa->triple_xinfa > time()) {
                $triple_xinfa = $triple_xinfa->triple_xinfa + 3600 * 2;
            } else {
                $triple_xinfa = time() + 3600 * 2;
            }
            $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
            $request->roleRow->triple_xinfa = $triple_xinfa;
            $role_attrs->triple_xinfa = $triple_xinfa;
            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
            Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
            $log = '【使用道具】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') . '】使用道具【' .
                $dj->row['name'] . '】，三倍心法时间延期至：' . date('Y-m-d H:i:s', $triple_xinfa) . '。';
            $sql = <<<SQL
UPDATE `roles` SET `triple_xinfa` = $triple_xinfa WHERE `id` = $request->roleId;
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;

            if ($dj->number < 2) {
                $sql .= <<<SQL
DELETE FROM `role_djs` WHERE `id` = $role_dj_id;
SQL;

            } else {
                $number = $dj->number - 1;
                $sql .= <<<SQL
UPDATE `role_djs` SET `number` = $number WHERE `id` = $role_dj_id;
SQL;

            }

            Helpers::execSql($sql);
            return $connection->send(\cache_response($request, \view('Role/SpecialThing/message.twig', [
                'request' => $request,
                'message' => 'Ngươi cầm lấy một viên gấp ba tâm pháp đan ăn đi xuống, 2 tiếng đồng hồ nội chiến đấu có thể đạt được gấp ba tâm pháp kinh nghiệm.',
            ])));
        }

        //
        //
        //
        //
        //
        //
        //
        //
        //
        //


        /**
         * 千年人参
         *
         */
        if ($dj->dj_id == 21) {
            $sql = <<<SQL
SELECT `renshen` FROM `roles` WHERE `id` = $request->roleId;
SQL;

            $renshen = Helpers::queryFetchObject($sql);
            if ($renshen->renshen >= 4) {
                return $connection->send(\cache_response($request, \view('Role/SpecialThing/message.twig', [
                    'request' => $request,
                    'message' => 'Ngươi dùng ngàn năm nhân sâm số lần đã có 4 thứ, nó đối với ngươi đã không có hiệu quả.',
                ])));
            }
            $renshen = $renshen->renshen + 1;
            $request->roleRow->renshen = $renshen;
            $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
            $role_attrs->renshen = $renshen;
            Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
            $log = '【使用道具】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') . '】使用道具【' .
                $dj->row['name'] . '】，千年人参Sử dụng次数提升至' . $renshen . '次。';
            $sql = <<<SQL
UPDATE `roles` SET `renshen` = $renshen WHERE `id` = $request->roleId;
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;

            if ($dj->number < 2) {
                $sql .= <<<SQL
DELETE FROM `role_djs` WHERE `id` = $role_dj_id;
SQL;

            } else {
                $number = $dj->number - 1;
                $sql .= <<<SQL
UPDATE `role_djs` SET `number` = $number WHERE `id` = $role_dj_id;
SQL;

            }

            Helpers::execSql($sql);

            return $connection->send(\cache_response($request, \view('Role/SpecialThing/message.twig', [
                'request' => $request,
                'message' => 'Ngươi cầm lấy một cây ngàn năm nhân sâm ăn đi xuống, thoát ly chiến đấu sau trạng thái khôi phục vĩnh cửu tăng lên 10%。',
            ])));
        }

        //
        //
        //
        //
        //
        //
        //
        //
        //
        //

        if ($dj->dj_id == 22) {
            $sql = <<<SQL
SELECT `triple_qianneng` FROM `roles` WHERE `id` = $request->roleId;
SQL;

            $triple_qianneng = Helpers::queryFetchObject($sql);
            if ($triple_qianneng->triple_qianneng > time()) {
                $triple_qianneng = $triple_qianneng->triple_qianneng + 3600 * 2;
            } else {
                $triple_qianneng = time() + 3600 * 2;
            }
            $request->roleRow->triple_qianneng = $triple_qianneng;
            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
            $log = '【使用道具】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') . '】使用道具【' .
                $dj->row['name'] . '】，双倍Nội lực时间延期至：' . date('Y-m-d H:i:s', $triple_qianneng) . '。';
            $sql = <<<SQL
UPDATE `roles` SET `triple_qianneng` = $triple_qianneng WHERE `id` = $request->roleId;
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;

            if ($dj->number < 2) {
                $sql .= <<<SQL
DELETE FROM `role_djs` WHERE `id` = $role_dj_id;
SQL;

            } else {
                $number = $dj->number - 1;
                $sql .= <<<SQL
UPDATE `role_djs` SET `number` = $number WHERE `id` = $role_dj_id;
SQL;

            }

            Helpers::execSql($sql);

            return $connection->send(\cache_response($request, \view('Role/SpecialThing/message.twig', [
                'request' => $request,
                'message' => 'Ngươi cầm lấy một viên gấp ba tiềm năng đan ăn đi xuống, 2 tiếng đồng hồ nội chiến đấu có thể đạt được gấp ba tiềm năng.',
            ])));
        }


        //
        //
        //
        //
        //
        //
        //
        //
        //
        //


        /**
         * 三倍Tu hành丹
         *
         */
        if ($dj->dj_id == 23) {
            $sql = <<<SQL
SELECT `triple_experience` FROM `roles` WHERE `id` = $request->roleId;
SQL;

            $triple_experience = Helpers::queryFetchObject($sql);
            if ($triple_experience->triple_experience > time()) {
                $triple_experience = $triple_experience->triple_experience + 3600 * 2;
            } else {
                $triple_experience = time() + 3600 * 2;
            }
            $request->roleRow->triple_experience = $triple_experience;
            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
            $log = '【使用道具】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') . '】使用道具【' .
                $dj->row['name'] . '】，双倍Tu vi时间延期至：' . date('Y-m-d H:i:s', $triple_experience) . '。';
            $sql = <<<SQL
UPDATE `roles` SET `triple_experience` = $triple_experience WHERE `id` = $request->roleId;
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;

            if ($dj->number < 2) {
                $sql .= <<<SQL
DELETE FROM `role_djs` WHERE `id` = $role_dj_id;
SQL;

            } else {
                $number = $dj->number - 1;
                $sql .= <<<SQL
UPDATE `role_djs` SET `number` = $number WHERE `id` = $role_dj_id;
SQL;

            }

            Helpers::execSql($sql);

            return $connection->send(\cache_response($request, \view('Role/SpecialThing/message.twig', [
                'request' => $request,
                'message' => 'Ngươi cầm lấy một viên gấp ba tu hành đan ăn đi xuống, 2 tiếng đồng hồ nội chiến đấu có thể đạt được gấp ba tu hành.',
            ])));
        }

        //
        //
        //

        return $connection->send(\cache_response($request, \view('Role/SpecialThing/message.twig', [
            'request' => $request,
            'message' => 'Tạm thời vô pháp sử dụng',
        ])));
    }
}
