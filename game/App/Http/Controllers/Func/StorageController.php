<?php

namespace App\Http\Controllers\Func;

use App\Http\Controllers\Role\ShopController;
use App\Libs\Attrs\FlushRoleAttrs;
use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 仓库
 *
 */
class StorageController
{
    /**
     * 仓库首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $page
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request, int $page = 1)
    {
        /**
         * 获取页数
         */
        $sql = <<<SQL
SELECT count(`id`) as count FROM `role_storage` WHERE `role_id` = $request->roleId;
SQL;

        $count = Helpers::queryFetchObject($sql);
        $count = $count->count;
        $page_max = intval(ceil($count / 20));

        if ($page > 1) {
            $last_url = 'Func/Storage/index/' . ($page - 1);
        }
        if ($page < $page_max) {
            $next_url = 'Func/Storage/index/' . ($page + 1);
        }

        $offset = ($page - 1) * 20;

        /**
         * 获取目录
         */
        $sql = <<<SQL
SELECT * FROM `role_storage` WHERE `role_id` = $request->roleId ORDER BY `id` DESC LIMIT $offset, 20;
SQL;

        $things = Helpers::queryFetchAll($sql);

        if (is_array($things)) {
            foreach ($things as $thing) {
                $thing->row = Helpers::getThingRowByThingId($thing->thing_id);
                $thing->viewUrl = 'Func/Storage/view/' . $thing->id;
                $thing->popUrl = 'Func/Storage/pop/' . $thing->id;
            }
        }

        return $connection->send(\cache_response($request, \view('Func/Storage/index.twig', [
            'request'  => $request,
            'last_url' => $last_url ?? null,
            'next_url' => $next_url ?? null,
            'page'     => $page,
            'page_max' => $page_max,
            'count'    => $count,
            'things'   => $things,
        ])));
    }


    /**
     * 搜索
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function search(TcpConnection $connection, Request $request)
    {
        if (strtoupper($request->method()) !== 'POST') {
            return $connection->send(\cache_response($request, \view('Func/Storage/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $name = trim($request->post('name'));
        if (!preg_match('#^[\x{4e00}-\x{9fa5}]+$#u', $name)) {
            return $connection->send(\cache_response($request, \view('Func/Storage/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }

        /**
         * 获取目录
         */
        $sql = <<<SQL
SELECT `role_storage`.`id`, `thing_id` FROM `role_storage`
    INNER JOIN `things` ON `thing_id` = `things`.`id` AND `name` LIKE '%$name%'
