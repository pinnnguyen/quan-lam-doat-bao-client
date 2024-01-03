<?php


namespace App\Http\Controllers\Func;


use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;


/**
 * Giao dịch
 * 1 物品     2 箱子     3 药     4 心法     5 道具
 *
 */
class TransactionController
{
    /**
     * 发起Giao dịch
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     *
     * @return bool|null
     */
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

        $transactions = [];

        /**
         * 获取可以Giao dịch的物品
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
                    $transactions[] = [
                        'name' => '一' . $role_thing->row->unit . $role_thing->row->name,
                        'url' => 'Func/Transaction/thingQuestion/' . $o_role_id . '/' . $role_thing->id,
                    ];
                } elseif (in_array($role_thing->thing_id, [215, 216, 217, 218, 219, 220, 221, 222, 245])) {
                    $transactions[] = [
                        'name' => Helpers::getHansNumber($role_thing->number) . $role_thing->row->unit . $role_thing->row->name,
                        'url' => 'Func/Transaction/boxQuestion/' . $o_role_id . '/' . $role_thing->id,
                    ];
                }
            }
        }

        /**
         * 获取可以Giao dịch的药
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_drugs` WHERE `role_id` = $request->roleId;
SQL;

        $role_drugs = Helpers::queryFetchAll($sql);
        if (is_array($role_drugs)) {
            foreach ($role_drugs as $role_drug) {
                $role_drug->row = Helpers::getThingRowByThingId($role_drug->thing_id);
                $transactions[] = [
                    'name' => Helpers::getHansNumber($role_drug->number) . $role_drug->row->unit . $role_drug->row->name,
                    'url' => 'Func/Transaction/drugQuestion/' . $o_role_id . '/' . $role_drug->id,
                ];
            }
        }
        return $connection->send(\cache_response($request, \view('Func/Transaction/start.twig', [
            'request' => $request,
            'trade_xinfa_url' => 'Func/Transaction/xinfa/' . $o_role_id,
            'o_role_row' => $o_role_row,
            'transactions' => $transactions,
        ])));
    }


    /**
     * Giao dịch物品  装备 书籍
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

        /**
         * 获取可以Giao dịch的物品
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

        return $connection->send(\cache_response($request, \view('Func/Transaction/thingQuestion.twig', [
            'request' => $request,
            'back_url' => 'Func/Transaction/start/' . $o_role_id,
            'post_url' => 'Func/Transaction/thingPost/' . $o_role_id . '/' . $role_thing_id,
            'o_role_row' => $o_role_row,
            'role_thing' => $role_thing,
        ])));
    }


    /**
     * Giao dịch物品 提交
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
        if (strtoupper($request->method()) !== 'POST') {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $price = $request->post('price');
        if (!is_numeric($price)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $price = intval($price);
        if ($price < 1 or $price > 10000000000) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $price *= 100;
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

        /**
         * 获取可以Giao dịch的物品
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
        cache()->set('role_transaction_' . $request->roleId, [
            'to' => $o_role_row->id,
            'kind' => 1,
            'id' => intval($role_thing->id),
            'number' => 1,
            'price' => $price,
        ]);
        cache()->rPush('role_broadcast_' . $o_role_row->id, [
            'kind' => 8,
            'content' => $request->roleRow->name . ' Muốn bán một cái ' . $role_thing->row->unit . $role_thing->row->name . ' Đây nhé, đấu giá ' . Helpers::getHansMoney($price) . ', bạn có đồng ý giao dịch không?',
            'view_url' => 'Func/Transaction/viewThing/' . $request->roleId . '/' . $role_thing->id,
            'consent_url' => 'Func/Transaction/consentThing/' . $request->roleId . '/' . $role_thing->id,
            'refuse_url' => 'Func/Transaction/refuse/' . $request->roleId,
        ]);

        return $connection->send(\cache_response($request, \view('Func/Transaction/thingPost.twig', [
            'request' => $request,
            'o_role_row' => $o_role_row,
            'role_thing' => $role_thing,
            'price' => Helpers::getHansMoney($price),
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

        /**
         * Xem xét对方Giao dịch状态
         *
         */
        $transaction = cache()->get('role_transaction_' . $o_role_id);
        if ($transaction['to'] !== $request->roleId or $transaction['kind'] !== 1 or $transaction['id'] !== $o_role_thing_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Giao dịch đã bị hủy bởi bên kia',
            ])));
        }

        /**
         * 获取可以Giao dịch的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $o_role_thing_id AND `role_id` = $o_role_id;
SQL;

        $o_role_thing = Helpers::queryFetchObject($sql);
        if (!is_object($o_role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Vật phẩm giao dịch đã biến mất',
            ])));
        }
        $o_role_thing->row = Helpers::getThingRowByThingId($o_role_thing->thing_id);
        if ($o_role_thing->row->kind === '装备') {
            $o_role_thing->statusString = str_repeat('*', $o_role_thing->status);
        }
        return $connection->send(\cache_response($request, \view('Func/Transaction/viewThing.twig', [
            'request' => $request,
            'o_role_row' => $o_role_row,
            'o_role_thing' => $o_role_thing,
            'consent_url' => 'Func/Transaction/consentThing/' . $o_role_id . '/' . $o_role_thing_id,
            'refuse_url' => 'Func/Transaction/refuse/' . $o_role_id,
            'price' => Helpers::getHansMoney($transaction['price']),
        ])));
    }


    /**
     * 物品 Đồng ý giao dịch
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
        if (!cache()->set('lock_role_bank_' . $o_role_id, 'ok', ['NX', 'EX' => 50])) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Giao dịch đã bị hủy',
            ])));
        }

        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->weight >= 100000000) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ba lô đã đầy và không thể chấp nhận được',
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

        /**
         * Xem xét对方Giao dịch状态
         *
         */
        $transaction = cache()->get('role_transaction_' . $o_role_id);
        if ($transaction['to'] !== $request->roleId or $transaction['kind'] !== 1 or $transaction['id'] !== $o_role_thing_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Giao dịch đã bị hủy bởi bên kia',
            ])));
        }

        /**
         * 获取可以Giao dịch的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $o_role_thing_id AND `role_id` = $o_role_id;
SQL;

        $o_role_thing = Helpers::queryFetchObject($sql);
        if (!is_object($o_role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Vật phẩm giao dịch đã biến mất',
            ])));
        }

        /**
         * 查询金钱是否足够
         *
         */
        if ($request->roleRow->bank_balance < $transaction['price']) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Tiền tiết kiệm của bạn trong ngân hàng không đủ',
            ])));
        }

        /**
         * 再次Xác nhậnGiao dịch状态
         *
         */
        $transaction_confirm = cache()->get('role_transaction_' . $o_role_id);
        if ($transaction['to'] !== $transaction_confirm['to'] or $transaction['kind'] !== $transaction_confirm['kind']
            or $transaction['id'] !== $transaction_confirm['id'] or $transaction['number'] !== $transaction_confirm['number']
            or $transaction['price'] !== $transaction_confirm['price']) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Giao dịch đã bị hủy bởi bên kia',
            ])));
        }

        /**
         * 转移物品、财产
         */

        $sql = <<<SQL
