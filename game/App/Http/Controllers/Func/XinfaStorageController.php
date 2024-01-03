<?php

namespace App\Http\Controllers\Func;

use App\Http\Controllers\Role\ShopController;
use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 心法仓库
 *
 */
class XinfaStorageController
{
    /**
     * 心法首页
     *
     * @param TcpConnection $connection
     * @param Request $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        /**
         * 获取
         */
        $sql = <<<SQL
SELECT * FROM `role_xinfas_storage` WHERE `role_id` = $request->roleId;
SQL;

        $xinfas = Helpers::queryFetchAll($sql);

        if (is_array($xinfas)) {
            foreach ($xinfas as $xinfa) {
                $xinfa->row = Helpers::getXinfaRowByXinfaId($xinfa->xinfa_id);
                $xinfa->popUrl = 'Func/XinfaStorage/pop/' . $xinfa->id;
            }
            $count = count($xinfas);
        }

        return $connection->send(\cache_response($request, \view('Func/XinfaStorage/index.twig', [
            'request' => $request,
            'count' => $count ?? 0,
            'xinfas' => $xinfas,
        ])));
    }


    /**
     * 取出
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $storage_id
     *
     * @return bool|null
     */
    public function pop(TcpConnection $connection, Request $request, int $storage_id)
    {
        if (!cache()->set('lock_role_xinfa_storage_' . $request->roleId, 'ok', ['NX', 'PX' => 30])) {
            return $connection->send(\cache_response($request, \view('Func/XinfaStorage/message.twig', [
                'request' => $request,
                'message' => 'Tạm thời không thể lấy ra tâm pháp',
            ])));
        }

        $sql = <<<SQL
SELECT * FROM `role_xinfas_storage` WHERE `id` = $storage_id;
SQL;

        $xinfa = Helpers::queryFetchObject($sql);

        if (empty($xinfa)) {
            return $connection->send(\cache_response($request, \view('Func/XinfaStorage/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }

        $xinfa->row = Helpers::getXinfaRowByXinfaId($xinfa->xinfa_id);

        /**
         * Tâm Pháp背包容量是否足够
         *
         */
        $sql = <<<SQL
SELECT count(`id`) as count FROM `role_xinfas` WHERE `role_id` = $request->roleId;
SQL;

        $count = Helpers::queryFetchObject($sql);
        if ($count->count >= 10) {
            return $connection->send(\cache_response($request, \view('Func/XinfaStorage/message.twig', [
                'request' => $request,
                'message' => 'Ngài trước mặt tâm pháp ba lô không gian đã mãn, thỉnh kịp thời rửa sạch.',
            ])));
        }

        /**
         * 取出、删除
         */
        $sql = <<<SQL
DELETE FROM `role_xinfas_storage` WHERE `id` = $storage_id;
INSERT INTO `role_xinfas` (`role_id`, `xinfa_id`, `lv`, `max_lv`, `base_experience`, `experience`, `private_name`) 
VALUES ($request->roleId, $xinfa->xinfa_id, $xinfa->lv, $xinfa->max_lv, $xinfa->base_experience, $xinfa->experience, '$xinfa->private_name');
SQL;

        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Func/XinfaStorage/message.twig', [
            'request' => $request,
            'message' => 'Ngươi lấy ra một quyển ' . $xinfa->row->name . '。',
        ])));
    }


    /**
     * 存入首页
     *
     * @param TcpConnection $connection
     * @param Request $request
     *
     * @return bool|null
     */
    public function pushIndex(TcpConnection $connection, Request $request)
    {
        /**
         * 获取
         */
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `role_id` = $request->roleId AND `equipped` = 0 AND `is_sell` = 0 AND `practiced` = 0;
SQL;

        $xinfas = Helpers::queryFetchAll($sql);

        if (is_array($xinfas)) {
            foreach ($xinfas as $xinfa) {
                $xinfa->row = Helpers::getXinfaRowByXinfaId($xinfa->xinfa_id);
                $xinfa->pushUrl = 'Func/XinfaStorage/push/' . $xinfa->id;
            }
        }

        return $connection->send(\cache_response($request, \view('Func/XinfaStorage/pushIndex.twig', [
            'request' => $request,
            'xinfas' => $xinfas,
        ])));
    }


    /**
     * 存入
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function push(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        if (!cache()->set('lock_role_xinfa_storage_' . $request->roleId, 'ok', ['NX', 'PX' => 30])) {
            return $connection->send(\cache_response($request, \view('Func/XinfaStorage/message.twig', [
                'request' => $request,
                'message' => 'Tạm thời không thể tồn nhập tâm pháp',
            ])));
        }
        /**
         * 判断仓库容量
         */
        $sql = <<<SQL
