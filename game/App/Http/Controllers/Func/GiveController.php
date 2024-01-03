<?php


namespace App\Http\Controllers\Func;


use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;


/**
 * Cho
 * 1 物品     2 箱子     3 药    5 金钱
 *
 */
class GiveController
{
    public function start(TcpConnection $connection, Request $request, int $o_role_id)
    {
        /**
         * Xem xét玩家是否在线
         *
         */
        $o_role_row = Helpers::getRoleRowByRoleId($o_role_id);
        if (empty($o_role_row)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người bên kia đang offline',
            ])));
        }
        if ($o_role_row->map_id !== $request->roleRow->map_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Đối phương đã rời đi',
            ])));
        }

        $gives = [];

        /**
         * 获取可以的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` > 0 AND `equipped` = 0;
SQL;

        $role_things = Helpers::queryFetchAll($sql);
        if (is_array($role_things)) {
            foreach ($role_things as $role_thing) {
                $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
                if ($role_thing->row->kind === '装备' or $role_thing->row->kind === '书籍') {
                    $gives[] = [
                        'name' => '一' . $role_thing->row->unit . $role_thing->row->name,
                        'url' => 'Func/Give/thingQuestion/' . $o_role_id . '/' . $role_thing->id,
                    ];
                } elseif (in_array($role_thing->thing_id, [215, 216, 217, 218, 219, 220, 221, 222, 245])) {
                    $gives[] = [
                        'name' => Helpers::getHansNumber($role_thing->number) . $role_thing->row->unit . $role_thing->row->name,
                        'url' => 'Func/Give/boxQuestion/' . $o_role_id . '/' . $role_thing->id,
                    ];
                } elseif ($role_thing->thing_id == 213) {
                    $gives[] = [
                        'name' => Helpers::getHansMoney($role_thing->number),
                        'url' => 'Func/Give/moneyQuestion/' . $o_role_id . '/' . $role_thing->id,
                    ];
                }
            }
        }

        /**
         * 获取可以的药
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_drugs` WHERE `role_id` = $request->roleId;
SQL;

        $role_drugs = Helpers::queryFetchAll($sql);
        if (is_array($role_drugs)) {
            foreach ($role_drugs as $role_drug) {
                $role_drug->row = Helpers::getThingRowByThingId($role_drug->thing_id);
                $gives[] = [
                    'name' => Helpers::getHansNumber($role_drug->number) . $role_drug->row->unit . $role_drug->row->name,
                    'url' => 'Func/Give/drugQuestion/' . $o_role_id . '/' . $role_drug->id,
                ];
            }
        }
        return $connection->send(\cache_response($request, \view('Func/Give/start.twig', [
            'request' => $request,
            'o_role_row' => $o_role_row,
            'gives' => $gives,
        ])));
    }


    /**
     * 赠送物品
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     * @param int $role_thing_id
     *
     * @return bool|null
     */
    public function thingQuestion(TcpConnection $connection, Request $request, int $o_role_id, int $role_thing_id)
    {
        /**
         * Xem xét玩家是否在线
         *
         */
        $o_role_row = Helpers::getRoleRowByRoleId($o_role_id);
        if (empty($o_role_row)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người bên kia đang offline',
            ])));
        }
        if ($o_role_row->map_id !== $request->roleRow->map_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Đối phương đã rời đi',
            ])));
        }

        /**
         * 获取可以的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id AND `role_id` = $request->roleId;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        if (!is_object($role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Mục này không còn thuộc về bạn',
            ])));
        }
        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);

        return $connection->send(\cache_response($request, \view('Func/Give/thingQuestion.twig', [
            'request' => $request,
            'back_url' => 'Func/Give/start/' . $o_role_id,
            'post_url' => 'Func/Give/thingPost/' . $o_role_id . '/' . $role_thing_id,
            'o_role_row' => $o_role_row,
            'role_thing' => $role_thing,
        ])));
    }


    /**
     * 物品 提交
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     * @param int $role_thing_id
     *
     * @return bool|null
     */
    public function thingPost(TcpConnection $connection, Request $request, int $o_role_id, int $role_thing_id)
    {
        /**
         * Xem xét玩家是否在线
         *
         */
        $o_role_row = Helpers::getRoleRowByRoleId($o_role_id);
        if (empty($o_role_row)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người bên kia đang offline',
            ])));
        }
        if ($o_role_row->map_id !== $request->roleRow->map_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Đối phương đã rời đi',
            ])));
        }

        /**
         * 获取物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id AND `role_id` = $request->roleId;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        if (!is_object($role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Mục này không còn thuộc về bạn',
            ])));
        }
        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
        cache()->set('role_give_' . $request->roleId, [
            'to' => $o_role_row->id,
            'kind' => 1,
            'id' => intval($role_thing->id),
            'number' => 1,
        ]);
        cache()->rPush('role_broadcast_' . $o_role_row->id, [
            'kind' => 9,
            'content' => $request->roleRow->name . ' muốn tặng một cái làm quà ' . $role_thing->row->unit . $role_thing->row->name . ' đây rồi, bạn có chấp nhận nó không?',
            'view_url' => 'Func/Give/viewThing/' . $request->roleId . '/' . $role_thing->id,
            'consent_url' => 'Func/Give/consentThing/' . $request->roleId . '/' . $role_thing->id,
            'refuse_url' => 'Func/Give/refuse/' . $request->roleId,
        ]);
        return $connection->send(\cache_response($request, \view('Func/Give/thingPost.twig', [
            'request' => $request,
            'o_role_row' => $o_role_row,
            'role_thing' => $role_thing,
        ])));
    }


    /**
     * Xem xét vật phẩm
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     * @param int $o_role_thing_id
     *
     * @return bool|null
     */
    public function viewThing(TcpConnection $connection, Request $request, int $o_role_id, int $o_role_thing_id)
    {
        /**
         * Xem xét玩家是否在线
         *
         */
        $o_role_row = Helpers::getRoleRowByRoleId($o_role_id);
        if (empty($o_role_row)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người bên kia đang offline',
            ])));
        }
        if ($o_role_row->map_id !== $request->roleRow->map_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Đối phương đã rời đi',
            ])));
        }

        /**
         * Xem xét对方状态
         *
         */
        $give = cache()->get('role_give_' . $o_role_id);
        if ($give['to'] !== $request->roleId or $give['kind'] !== 1 or $give['id'] !== $o_role_thing_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Quà tặng đã bị bên kia hủy',
            ])));
        }

        /**
         * 获取物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $o_role_thing_id AND `role_id` = $o_role_id;
SQL;

        $o_role_thing = Helpers::queryFetchObject($sql);
        if (!is_object($o_role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Vật phẩm quà tặng đã biến mất',
            ])));
        }
        $o_role_thing->row = Helpers::getThingRowByThingId($o_role_thing->thing_id);
        if ($o_role_thing->row->kind === '装备') {
            $o_role_thing->statusString = str_repeat('*', $o_role_thing->status);
        }
        return $connection->send(\cache_response($request, \view('Func/Give/viewThing.twig', [
            'request' => $request,
            'o_role_row' => $o_role_row,
            'o_role_thing' => $o_role_thing,
            'consent_url' => 'Func/Give/consentThing/' . $o_role_id . '/' . $o_role_thing_id,
            'refuse_url' => 'Func/Give/refuse/' . $o_role_id,
        ])));
    }


    /**
     * 物品 同意
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     * @param int $o_role_thing_id
     *
     * @return bool|null
     */
    public function consentThing(TcpConnection $connection, Request $request, int $o_role_id, int $o_role_thing_id)
    {
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->weight >= 100000000) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ba lô đã đầy và không thể nhận',
            ])));
        }
        /**
         * Xem xét玩家是否在线
         *
         */
        $o_role_row = Helpers::getRoleRowByRoleId($o_role_id);
        if (empty($o_role_row)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người bên kia đang offline',
            ])));
        }
        if ($o_role_row->map_id !== $request->roleRow->map_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Đối phương đã rời đi',
            ])));
        }

        /**
         * Xem xét对方状态
         *
         */
        $give = cache()->get('role_give_' . $o_role_id);
        if ($give['to'] !== $request->roleId or $give['kind'] !== 1 or $give['id'] !== $o_role_thing_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Quà tặng đã bị bên kia hủy',
            ])));
        }

        /**
         * 获取可以的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $o_role_thing_id AND `role_id` = $o_role_id;
SQL;

        $o_role_thing = Helpers::queryFetchObject($sql);
        if (!is_object($o_role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Các vật phẩm quà tặng đã biến mất',
            ])));
        }

        /**
         * 再次Xác nhận赠与状态
         *
         */
        $give_confirm = cache()->get('role_give_' . $o_role_id);
        if ($give['to'] !== $give_confirm['to'] or $give['kind'] !== $give_confirm['kind']
            or $give['id'] !== $give_confirm['id'] or $give['number'] !== $give_confirm['number']) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Quà tặng đã bị bên kia hủy',
            ])));
        }

        /**
         * 转移物品、财产
         */

        $sql = <<<SQL