UPDATE `roles` SET `bank_balance` = `bank_balance` + {$transaction['price']} WHERE `id` = $o_role_id;
UPDATE `roles` SET `bank_balance` = `bank_balance` - {$transaction['price']} WHERE `id` = $request->roleId;
UPDATE `role_things` SET `role_id` = $request->roleId WHERE `id` = $o_role_thing_id;
SQL;

        Helpers::execSql($sql);
        $o_role_row = Helpers::getRoleRowByRoleId($o_role_id);
        $o_role_row->bank_balance += $transaction['price'];
        Helpers::setRoleRowByRoleId($o_role_id, $o_role_row);
        cache()->rPush('role_broadcast_' . $o_role_id, [
            'kind' => 6,
            'content' => 'bạn và ' . $request->roleRow->name . ' Giao dịch thành công,' . Helpers::getHansMoney($transaction['price']) .
                'Nó đã được tự động gửi vào ngân hàng.',
        ]);
        cache()->set('role_flush_weight_' . $o_role_id, true);
        $request->roleRow->bank_balance -= $transaction['price'];
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);

        $o_role_thing->row = Helpers::getThingRowByThingId($o_role_thing->thing_id);

        loglog(LOG_TRANSACTIONS_GIVES, 'Giao dịch物品', [
            '买方玩家' => $request->roleRow->name,
            '卖方玩家' => $o_role_row->name,
            '物品' => $o_role_thing->row->name,
            '金额' => $transaction['price'],
        ]);
        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => $o_role_row->name . 'Tôi sẽ cho bạn một thỏa thuận ' . $o_role_thing->row->unit . $o_role_thing->row->name . ',thành công.',
        ])));
    }


    /**
     * Giao dịch箱子
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

        /**
         * 获取可以Giao dịch的物品
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

        return $connection->send(\cache_response($request, \view('Func/Transaction/boxQuestion.twig', [
            'request' => $request,
            'back_url' => 'Func/Transaction/start/' . $o_role_id,
            'post_url' => 'Func/Transaction/boxPost/' . $o_role_id . '/' . $role_thing_id,
            'o_role_row' => $o_role_row,
            'role_thing' => $role_thing,
        ])));
    }


    /**
     * Giao dịch箱子
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
        $price = $request->post('price');
        if (!is_numeric($number) or !is_numeric($price)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $number = intval($number);
        $price = intval($price);
        if ($price < 1 or $number < 1 or $price > 10000000000 or $number > 10000000000) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $price *= 100;
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

        /**
         * 获取可以Giao dịch的物品
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
                'message' => 'bạn không có đủ ' . $role_thing->row->name . '。',
            ])));
        }
        $md5 = md5(microtime(true));
        cache()->set('role_transaction_' . $request->roleId, [
            'to' => $o_role_row->id,
            'kind' => 2,
            'id' => intval($role_thing->id),
            'number' => $number,
            'price' => $price,
            'md5' => $md5,
        ]);
        cache()->rPush('role_broadcast_' . $o_role_row->id, [
            'kind' => 8,
            'content' => $request->roleRow->name . 'Muốn bán ' . Helpers::getHansNumber($number) . $role_thing->row->unit . $role_thing->row->name . 'Đây nhé, đấu giá' . Helpers::getHansMoney($price) . ', bạn có đồng ý giao dịch không?',
            'view_url' => 'Func/Transaction/viewBox/' . $request->roleId . '/' . $role_thing->id . '/' . $md5,
            'consent_url' => 'Func/Transaction/consentBox/' . $request->roleId . '/' . $role_thing->id . '/' . $md5,
            'refuse_url' => 'Func/Transaction/refuse/' . $request->roleId,
        ]);
        return $connection->send(\cache_response($request, \view('Func/Transaction/boxPost.twig', [
            'request' => $request,
            'o_role_row' => $o_role_row,
            'role_thing' => $role_thing,
            'price' => Helpers::getHansMoney($price),
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

        /**
         * Xem xét对方Giao dịch状态
         *
         */
        $transaction = cache()->get('role_transaction_' . $o_role_id);
        if ($transaction['to'] !== $request->roleId or $transaction['kind'] !== 2 or
            $transaction['id'] !== $o_role_thing_id or $transaction['md5'] !== $md5) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Giao dịch đã bị hủy bởi bên kia',
            ])));
        }

        /**
         * 获取可以Giao dịch的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $o_role_thing_id AND `role_id` = $o_role_id;
SQL;

        $o_role_thing = Helpers::queryFetchObject($sql);
        if (!is_object($o_role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Vật phẩm giao dịch đã biến mất',
            ])));
        }
        $o_role_thing->row = Helpers::getThingRowByThingId($o_role_thing->thing_id);
        return $connection->send(\cache_response($request, \view('Func/Transaction/viewBox.twig', [
            'request' => $request,
            'o_role_row' => $o_role_row,
            'o_role_thing' => $o_role_thing,
            'consent_url' => 'Func/Transaction/consentBox/' . $o_role_id . '/' . $o_role_thing_id . '/' . $md5,
            'refuse_url' => 'Func/Transaction/refuse/' . $o_role_id,
            'price' => Helpers::getHansMoney($transaction['price']),
            'number' => Helpers::getHansNumber($transaction['number']),
        ])));
    }


    /**
     * Giao dịch箱子
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
        if (!cache()->set('lock_role_bank_' . $o_role_id, 'ok', ['NX', 'EX' => 50])) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Giao dịch đã bị hủy',
            ])));
        }

        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->weight >= 100000000) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ba lô đã đầy và không thể chấp nhận được',
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

        /**
         * Xem xét对方Giao dịch状态
         *
         */
        $transaction = cache()->get('role_transaction_' . $o_role_id);
        if ($transaction['to'] !== $request->roleId or $transaction['kind'] !== 2 or
            $transaction['id'] !== $o_role_thing_id or $transaction['md5'] !== $md5) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Giao dịch đã bị hủy bởi bên kia',
            ])));
        }

        /**
         * 获取可以Giao dịch的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $o_role_thing_id AND `role_id` = $o_role_id;
SQL;

        $o_role_thing = Helpers::queryFetchObject($sql);
        if (!is_object($o_role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Vật phẩm giao dịch đã biến mất',
            ])));
        }

        /**
         * 查询金钱是否足够
         *
         */
        if ($request->roleRow->bank_balance < $transaction['price']) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Tiền tiết kiệm của bạn trong ngân hàng không đủ',
            ])));
        }

        /**
         * 再次Xác nhậnGiao dịch状态
         *
         */
        $transaction_confirm = cache()->get('role_transaction_' . $o_role_id);
        if ($transaction['to'] !== $transaction_confirm['to'] or $transaction['kind'] !== $transaction_confirm['kind']
            or $transaction['id'] !== $transaction_confirm['id'] or $transaction['number'] !== $transaction_confirm['number']
            or $transaction['price'] !== $transaction_confirm['price'] or $transaction['md5'] !== $transaction_confirm['md5']) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Giao dịch đã bị hủy bởi bên kia',
            ])));
        }

        /**
         * 判断数量是否足够
         */
        if ($o_role_thing->number < $transaction['number']) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Giao dịch đã bị hủy bởi bên kia',
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
UPDATE `role_things` SET `number` = `number` + {$transaction['number']} WHERE `id` = $role_thing->id;
SQL;

        } else {
            $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, $o_role_thing->thing_id, {$transaction['number']});