WHERE `role_id` = $request->roleId ORDER BY `role_storage`.`id` DESC;
SQL;

        $things = Helpers::queryFetchAll($sql);

        if (is_array($things)) {
            foreach ($things as $thing) {
                $thing->row = Helpers::getThingRowByThingId($thing->thing_id);
                $thing->viewUrl = 'Func/Storage/view/' . $thing->id;
                $thing->popUrl = 'Func/Storage/pop/' . $thing->id;
            }
        }

        return $connection->send(\cache_response($request, \view('Func/Storage/search.twig', [
            'request' => $request,
            'things'  => $things,
        ])));
    }


    /**
     * Xem xét
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $storage_id
     *
     * @return bool|null
     */
    public function view(TcpConnection $connection, Request $request, int $storage_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_storage` WHERE `id` = $storage_id;
SQL;

        $thing = Helpers::queryFetchObject($sql);

        if (empty($thing)) {
            return $connection->send(\cache_response($request, \view('Func/Storage/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));

        }

        $thing->row = Helpers::getThingRowByThingId($thing->thing_id);
        $thing->popUrl = 'Func/Storage/pop/' . $thing->id;
        if ($thing->row->kind == '装备') {
            if ($thing->status > 0) {
                $thing->statusString = str_repeat('*', $thing->status);
            } else {
                $thing->statusString = '×';
            }
        }

        return $connection->send(\cache_response($request, \view('Func/Storage/view.twig', [
            'request' => $request,
            'thing'   => $thing,
        ])));
    }


    /**
     * 取出
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $storage_id
     *
     * @return bool|null
     */
    public function pop(TcpConnection $connection, Request $request, int $storage_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_storage` WHERE `id` = $storage_id;
SQL;

        $thing = Helpers::queryFetchObject($sql);

        if (empty($thing)) {
            return $connection->send(\cache_response($request, \view('Func/Storage/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }

        $thing->row = Helpers::getThingRowByThingId($thing->thing_id);

        /**
         * Xem xét背包容量是否足够
         *
         */
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->weight + $thing->row->weight >= 100000000) {
            return $connection->send(\cache_response($request, \view('Func/Storage/message.twig', [
                'request' => $request,
                'message' => 'Ngài trước mặt ba lô vật phẩm không gian đã mãn, thỉnh kịp thời rửa sạch.',
            ])));
        }

        /**
         * 取出、删除
         */
        $sql = <<<SQL
DELETE FROM `role_storage` WHERE `id` = $storage_id;
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`, `status`, durability) VALUES ($request->roleId, $thing->thing_id, 1, $thing->status, $thing->durability);
SQL;

        Helpers::execSql($sql);

        FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);

        return $connection->send(\cache_response($request, \view('Func/Storage/message.twig', [
            'request' => $request,
            'message' => '你取出一' . $thing->row->unit . $thing->row->name . '。',
        ])));
    }


    /**
     * 存入首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function pushIndex(TcpConnection $connection, Request $request)
    {
        /**
         * 获取页数
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `role_id` = $request->roleId AND `equipped` = 0;
SQL;

        $things = Helpers::queryFetchAll($sql);

        if (is_array($things)) {
            foreach ($things as $key => $thing) {
                if ($thing->thing_id > 0) {
                    $thing->row = Helpers::getThingRowByThingId($thing->thing_id);
                    if (!empty($thing->row->kind) && ($thing->row->kind == '装备' || $thing->row->kind == '书籍')) {
                        $thing->pushUrl = 'Func/Storage/push/' . $thing->id;
                    } else {
                        unset($things[$key]);
                    }
                } else {
                    unset($things[$key]);
                }
            }
        }

        return $connection->send(\cache_response($request, \view('Func/Storage/pushIndex.twig', [
            'request' => $request,
            'things'  => $things,
        ])));
    }


    /**
     * 存入
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_thing_id
     *
     * @return bool|null
     */
    public function push(TcpConnection $connection, Request $request, int $role_thing_id)
    {
        /**
         * 判断仓库容量
         */
        $sql = <<<SQL
SELECT count(`id`) as count FROM `role_storage` WHERE `role_id` = $request->roleId;
SQL;

        $count = Helpers::queryFetchObject($sql);
        $count = $count->count;
        if ($count + 1 > $request->roleRow->storage) {
            return $connection->send(\cache_response($request, \view('Func/Storage/message.twig', [
                'request' => $request,
                'message' => 'Ngài trước mặt kho hàng vật phẩm không gian đã mãn, thỉnh kịp thời rửa sạch.',
            ])));
        }

        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $thing = Helpers::queryFetchObject($sql);

        if (empty($thing)) {
            return $connection->send(\cache_response($request, \view('Func/Storage/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }

        $thing->row = Helpers::getThingRowByThingId($thing->thing_id);

        /**
         * 取出、删除
         */
        $sql = <<<SQL
DELETE FROM `role_things` WHERE `id` = $role_thing_id;
INSERT INTO `role_storage` (`role_id`, `thing_id`, `status`, durability) VALUES ($request->roleId, $thing->thing_id, $thing->status, $thing->durability);
SQL;

        Helpers::execSql($sql);

        FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);

        return $connection->send(\cache_response($request, \view('Func/Storage/message.twig', [
            'request' => $request,
            'message' => 'Ngươi đem ' . $thing->row->name . ' Tồn vào kho hàng!',
        ])));
    }


    /**
     * 扩展仓库
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function extendQuestion(TcpConnection $connection, Request $request)
    {
        $sql = <<<SQL
SELECT * FROM `role_djs` WHERE `role_id` = $request->roleId AND `dj_id` = 8;
SQL;

        $role_dj = Helpers::queryFetchObject($sql);
        if (!is_object($role_dj)) {
            return $connection->send(\cache_response($request, \view('Func/Storage/message.twig', [
                'request' => $request,
                'message' => 'Ngươi trước mặt không có tơ vàng gỗ nam, mau đi đạo cụ thương thành mua sắm đi!',
            ])));
        }
        return $connection->send(\cache_response($request, \view('Func/Storage/extendQuestion.twig', [
            'request' => $request,
        ])));
    }


    /**
     * 扩建仓库
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function extendPost(TcpConnection $connection, Request $request)
    {
        $sql = <<<SQL
SELECT * FROM `role_djs` WHERE `role_id` = $request->roleId AND `dj_id` = 8;
SQL;

        $role_dj = Helpers::queryFetchObject($sql);
        if (!is_object($role_dj)) {
            return $connection->send(\cache_response($request, \view('Func/Storage/message.twig', [
                'request' => $request,
                'message' => 'Ngươi trước mặt không có tơ vàng gỗ nam, mau đi đạo cụ thương thành mua sắm đi!',
            ])));
        }

        /**
         * 扩建仓库
         *
         */
        $sql = <<<SQL
UPDATE `roles` SET `storage` = `storage` + 100 WHERE `id` = $request->roleId;
SQL;

        $request->roleRow->storage += 100;
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        if ($role_dj->number <= 1) {
            $sql .= <<<SQL
DELETE FROM `role_djs` WHERE `id` = $role_dj->id;
SQL;

        } else {
            $sql .= <<<SQL
UPDATE `role_djs` SET `number` = `number` - 1 WHERE `id` = $role_dj->id;
SQL;

        }
        $role_dj->row = ShopController::$djs[$role_dj->dj_id];
        $log = '【使用道具】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') . '】使用道具【' .
            $role_dj->row['name'] . '】，将随身仓库扩容至' . $request->roleRow->storage . '。';
        $sql .= <<<SQL
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;
        Helpers::execSql($sql);
        return $connection->send(\cache_response($request, \view('Func/Storage/extendPost.twig', [
            'request' => $request,
        ])));
    }
}