UPDATE `role_things` SET `role_id` = $request->roleId WHERE `id` = $o_role_thing_id;
SQL;

        Helpers::execSql($sql);
        cache()->rPush('role_broadcast_' . $o_role_id, [
            'kind' => 6,
            'content' => $request->roleRow->name . 'Đã nhận quà của bạn.',
        ]);
        cache()->set('role_flush_weight_' . $o_role_id, true);
        $o_role_thing->row = Helpers::getThingRowByThingId($o_role_thing->thing_id);

        loglog(LOG_TRANSACTIONS_GIVES, '赠与物品', [
            '赠与玩家' => $o_role_row->name,
            '受赠玩家' => $request->roleRow->name,
            '物品' => $o_role_thing->row->name,
        ]);

        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => $o_role_row->name . ' tặng bạn một cái ' . $o_role_thing->row->unit . $o_role_thing->row->name . ',thành công.',
        ])));
    }


    /**
     * 箱子
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     * @param int $role_thing_id
     *
     * @return bool|null
     */
    public function boxQuestion(TcpConnection $connection, Request $request, int $o_role_id, int $role_thing_id)
    {
        /**
         * Xem xét玩家是否在线
         *
         */
        $o_role_row = Helpers::getRoleRowByRoleId($o_role_id);
        if (empty($o_role_row)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người bên kia đang offline',
            ])));
        }
        if ($o_role_row->map_id !== $request->roleRow->map_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Đối phương đã rời đi',
            ])));
        }

        /**
         * 获取可以的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id AND `role_id` = $request->roleId;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        if (!is_object($role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Mục này không còn thuộc về bạn',
            ])));
        }
        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
        return $connection->send(\cache_response($request, \view('Func/Give/boxQuestion.twig', [
            'request' => $request,
            'back_url' => 'Func/Give/start/' . $o_role_id,
            'post_url' => 'Func/Give/boxPost/' . $o_role_id . '/' . $role_thing_id,
            'o_role_row' => $o_role_row,
            'role_thing' => $role_thing,
        ])));
    }


    /**
     * 箱子
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     * @param int $role_thing_id
     *
     * @return bool|null
     */
    public function boxPost(TcpConnection $connection, Request $request, int $o_role_id, int $role_thing_id)
    {
        if (strtoupper($request->method()) !== 'POST') {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $number = $request->post('number');
        if (!is_numeric($number)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $number = intval($number);
        if ($number < 1 or $number > 10000000000) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        /**
         * Xem xét玩家是否在线
         *
         */
        $o_role_row = Helpers::getRoleRowByRoleId($o_role_id);
        if (empty($o_role_row)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người bên kia đang offline',
            ])));
        }
        if ($o_role_row->map_id !== $request->roleRow->map_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Đối phương đã rời đi',
            ])));
        }

        /**
         * 获取可以的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id AND `role_id` = $request->roleId;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        if (!is_object($role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Mục này không còn thuộc về bạn',
            ])));
        }
        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
        if ($role_thing->number < $number) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'bạn không có đủ' . $role_thing->row->name . '。',
            ])));
        }
        $md5 = md5(microtime(true));
        cache()->set('role_give_' . $request->roleId, [
            'to' => $o_role_row->id,
            'kind' => 2,
            'id' => intval($role_thing->id),
            'number' => $number,
            'md5' => $md5,
        ]);
        cache()->rPush('role_broadcast_' . $o_role_row->id, [
            'kind' => 9,
            'content' => $request->roleRow->name . ' muốn tặng đi ' . Helpers::getHansNumber($number) . $role_thing->row->unit . $role_thing->row->name . 'Đây rồi, bạn có chấp nhận nó không?',
            'view_url' => 'Func/Give/viewBox/' . $request->roleId . '/' . $role_thing->id . '/' . $md5,
            'consent_url' => 'Func/Give/consentBox/' . $request->roleId . '/' . $role_thing->id . '/' . $md5,
            'refuse_url' => 'Func/Give/refuse/' . $request->roleId,
        ]);
        return $connection->send(\cache_response($request, \view('Func/Give/boxPost.twig', [
            'request' => $request,
            'o_role_row' => $o_role_row,
            'role_thing' => $role_thing,
            'number' => Helpers::getHansNumber($number),
        ])));
    }


    /**
     * Xem xét箱子
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     * @param int $o_role_thing_id
     * @param string $md5
     *
     * @return bool|null
     */
    public function viewBox(TcpConnection $connection, Request $request, int $o_role_id, int $o_role_thing_id, string $md5)
    {
        /**
         * Xem xét玩家是否在线
         *
         */
        $o_role_row = Helpers::getRoleRowByRoleId($o_role_id);
        if (empty($o_role_row)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người bên kia đang offline',
            ])));
        }
        if ($o_role_row->map_id !== $request->roleRow->map_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Đối phương đã rời đi',
            ])));
        }


        /**
         * Xem xét对方状态
         *
         */
        $give = cache()->get('role_give_' . $o_role_id);
        if ($give['to'] !== $request->roleId or $give['kind'] !== 2 or
            $give['id'] !== $o_role_thing_id or $give['md5'] !== $md5) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Quà tặng đã bị bên kia hủy',
            ])));
        }

        /**
         * 获取可以的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $o_role_thing_id AND `role_id` = $o_role_id;
SQL;

        $o_role_thing = Helpers::queryFetchObject($sql);
        if (!is_object($o_role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Các vật phẩm quà tặng đã biến mất',
            ])));
        }
        $o_role_thing->row = Helpers::getThingRowByThingId($o_role_thing->thing_id);
        return $connection->send(\cache_response($request, \view('Func/Give/viewBox.twig', [
            'request' => $request,
            'o_role_row' => $o_role_row,
            'o_role_thing' => $o_role_thing,
            'consent_url' => 'Func/Give/consentBox/' . $o_role_id . '/' . $o_role_thing_id . '/' . $md5,
            'refuse_url' => 'Func/Give/refuse/' . $o_role_id,
            'number' => Helpers::getHansNumber($give['number']),
        ])));
    }


    /**
     * 箱子
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     * @param int $o_role_thing_id
     * @param string $md5
     *
     * @return bool|null
     */
    public function consentBox(TcpConnection $connection, Request $request, int $o_role_id, int $o_role_thing_id, string $md5)
    {
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->weight >= 100000000) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ba lô đã đầy và không thể nhận',
            ])));
        }
        /**
         * Xem xét玩家是否在线
         *
         */
        $o_role_row = Helpers::getRoleRowByRoleId($o_role_id);
        if (empty($o_role_row)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người bên kia đang offline',
            ])));
        }
        if ($o_role_row->map_id !== $request->roleRow->map_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Đối phương đã rời đi',
            ])));
        }

        /**
         * Xem xét对方状态
         *
         */
        $give = cache()->get('role_give_' . $o_role_id);
        if ($give['to'] !== $request->roleId or $give['kind'] !== 2 or
            $give['id'] !== $o_role_thing_id or $give['md5'] !== $md5) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Quà tặng đã bị bên kia hủy',
            ])));
        }

        /**
         * 获取可以的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $o_role_thing_id AND `role_id` = $o_role_id;
SQL;

        $o_role_thing = Helpers::queryFetchObject($sql);
        if (!is_object($o_role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Các vật phẩm quà tặng đã biến mất',
            ])));
        }

        /**
         * 再次Xác nhận状态
         *
         */
        $give_confirm = cache()->get('role_give_' . $o_role_id);
        if ($give['to'] !== $give_confirm['to'] or $give['kind'] !== $give_confirm['kind']
            or $give['id'] !== $give_confirm['id'] or $give['number'] !== $give_confirm['number']
            or $give['md5'] !== $give_confirm['md5']) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Quà tặng đã bị bên kia hủy',
            ])));
        }

        /**
         * 判断数量是否足够
         */
        if ($o_role_thing->number < $give['number']) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Quà tặng đã bị bên kia hủy',
            ])));
        }

        /**
         * 查询玩家是否存在已有的物品
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = $o_role_thing->thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        if (is_object($role_thing)) {
            $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` + {$give['number']} WHERE `id` = $role_thing->id;
SQL;

        } else {
            $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, $o_role_thing->thing_id, {$give['number']});
SQL;

        }
        if ($o_role_thing->number == $give['number']) {
            $sql .= <<<SQL
DELETE FROM `role_things` WHERE `id` = $o_role_thing_id;
SQL;

        } else {
            $sql .= <<<SQL
UPDATE `role_things` SET `number` = `number` - {$give['number']} WHERE `id` = $o_role_thing_id;
SQL;

        }
        /**
         * 转移物品
         */
        Helpers::execSql($sql);
        cache()->rPush('role_broadcast_' . $o_role_id, [
            'kind' => 6,
            'content' => $request->roleRow->name . 'Đã nhận quà của bạn.',
        ]);
        $o_role_thing->row = Helpers::getThingRowByThingId($o_role_thing->thing_id);
        cache()->set('role_flush_weight_' . $o_role_id, true);

        loglog(LOG_TRANSACTIONS_GIVES, '赠与箱子', [
            '赠与玩家' => $o_role_row->name,
            '受赠玩家' => $request->roleRow->name,
            '物品' => $o_role_thing->row->name,
            '数量' => $give['number'],
        ]);

        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => $o_role_row->name . 'Món quà cho bạn' . Helpers::getHansNumber($give['number']) . $o_role_thing->row->unit . $o_role_thing->row->name . ',thành công.',
        ])));
    }


    /**
     * 赠送金钱
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     * @param int $role_thing_id
     *
     * @return bool|null
     */
    public function moneyQuestion(TcpConnection $connection, Request $request, int $o_role_id, int $role_thing_id)
    {
        /**
         * Xem xét玩家是否在线
         *
         */
        $o_role_row = Helpers::getRoleRowByRoleId($o_role_id);
        if (empty($o_role_row)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người bên kia đang offline',
            ])));
        }
        if ($o_role_row->map_id !== $request->roleRow->map_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Đối phương đã rời đi',
            ])));
        }

        /**
         * 获取可以的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id AND `role_id` = $request->roleId;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        if (!is_object($role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Mục này không còn thuộc về bạn',
            ])));
        }
        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
        return $connection->send(\cache_response($request, \view('Func/Give/moneyQuestion.twig', [
            'request' => $request,
            'back_url' => 'Func/Give/start/' . $o_role_id,
            'post_url' => 'Func/Give/moneyPost/' . $o_role_id . '/' . $role_thing_id,
            'o_role_row' => $o_role_row,
            'role_thing' => $role_thing,
        ])));
    }


    /**
     * 金钱
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     * @param int $role_thing_id
     *
     * @return bool|null
     */
    public function moneyPost(TcpConnection $connection, Request $request, int $o_role_id, int $role_thing_id)
    {
        if (strtoupper($request->method()) !== 'POST') {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $number = $request->post('number');
        if (!is_numeric($number)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $number = intval($number);
        if ($number < 1 or $number > 10000000000) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        /**
         * Xem xét玩家是否在线
         *
         */
        $o_role_row = Helpers::getRoleRowByRoleId($o_role_id);
        if (empty($o_role_row)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người bên kia đang offline',
            ])));
        }
        if ($o_role_row->map_id !== $request->roleRow->map_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Đối phương đã rời đi',
            ])));
        }

        /**
         * 获取可以的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id AND `role_id` = $request->roleId;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        if (!is_object($role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Mục này không còn thuộc về bạn',
            ])));
        }
        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
        if ($role_thing->number < $number) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Bạn không có đủ tiền.',
            ])));
        }
        $md5 = md5(microtime(true));
        cache()->set('role_give_' . $request->roleId, [
            'to' => $o_role_row->id,
            'kind' => 5,
            'id' => intval($role_thing->id),
            'number' => $number,
            'md5' => $md5,
        ]);
        cache()->rPush('role_broadcast_' . $o_role_row->id, [
            'kind' => 9,
            'content' => $request->roleRow->name . ' muốn tặng đi ' . Helpers::getHansMoney($number) . 'Đây rồi, bạn có chấp nhận nó không?',
            'view_url' => 'Func/Give/viewMoney/' . $request->roleId . '/' . $role_thing->id . '/' . $md5,
            'consent_url' => 'Func/Give/consentMoney/' . $request->roleId . '/' . $role_thing->id . '/' . $md5,
            'refuse_url' => 'Func/Give/refuse/' . $request->roleId,
        ]);
        return $connection->send(\cache_response($request, \view('Func/Give/moneyPost.twig', [
            'request' => $request,
            'o_role_row' => $o_role_row,
            'role_thing' => $role_thing,
            'money' => Helpers::getHansMoney($number),
        ])));
    }


    /**
     * Xem xét金钱
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     * @param int $o_role_thing_id
     * @param string $md5
     *
     * @return bool|null
     */
    public function viewMoney(TcpConnection $connection, Request $request, int $o_role_id, int $o_role_thing_id, string $md5)
    {
        /**
         * Xem xét玩家是否在线
         *
         */
        $o_role_row = Helpers::getRoleRowByRoleId($o_role_id);
        if (empty($o_role_row)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người bên kia đang offline',
            ])));
        }
        if ($o_role_row->map_id !== $request->roleRow->map_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Đối phương đã rời đi',
            ])));
        }


        /**
         * Xem xét对方状态
         *
         */
        $give = cache()->get('role_give_' . $o_role_id);
        if ($give['to'] !== $request->roleId or $give['kind'] !== 5 or
            $give['id'] !== $o_role_thing_id or $give['md5'] !== $md5) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Quà tặng đã bị bên kia hủy',
            ])));
        }

        /**
         * 获取可以的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $o_role_thing_id AND `role_id` = $o_role_id;
SQL;

        $o_role_thing = Helpers::queryFetchObject($sql);
        if (!is_object($o_role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Các vật phẩm quà tặng đã biến mất',
            ])));
        }
        $o_role_thing->row = Helpers::getThingRowByThingId($o_role_thing->thing_id);
        return $connection->send(\cache_response($request, \view('Func/Give/viewMoney.twig', [
            'request' => $request,
            'o_role_row' => $o_role_row,
            'o_role_thing' => $o_role_thing,
            'consent_url' => 'Func/Give/consentMoney/' . $o_role_id . '/' . $o_role_thing_id . '/' . $md5,
            'refuse_url' => 'Func/Give/refuse/' . $o_role_id,
            'money' => Helpers::getHansMoney($give['number']),
        ])));
    }


    /**
     * 箱子
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     * @param int $o_role_thing_id
     * @param string $md5
     *
     * @return bool|null
     */
    public function consentMoney(TcpConnection $connection, Request $request, int $o_role_id, int $o_role_thing_id, string $md5)
    {

        if (!cache()->set('lock_role_give_' . $o_role_id, 'ok', ['NX', 'PX' => 50])) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Quà tặng đã bị hủy',
            ])));
        }

        if (!cache()->set('lock_role_give_' . $request->roleId, 'ok', ['NX', 'PX' => 50])) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Quà tặng đã bị hủy',
            ])));
        }


        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);

        /**
         * Xem xét玩家是否在线
         *
         */
        $o_role_row = Helpers::getRoleRowByRoleId($o_role_id);
        if (empty($o_role_row)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người bên kia đang offline',
            ])));
        }
        if ($o_role_row->map_id !== $request->roleRow->map_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Đối phương đã rời đi',
            ])));
        }

        /**
         * Xem xét对方状态
         *
         */
        $give = cache()->get('role_give_' . $o_role_id);
        if ($give['to'] !== $request->roleId or $give['kind'] !== 5 or
            $give['id'] !== $o_role_thing_id or $give['md5'] !== $md5) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Quà tặng đã bị bên kia hủy',
            ])));
        }


        /**
         * 获取可以的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $o_role_thing_id AND `role_id` = $o_role_id;
SQL;

        $o_role_thing = Helpers::queryFetchObject($sql);
        if (!is_object($o_role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Các vật phẩm quà tặng đã biến mất',
            ])));
        }

        /**
         * 再次Xác nhận状态
         *
         */
        $give_confirm = cache()->get('role_give_' . $o_role_id);
        if ($give['to'] !== $give_confirm['to'] or $give['kind'] !== $give_confirm['kind']
            or $give['id'] !== $give_confirm['id'] or $give['number'] !== $give_confirm['number']
            or $give['md5'] !== $give_confirm['md5']) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Quà tặng đã bị bên kia hủy',
            ])));
        }


        if ($role_attrs->weight + $give_confirm['number'] >= 100000000) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ba lô đã đầy và không thể nhận',
            ])));
        }

        /**
         * 判断数量是否足够
         */
        if ($o_role_thing->number < $give['number']) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Quà tặng đã bị bên kia hủy',
            ])));
        }

        /**
         * 查询玩家是否存在已有的物品
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = $o_role_thing->thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        if (is_object($role_thing)) {
            $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` + {$give['number']} WHERE `id` = $role_thing->id;
SQL;

        } else {
            $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, $o_role_thing->thing_id, {$give['number']});
SQL;

        }
        if ($o_role_thing->number == $give['number']) {
            $sql .= <<<SQL
DELETE FROM `role_things` WHERE `id` = $o_role_thing_id;
SQL;

        } else {
            $sql .= <<<SQL
UPDATE `role_things` SET `number` = `number` - {$give['number']} WHERE `id` = $o_role_thing_id;
SQL;

        }
        /**
         * 转移物品
         */
        Helpers::execSql($sql);
        cache()->rPush('role_broadcast_' . $o_role_id, [
            'kind' => 6,
            'content' => $request->roleRow->name . 'Đã nhận quà của bạn.',
        ]);
        cache()->set('role_flush_weight_' . $o_role_id, true);
        $o_role_thing->row = Helpers::getThingRowByThingId($o_role_thing->thing_id);

        loglog(LOG_TRANSACTIONS_GIVES, '赠与金钱', [
            '赠与玩家' => $o_role_row->name,
            '受赠玩家' => $request->roleRow->name,
            '数量' => $give['number'],
        ]);

        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => $o_role_row->name . 'Món quà cho bạn' . Helpers::getHansMoney($give['number']) . ',thành công.',
        ])));
    }


    /**
     * 药
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     * @param int $role_drug_id
     *
     * @return bool|null
     */
    public function drugQuestion(TcpConnection $connection, Request $request, int $o_role_id, int $role_drug_id)
    {
        /**
         * Xem xét玩家是否在线
         *
         */
        $o_role_row = Helpers::getRoleRowByRoleId($o_role_id);
        if (empty($o_role_row)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người bên kia đang offline',
            ])));
        }
        if ($o_role_row->map_id !== $request->roleRow->map_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Đối phương đã rời đi',
            ])));
        }

        /**
         * 获取可以的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_drugs` WHERE `id` = $role_drug_id AND `role_id` = $request->roleId;
SQL;

        $role_drug = Helpers::queryFetchObject($sql);
        if (!is_object($role_drug)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Mục này không còn thuộc về bạn',
            ])));
        }
        $role_drug->row = Helpers::getThingRowByThingId($role_drug->thing_id);

        return $connection->send(\cache_response($request, \view('Func/Give/drugQuestion.twig', [
            'request' => $request,
            'back_url' => 'Func/Give/start/' . $o_role_id,
            'post_url' => 'Func/Give/drugPost/' . $o_role_id . '/' . $role_drug_id,
            'o_role_row' => $o_role_row,
            'role_drug' => $role_drug,
        ])));
    }


    /**
     * 药物
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     * @param int $role_thing_id
     *
     * @return bool|null
     */
    public function drugPost(TcpConnection $connection, Request $request, int $o_role_id, int $role_thing_id)
    {
        if (strtoupper($request->method()) !== 'POST') {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $number = $request->post('number');
        if (!is_numeric($number)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $number = intval($number);
        if ($number < 1 or $number > 10000000000) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        /**
         * Xem xét玩家是否在线
         *
         */
        $o_role_row = Helpers::getRoleRowByRoleId($o_role_id);
        if (empty($o_role_row)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người bên kia đang offline',
            ])));
        }
        if ($o_role_row->map_id !== $request->roleRow->map_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Đối phương đã rời đi',
            ])));
        }

        /**
         * 获取可以的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_drugs` WHERE `id` = $role_thing_id AND `role_id` = $request->roleId;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        if (!is_object($role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Mục này không còn thuộc về bạn',
            ])));
        }
        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
        if ($role_thing->number < $number) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'bạn không có đủ' . $role_thing->row->name . '。',
            ])));
        }
        $md5 = md5(microtime(true));
        cache()->set('role_give_' . $request->roleId, [
            'to' => $o_role_row->id,
            'kind' => 3,
            'id' => intval($role_thing->id),
            'number' => $number,
            'md5' => $md5,
        ]);
        cache()->rPush('role_broadcast_' . $o_role_row->id, [
            'kind' => 9,
            'content' => $request->roleRow->name . ' muốn tặng đi ' . Helpers::getHansNumber($number) . $role_thing->row->unit . $role_thing->row->name . 'Đây rồi, bạn có chấp nhận nó không?',
            'view_url' => 'Func/Give/viewDrug/' . $request->roleId . '/' . $role_thing->id . '/' . $md5,
            'consent_url' => 'Func/Give/consentDrug/' . $request->roleId . '/' . $role_thing->id . '/' . $md5,
            'refuse_url' => 'Func/Give/refuse/' . $request->roleId,
        ]);
        return $connection->send(\cache_response($request, \view('Func/Give/drugPost.twig', [
            'request' => $request,
            'o_role_row' => $o_role_row,
            'role_thing' => $role_thing,
            'number' => Helpers::getHansNumber($number),
        ])));
    }


    /**
     * Xem xét药
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     * @param int $o_role_thing_id
     * @param string $md5
     *
     * @return bool|null
     */
    public function viewDrug(TcpConnection $connection, Request $request, int $o_role_id, int $o_role_thing_id, string $md5)
    {
        /**
         * Xem xét玩家是否在线
         *
         */
        $o_role_row = Helpers::getRoleRowByRoleId($o_role_id);
        if (empty($o_role_row)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người bên kia đang offline',
            ])));
        }
        if ($o_role_row->map_id !== $request->roleRow->map_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Đối phương đã rời đi',
            ])));
        }

        /**
         * Xem xét对方状态
         *
         */
        $give = cache()->get('role_give_' . $o_role_id);
        if ($give['to'] !== $request->roleId or $give['kind'] !== 3 or
            $give['id'] !== $o_role_thing_id or $give['md5'] !== $md5) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Quà tặng đã bị bên kia hủy',
            ])));
        }

        /**
         * 获取可以的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_drugs` WHERE `id` = $o_role_thing_id AND `role_id` = $o_role_id;
SQL;

        $o_role_thing = Helpers::queryFetchObject($sql);
        if (!is_object($o_role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Các vật phẩm quà tặng đã biến mất',
            ])));
        }
        $o_role_thing->row = Helpers::getThingRowByThingId($o_role_thing->thing_id);
        return $connection->send(\cache_response($request, \view('Func/Give/viewDrug.twig', [
            'request' => $request,
            'o_role_row' => $o_role_row,
            'o_role_thing' => $o_role_thing,
            'consent_url' => 'Func/Give/consentDrug/' . $o_role_id . '/' . $o_role_thing_id . '/' . $md5,
            'refuse_url' => 'Func/Give/refuse/' . $o_role_id,
            'number' => Helpers::getHansNumber($give['number']),
        ])));
    }


    /**
     * 药
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     * @param int $o_role_thing_id
     * @param string $md5
     *
     * @return bool|null
     */
    public function consentDrug(TcpConnection $connection, Request $request, int $o_role_id, int $o_role_thing_id, string $md5)
    {
        /**
         * Xem xét玩家是否在线
         *
         */
        $o_role_row = Helpers::getRoleRowByRoleId($o_role_id);
        if (empty($o_role_row)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người bên kia đang offline',
            ])));
        }
        if ($o_role_row->map_id !== $request->roleRow->map_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Đối phương đã rời đi',
            ])));
        }

        /**
         * Xem xét对方状态
         *
         */
        $give = cache()->get('role_give_' . $o_role_id);
        if ($give['to'] !== $request->roleId or $give['kind'] !== 3 or
            $give['id'] !== $o_role_thing_id or $give['md5'] !== $md5) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Quà tặng đã bị bên kia hủy',
            ])));
        }

        /**
         * 获取可以的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_drugs` WHERE `id` = $o_role_thing_id AND `role_id` = $o_role_id;
SQL;

        $o_role_thing = Helpers::queryFetchObject($sql);
        if (!is_object($o_role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Các vật phẩm quà tặng đã biến mất',
            ])));
        }

        /**
         * 再次Xác nhận状态
         *
         */
        $give_confirm = cache()->get('role_give_' . $o_role_id);
        if ($give['to'] !== $give_confirm['to'] or $give['kind'] !== $give_confirm['kind']
            or $give['id'] !== $give_confirm['id'] or $give['number'] !== $give_confirm['number']
            or $give['md5'] !== $give_confirm['md5']) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Quà tặng đã bị bên kia hủy',
            ])));
        }

        /**
         * 判断数量是否足够
         */
        if ($o_role_thing->number < $give['number']) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Quà tặng đã bị bên kia hủy',
            ])));
        }

        /**
         * 查询玩家是否存在已有的物品
         */
        $sql = <<<SQL