SQL;

        }
        if ($o_role_thing->number == $transaction['number']) {
            $sql .= <<<SQL
DELETE FROM `role_things` WHERE `id` = $o_role_thing_id;
SQL;

        } else {
            $sql .= <<<SQL
UPDATE `role_things` SET `number` = `number` - {$transaction['number']} WHERE `id` = $o_role_thing_id;
SQL;

        }
        /**
         * 转移物品、财产
         */

        $sql .= <<<SQL
UPDATE `roles` SET `bank_balance` = `bank_balance` + {$transaction['price']} WHERE `id` = $o_role_id;
UPDATE `roles` SET `bank_balance` = `bank_balance` - {$transaction['price']} WHERE `id` = $request->roleId;
SQL;

        Helpers::execSql($sql);
        $o_role_row = Helpers::getRoleRowByRoleId($o_role_id);
        $o_role_row->bank_balance += $transaction['price'];
        Helpers::setRoleRowByRoleId($o_role_id, $o_role_row);
        cache()->rPush('role_broadcast_' . $o_role_id, [
            'kind' => 6,
            'content' => 'bạn và ' . $request->roleRow->name . ' Giao dịch thành công,' . Helpers::getHansMoney($transaction['price']) .
                'Nó đã được tự động gửi vào ngân hàng.',
        ]);
        cache()->set('role_flush_weight_' . $o_role_id, true);

        $request->roleRow->bank_balance -= $transaction['price'];
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);

        $o_role_thing->row = Helpers::getThingRowByThingId($o_role_thing->thing_id);

        loglog(LOG_TRANSACTIONS_GIVES, 'Giao dịch箱子', [
            '买方玩家' => $request->roleRow->name,
            '卖方玩家' => $o_role_row->name,
            '物品' => $o_role_thing->row->name,
            '数量' => $transaction['number'],
            '金额' => $transaction['price'],
        ]);

        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => $o_role_row->name . 'thỏa thuận với bạn' . Helpers::getHansNumber($transaction['number']) . $o_role_thing->row->unit . $o_role_thing->row->name . ',thành công.',
        ])));
    }


    /**
     * Giao dịch药
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

        /**
         * 获取可以Giao dịch的物品
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

        return $connection->send(\cache_response($request, \view('Func/Transaction/drugQuestion.twig', [
            'request' => $request,
            'back_url' => 'Func/Transaction/start/' . $o_role_id,
            'post_url' => 'Func/Transaction/drugPost/' . $o_role_id . '/' . $role_drug_id,
            'o_role_row' => $o_role_row,
            'role_drug' => $role_drug,
        ])));
    }


    /**
     * Giao dịch药物
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
        $price = $request->post('price');
        if (!is_numeric($number) or !is_numeric($price)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $number = intval($number);
        $price = intval($price);
        if ($price < 1 or $number < 1 or $price > 10000000000 or $number > 10000000000) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $price *= 100;
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

        /**
         * 获取可以Giao dịch的物品
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
                'message' => 'bạn không có đủ ' . $role_thing->row->name . '。',
            ])));
        }
        $md5 = md5(microtime(true));
        cache()->set('role_transaction_' . $request->roleId, [
            'to' => $o_role_row->id,
            'kind' => 3,
            'id' => intval($role_thing->id),
            'number' => $number,
            'price' => $price,
            'md5' => $md5,
        ]);
        cache()->rPush('role_broadcast_' . $o_role_row->id, [
            'kind' => 8,
            'content' => $request->roleRow->name . 'Muốn bán ' . Helpers::getHansNumber($number) . $role_thing->row->unit . $role_thing->row->name . 'Đây nhé, đấu giá' . Helpers::getHansMoney($price) . ', bạn có đồng ý giao dịch không?',
            'view_url' => 'Func/Transaction/viewDrug/' . $request->roleId . '/' . $role_thing->id . '/' . $md5,
            'consent_url' => 'Func/Transaction/consentDrug/' . $request->roleId . '/' . $role_thing->id . '/' . $md5,
            'refuse_url' => 'Func/Transaction/refuse/' . $request->roleId,
        ]);
        return $connection->send(\cache_response($request, \view('Func/Transaction/drugPost.twig', [
            'request' => $request,
            'o_role_row' => $o_role_row,
            'role_thing' => $role_thing,
            'price' => Helpers::getHansMoney($price),
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

        /**
         * Xem xét对方Giao dịch状态
         *
         */
        $transaction = cache()->get('role_transaction_' . $o_role_id);
        if ($transaction['to'] !== $request->roleId or $transaction['kind'] !== 3 or
            $transaction['id'] !== $o_role_thing_id or $transaction['md5'] !== $md5) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Giao dịch đã bị hủy bởi bên kia',
            ])));
        }

        /**
         * 获取可以Giao dịch的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_drugs` WHERE `id` = $o_role_thing_id AND `role_id` = $o_role_id;
SQL;

        $o_role_thing = Helpers::queryFetchObject($sql);
        if (!is_object($o_role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Vật phẩm giao dịch đã biến mất',
            ])));
        }
        $o_role_thing->row = Helpers::getThingRowByThingId($o_role_thing->thing_id);
        return $connection->send(\cache_response($request, \view('Func/Transaction/viewDrug.twig', [
            'request' => $request,
            'o_role_row' => $o_role_row,
            'o_role_thing' => $o_role_thing,
            'consent_url' => 'Func/Transaction/consentDrug/' . $o_role_id . '/' . $o_role_thing_id . '/' . $md5,
            'refuse_url' => 'Func/Transaction/refuse/' . $o_role_id,
            'price' => Helpers::getHansMoney($transaction['price']),
            'number' => Helpers::getHansNumber($transaction['number']),
        ])));
    }


    /**
     * Giao dịch药
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
        if (!cache()->set('lock_role_bank_' . $o_role_id, 'ok', ['NX', 'EX' => 50])) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Giao dịch đã bị hủy',
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

        /**
         * Xem xét对方Giao dịch状态
         *
         */
        $transaction = cache()->get('role_transaction_' . $o_role_id);
        if ($transaction['to'] !== $request->roleId or $transaction['kind'] !== 3 or
            $transaction['id'] !== $o_role_thing_id or $transaction['md5'] !== $md5) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Giao dịch đã bị hủy bởi bên kia',
            ])));
        }

        /**
         * 获取可以Giao dịch的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_drugs` WHERE `id` = $o_role_thing_id AND `role_id` = $o_role_id;
SQL;

        $o_role_thing = Helpers::queryFetchObject($sql);
        if (!is_object($o_role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Vật phẩm giao dịch đã biến mất',
            ])));
        }

        /**
         * 查询金钱是否足够
         *
         */
        if ($request->roleRow->bank_balance < $transaction['price']) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Tiền tiết kiệm của bạn trong ngân hàng không đủ',
            ])));
        }

        /**
         * 再次Xác nhậnGiao dịch状态
         *
         */
        $transaction_confirm = cache()->get('role_transaction_' . $o_role_id);
        if ($transaction['to'] !== $transaction_confirm['to'] or $transaction['kind'] !== $transaction_confirm['kind']
            or $transaction['id'] !== $transaction_confirm['id'] or $transaction['number'] !== $transaction_confirm['number']
            or $transaction['price'] !== $transaction_confirm['price'] or $transaction['md5'] !== $transaction_confirm['md5']) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Giao dịch đã bị hủy bởi bên kia',
            ])));
        }

        /**
         * 判断数量是否足够
         */
        if ($o_role_thing->number < $transaction['number']) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Giao dịch đã bị hủy bởi bên kia',
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
UPDATE `role_drugs` SET `number` = `number` + {$transaction['number']} WHERE `id` = $role_thing->id;
SQL;

        } else {
            $sql = <<<SQL
INSERT INTO `role_drugs` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, $o_role_thing->thing_id, {$transaction['number']});
SQL;

        }
        if ($o_role_thing->number == $transaction['number']) {
            $sql .= <<<SQL
DELETE FROM `role_drugs` WHERE `id` = $o_role_thing_id;
SQL;

        } else {
            $sql .= <<<SQL
UPDATE `role_drugs` SET `number` = `number` - {$transaction['number']} WHERE `id` = $o_role_thing_id;
SQL;

        }
        /**
         * 转移物品、财产
         */

        $sql .= <<<SQL