SELECT count(`id`) as count FROM `role_xinfas_storage` WHERE `role_id` = $request->roleId;
SQL;

        $count = Helpers::queryFetchObject($sql);
        $count = $count->count;
        if ($count + 1 > $request->roleRow->xinfa_storage) {
            return $connection->send(\cache_response($request, \view('Func/XinfaStorage/message.twig', [
                'request' => $request,
                'message' => 'Ngài trước mặt kho hàng tâm pháp không gian đã mãn, thỉnh kịp thời rửa sạch.',
            ])));
        }

        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        $xinfa = Helpers::queryFetchObject($sql);

        if (empty($xinfa)) {
            return $connection->send(\cache_response($request, \view('Func/XinfaStorage/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }


        if ($xinfa->role_id != $request->roleId) {
            return $connection->send(\cache_response($request, \view('Func/XinfaStorage/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }

        $xinfa->row = Helpers::getXinfaRowByXinfaId($xinfa->xinfa_id);

        /**
         * 取出、删除
         */
        $sql = <<<SQL
DELETE FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
INSERT INTO `role_xinfas_storage` (`role_id`, `xinfa_id`, `lv`, `max_lv`, `base_experience`, `experience`, `private_name`) 
VALUES ($request->roleId, $xinfa->xinfa_id, $xinfa->lv, $xinfa->max_lv, $xinfa->base_experience, $xinfa->experience, '$xinfa->private_name');
SQL;

        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Func/XinfaStorage/message.twig', [
            'request' => $request,
            'message' => 'Ngươi đem ' . $xinfa->row->name . ' tồn vào kho hàng!',
        ])));
    }


    /**
     * 扩展仓库
     *
     * @param TcpConnection $connection
     * @param Request $request
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
            return $connection->send(\cache_response($request, \view('Func/XinfaStorage/message.twig', [
                'request' => $request,
                'message' => 'Ngươi trước mặt không có tơ vàng gỗ nam, mau đi đạo cụ thương thành mua sắm đi!',
            ])));
        }
        return $connection->send(\cache_response($request, \view('Func/XinfaStorage/extendQuestion.twig', [
            'request' => $request,
        ])));
    }


    /**
     * 扩建仓库
     *
     * @param TcpConnection $connection
     * @param Request $request
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
            return $connection->send(\cache_response($request, \view('Func/XinfaStorage/message.twig', [
                'request' => $request,
                'message' => 'Ngươi trước mặt không có tơ vàng gỗ nam, mau đi đạo cụ thương thành mua sắm đi!',
            ])));
        }

        /**
         * 扩建仓库
         *
         */
        $sql = <<<SQL
UPDATE `roles` SET `xinfa_storage` = `xinfa_storage` + 2 WHERE `id` = $request->roleId;
SQL;

        $request->roleRow->xinfa_storage += 2;
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
            $role_dj->row['name'] . '】，将心法仓库扩容至' . $request->roleRow->xinfa_storage . '。';
        $sql .= <<<SQL
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;
        Helpers::execSql($sql);
        return $connection->send(\cache_response($request, \view('Func/XinfaStorage/extendPost.twig', [
            'request' => $request,
        ])));
    }
}