SELECT * FROM `role_drugs` WHERE `role_id` = $request->roleId AND `thing_id` = $o_role_thing->thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        if (is_object($role_thing)) {
            $sql = <<<SQL
UPDATE `role_drugs` SET `number` = `number` + {$give['number']} WHERE `id` = $role_thing->id;
SQL;

        } else {
            $sql = <<<SQL
INSERT INTO `role_drugs` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, $o_role_thing->thing_id, {$give['number']});
SQL;

        }
        if ($o_role_thing->number == $give['number']) {
            $sql .= <<<SQL
DELETE FROM `role_drugs` WHERE `id` = $o_role_thing_id;
SQL;

        } else {
            $sql .= <<<SQL
UPDATE `role_drugs` SET `number` = `number` - {$give['number']} WHERE `id` = $o_role_thing_id;
SQL;

        }
        /**
         * 转移物品
         *
         */
        Helpers::execSql($sql);
        cache()->rPush('role_broadcast_' . $o_role_id, [
            'kind' => 6,
            'content' => $request->roleRow->name . 'Đã nhận quà của bạn.',
        ]);
        $o_role_thing->row = Helpers::getThingRowByThingId($o_role_thing->thing_id);

        loglog(LOG_TRANSACTIONS_GIVES, '赠与Chữa thương dược', [
            '赠与玩家' => $o_role_row->name,
            '受赠玩家' => $request->roleRow->name,
            '物品' => $o_role_thing->row->name,
            '数量' => $give['number'],
        ]);

        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => $o_role_row->name . 'Món quà cho bạn' . Helpers::getHansNumber($give['number']) . $o_role_thing->row->unit . $o_role_thing->row->name . ',thành công.',
        ])));
    }


    /**
     * 拒绝
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     *
     * @return bool|null
     */
    public function refuse(TcpConnection $connection, Request $request, int $o_role_id)
    {
        /**
         * Xem xét玩家是否在线
         *
         */
        $o_role_row = Helpers::getRoleRowByRoleId($o_role_id);
        if (empty($o_role_row)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người bên kia đang offline',
            ])));
        }
        if ($o_role_row->map_id !== $request->roleRow->map_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Đối phương đã rời đi',
            ])));
        }
        cache()->rPush('role_broadcast_' . $o_role_id, [
            'kind' => 6,
            'content' => $request->roleRow->name . 'Tôi đã từ chối quà của bạn.',
        ]);

        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => 'Bạn từ chối ' . $o_role_row->name . ' quà!',
        ])));
    }
}