UPDATE `roles` SET `bank_balance` = `bank_balance` + {$transaction['price']} WHERE `id` = $o_role_id;
UPDATE `roles` SET `bank_balance` = `bank_balance` - {$transaction['price']} WHERE `id` = $request->roleId;
SQL;

        Helpers::execSql($sql);
        $o_role_row = Helpers::getRoleRowByRoleId($o_role_id);
        $o_role_row->bank_balance += $transaction['price'];
        Helpers::setRoleRowByRoleId($o_role_id, $o_role_row);
        cache()->rPush('role_broadcast_' . $o_role_id, [
            'kind' => 6,
            'content' => 'bạn và ' . $request->roleRow->name . ' Giao dịch thành công,' . Helpers::getHansMoney($transaction['price']) .
                'Nó đã được tự động gửi vào ngân hàng.',
        ]);

        $request->roleRow->bank_balance -= $transaction['price'];
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);

        $o_role_thing->row = Helpers::getThingRowByThingId($o_role_thing->thing_id);

        loglog(LOG_TRANSACTIONS_GIVES, 'Giao dịchChữa thương dược', [
            '买方玩家' => $request->roleRow->name,
            '卖方玩家' => $o_role_row->name,
            '物品' => $o_role_thing->row->name,
            '数量' => $transaction['number'],
            '金额' => $transaction['price'],
        ]);
        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => $o_role_row->name . 'thỏa thuận với bạn' . Helpers::getHansNumber($transaction['number']) . $o_role_thing->row->unit . $o_role_thing->row->name . ',thành công.',
        ])));
    }


    /**
     * Cự tuyệt giao dịch
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
        cache()->rPush('role_broadcast_' . $o_role_id, [
            'kind' => 6,
            'content' => $request->roleRow->name . 'Giao dịch bạn thực hiện đã bị từ chối.',
        ]);

        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => 'bạn đã từ chối' . $o_role_row->name . 'của giao dịch này!',
        ])));
    }


    /**
     * 心法
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     *
     * @return bool|null
     */
    public function xinfa(TcpConnection $connection, Request $request, int $o_role_id)
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

        $transactions = [];

        /**
         * 获取可以Giao dịch的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `role_id` = $request->roleId AND `equipped` = 0 AND `practiced` = 0 AND `is_sell` = 0;
SQL;

        $role_xinfas = Helpers::queryFetchAll($sql);
        if (is_array($role_xinfas)) {
            foreach ($role_xinfas as $role_xinfa) {
                $role_xinfa->row = Helpers::getXinfaRowByXinfaId($role_xinfa->xinfa_id);
                $transactions[] = [
                    'name' => 'Một quyển' . $role_xinfa->row->name,
                    'url' => 'Func/Transaction/xinfaQuestion/' . $o_role_id . '/' . $role_xinfa->id,
                ];
            }
        }
        return $connection->send(\cache_response($request, \view('Func/Transaction/xinfa.twig', [
            'request' => $request,
            'trade_thing_url' => 'Func/Transaction/start/' . $o_role_id,
            'o_role_row' => $o_role_row,
            'transactions' => $transactions,
        ])));
    }


    /**
     * 心法Giao dịch 选择
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     * @param int $role_thing_id
     *
     * @return bool|null
     */
    public function xinfaQuestion(TcpConnection $connection, Request $request, int $o_role_id, int $role_xinfa_id)
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

        /**
         * 获取可以Giao dịch的心法
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id AND `role_id` = $request->roleId;
SQL;

        $role_xinfa = Helpers::queryFetchObject($sql);
        if (!is_object($role_xinfa)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => '此心法已不属于你',
            ])));
        }
        $role_xinfa->row = Helpers::getXinfaRowByXinfaId($role_xinfa->xinfa_id);

        return $connection->send(\cache_response($request, \view('Func/Transaction/xinfaQuestion.twig', [
            'request' => $request,
            'back_url' => 'Func/Transaction/xinfa/' . $o_role_id,
            'post_url' => 'Func/Transaction/xinfaPost/' . $o_role_id . '/' . $role_xinfa_id,
            'o_role_row' => $o_role_row,
            'role_xinfa' => $role_xinfa,
        ])));
    }


    /**
     * Giao dịch物品 提交
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function xinfaPost(TcpConnection $connection, Request $request, int $o_role_id, int $role_xinfa_id)
    {
        if (strtoupper($request->method()) !== 'POST') {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $price = $request->post('price');
        if (!is_numeric($price)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $price = intval($price);
        if ($price < 1 or $price > 10000000000) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $price *= 100;
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

        /**
         * 获取可以Giao dịch的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id AND `role_id` = $request->roleId;
SQL;

        $role_xinfa = Helpers::queryFetchObject($sql);
        if (!is_object($role_xinfa)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Mục này không còn thuộc về bạn',
            ])));
        }
        $role_xinfa->row = Helpers::getXinfaRowByXinfaId($role_xinfa->xinfa_id);
        cache()->set('role_transaction_' . $request->roleId, [
            'to' => $o_role_row->id,
            'kind' => 4,
            'id' => intval($role_xinfa->id),
            'number' => 1,
            'price' => $price,
        ]);
        cache()->rPush('role_broadcast_' . $o_role_row->id, [
            'kind' => 8,
            'content' => $request->roleRow->name . 'Muốn bán Một quyển' . $role_xinfa->row->name . 'Đây nhé, đấu giá' . Helpers::getHansMoney($price) . ', bạn có đồng ý giao dịch không?',
            'view_url' => 'Func/Transaction/viewXinfa/' . $request->roleId . '/' . $role_xinfa->id,
            'consent_url' => 'Func/Transaction/consentXinfa/' . $request->roleId . '/' . $role_xinfa->id,
            'refuse_url' => 'Func/Transaction/refuse/' . $request->roleId,
        ]);

        return $connection->send(\cache_response($request, \view('Func/Transaction/xinfaPost.twig', [
            'request' => $request,
            'o_role_row' => $o_role_row,
            'role_xinfa' => $role_xinfa,
            'price' => Helpers::getHansMoney($price),
        ])));
    }


    /**
     * Tâm Pháp
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     * @param int $o_role_xinfa_id
     *
     * @return bool|null
     */
    public function viewXinfa(TcpConnection $connection, Request $request, int $o_role_id, int $o_role_xinfa_id)
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

        /**
         * Xem xét对方Giao dịch状态
         *
         */
        $transaction = cache()->get('role_transaction_' . $o_role_id);
        if ($transaction['to'] !== $request->roleId or $transaction['kind'] !== 4 or $transaction['id'] !== $o_role_xinfa_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Giao dịch đã bị hủy bởi bên kia',
            ])));
        }

        /**
         * 获取可以Giao dịch的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $o_role_xinfa_id AND `role_id` = $o_role_id;
SQL;

        $o_role_xinfa = Helpers::queryFetchObject($sql);
        if (!is_object($o_role_xinfa)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Vật phẩm giao dịch đã biến mất',
            ])));
        }
        $o_role_xinfa->row = Helpers::getXinfaRowByXinfaId($o_role_xinfa->xinfa_id);
        if ($o_role_xinfa->row->skill_id) {
            $o_role_xinfa->skill = Helpers::getSkillRowBySkillId($o_role_xinfa->row->skill_id);
        }
        if ($o_role_xinfa->row->sect_id) {
            $o_role_xinfa->sect = Helpers::getSect($o_role_xinfa->row->sect_id);
        } else {
            $o_role_xinfa->sect = 'Người bình thường';
        }
        $o_role_xinfa->need_experience = $o_role_xinfa->lv * $o_role_xinfa->lv * $o_role_xinfa->base_experience;
        return $connection->send(\cache_response($request, \view('Func/Transaction/viewXinfa.twig', [
            'request' => $request,
            'o_role_row' => $o_role_row,
            'o_role_xinfa' => $o_role_xinfa,
            'consent_url' => 'Func/Transaction/consentXinfa/' . $o_role_id . '/' . $o_role_xinfa_id,
            'refuse_url' => 'Func/Transaction/refuse/' . $o_role_id,
            'price' => Helpers::getHansMoney($transaction['price']),
        ])));
    }


    /**
     * 物品 Đồng ý giao dịch
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $o_role_id
     * @param int $o_role_xinfa_id
     *
     * @return bool|null
     */
    public function consentXinfa(TcpConnection $connection, Request $request, int $o_role_id, int $o_role_xinfa_id)
    {
        if (!cache()->set('lock_role_xinfa_' . $o_role_xinfa_id, 'ok', ['NX', 'PX' => 50])) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Giao dịch đã bị hủy',
            ])));
        }


        if (!cache()->set('lock_role_bank_' . $o_role_id, 'ok', ['NX', 'EX' => 50])) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Giao dịch đã bị hủy',
            ])));
        }

        $sql = <<<SQL
SELECT count(`id`) as num FROM `role_xinfas` WHERE `role_id` = $request->roleId;
SQL;

        $result = Helpers::queryFetchObject($sql);
        if ($result->num >= 10) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ba lô đã đầy và không thể chấp nhận được',
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

        /**
         * Xem xét对方Giao dịch状态
         *
         */
        $transaction = cache()->get('role_transaction_' . $o_role_id);
        if ($transaction['to'] !== $request->roleId or $transaction['kind'] !== 4 or $transaction['id'] !== $o_role_xinfa_id) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Giao dịch đã bị hủy bởi bên kia',
            ])));
        }

        /**
         * 获取可以Giao dịch的物品
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $o_role_xinfa_id AND `role_id` = $o_role_id AND `practiced` = 0 AND `equipped` = 0 AND `is_sell` = 0;
SQL;

        $o_role_xinfa = Helpers::queryFetchObject($sql);
        if (!is_object($o_role_xinfa)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Tâm lý giao dịch đã biến mất',
            ])));
        }

        /**
         * 查询金钱是否足够
         *
         */
        if ($request->roleRow->bank_balance < $transaction['price']) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Tiền tiết kiệm của bạn trong ngân hàng không đủ',
            ])));
        }

        /**
         * 再次Xác nhậnGiao dịch状态
         *
         */
        $transaction_confirm = cache()->get('role_transaction_' . $o_role_id);
        if ($transaction['to'] !== $transaction_confirm['to'] or $transaction['kind'] !== $transaction_confirm['kind']
            or $transaction['id'] !== $transaction_confirm['id'] or $transaction['number'] !== $transaction_confirm['number']
            or $transaction['price'] !== $transaction_confirm['price']) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Giao dịch đã bị hủy bởi bên kia',
            ])));
        }

        /**
         * 转移物品、财产
         */

        $sql = <<<SQL
UPDATE `roles` SET `bank_balance` = `bank_balance` + {$transaction['price']} WHERE `id` = $o_role_id;
UPDATE `roles` SET `bank_balance` = `bank_balance` - {$transaction['price']} WHERE `id` = $request->roleId;
UPDATE `role_xinfas` SET `role_id` = $request->roleId WHERE `id` = $o_role_xinfa_id;
SQL;

        Helpers::execSql($sql);
        $o_role_row = Helpers::getRoleRowByRoleId($o_role_id);
        $o_role_row->bank_balance += $transaction['price'];
        Helpers::setRoleRowByRoleId($o_role_id, $o_role_row);
        cache()->rPush('role_broadcast_' . $o_role_id, [
            'kind' => 6,
            'content' => 'bạn và ' . $request->roleRow->name . ' Giao dịch thành công,' . Helpers::getHansMoney($transaction['price']) .
                'Nó đã được tự động gửi vào ngân hàng.',
        ]);

        $request->roleRow->bank_balance -= $transaction['price'];
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);

        $o_role_xinfa->row = Helpers::getxinfaRowByxinfaId($o_role_xinfa->xinfa_id);
        loglog(LOG_TRANSACTIONS_GIVES, 'Giao dịch tâm pháp', [
            '买方玩家' => $request->roleRow->name,
            '卖方玩家' => $o_role_row->name,
            '心法' => $o_role_xinfa->row->name,
            '原始 ID' => $o_role_xinfa->id,
            '金额' => $transaction['price'],
        ]);


        cache()->set('role_flush_weight_' . $o_role_id, true);

        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => $o_role_row->name . 'Tôi sẽ cho bạn một thỏa thuận 本' . $o_role_xinfa->row->name . ',thành công.',
        ])));
    }
}
