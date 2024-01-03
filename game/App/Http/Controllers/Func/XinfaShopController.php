<?php

namespace App\Http\Controllers\Func;

use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 心法商店
 *
 */
class XinfaShopController
{
    /**
     * 售卖列表
     *
     * @var array|int[]
     */
    public static array $xinfas = [61, 83, 92, 116, 104, 132, 36, 22];

    /**
     * 定义价格
     *
     * @var array|int[]
     */
    public static array $xinfasPrice = [
        61  => 100000,
        83  => 100000,
        92  => 100000,
        116 => 100000,
        104 => 100000,
        132 => 100000,
        36  => 100000,
        22  => 100000,
    ];


    /**
     * 商店首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        $xinfas = array_map(function ($xinfa_id) {
            return Helpers::getXinfaRowByXinfaId($xinfa_id);
        }, self::$xinfas);
        foreach ($xinfas as $xinfa) {
            $xinfa->price = Helpers::getHansMoney(self::$xinfasPrice[$xinfa->id]);
            $xinfa->viewUrl = 'Func/XinfaShop/view/' . $xinfa->id;
        }


        return $connection->send(\cache_response($request, \view('Func/XinfaShop/index.twig', [
            'request' => $request,
            'xinfas'  => $xinfas,
        ])));
    }


    /**
     * Tâm Pháp
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $xinfa_id
     *
     * @return bool|null
     */
    public function view(TcpConnection $connection, Request $request, int $xinfa_id)
    {
        $xinfa = Helpers::getXinfaRowByXinfaId($xinfa_id);
        $xinfa->price = Helpers::getHansMoney(self::$xinfasPrice[$xinfa->id]);
        $xinfa->buyUrl = 'Func/XinfaShop/buy/' . $xinfa->id;

        if (mb_strlen($xinfa->description) > 50) {
            $xinfa->displayDescription = true;
            $xinfa->descriptionUrl = 'Func/XinfaShop/description/' . $xinfa_id;
        } else {
            $xinfa->displayDescription = false;
        }
        $xinfa->description = mb_substr($xinfa->description, 0, 50);

        if ($xinfa->skill_id) {
            $xinfa->skill = Helpers::getSkillRowBySkillId($xinfa->skill_id);
        }
        if ($xinfa->sect_id) {
            $xinfa->sect = Helpers::getSect($xinfa->sect_id);
        } else {
            $xinfa->sect = 'Người bình thường';
        }

        return $connection->send(\cache_response($request, \view('Func/XinfaShop/view.twig', [
            'request' => $request,
            'xinfa'   => $xinfa,
        ])));
    }


    /**
     * Xem xét描述
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $xinfa_id
     *
     * @return bool|null
     */
    public function description(TcpConnection $connection, Request $request, int $xinfa_id)
    {
        $xinfa = Helpers::getXinfaRowByXinfaId($xinfa_id);
        $xinfa->backUrl = 'Func/XinfaShop/view/' . $xinfa->id;
        return $connection->send(\cache_response($request, \view('Func/XinfaShop/description.twig', [
            'request' => $request,
            'xinfa'   => $xinfa,
        ])));
    }


    /**
     * Mua sắm tâm pháp
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $xinfa_id
     *
     * @return bool|null
     */
    public function buy(TcpConnection $connection, Request $request, int $xinfa_id)
    {
        $xinfa = Helpers::getXinfaRowByXinfaId($xinfa_id);
        $price = self::$xinfasPrice[$xinfa->id];

        /**
         * Xem xét金钱是否足够
         *
         */
        $sql = <<<SQL
SELECT `id`, `number` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

        $money = Helpers::queryFetchObject($sql);
        if (!is_object($money)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Không có tiền thì cút đi, đừng ảnh hưởng đến việc kinh doanh của tôi!',
            ])));
        }
        if ($money->number < $price) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Không có tiền thì cút đi, đừng ảnh hưởng đến việc kinh doanh của tôi!',
            ])));
        }

        /**
         * Tâm Pháp背包空间是否足够
         */
        $sql = <<<SQL
SELECT `id` FROM `role_xinfas` WHERE `role_id` = $request->roleId;
SQL;

        $role_xinfas = Helpers::queryFetchAll($sql);
        if (!is_array($role_xinfas)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Có vẻ như chiếc ba lô Xinfa của bạn không thể nhét vừa trong đó được!',
            ])));
        }
        if (count($role_xinfas) >= 10) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi tâm pháp ba lô đã đầy!',
            ])));
        }

        /**
         * 减少金钱 Cho心法
         */
        $max_lv = mt_rand(40, 80);
        $sql = <<<SQL
INSERT INTO `role_xinfas` (`role_id`, `xinfa_id`, `base_experience`, `lv`, `max_lv`) VALUES 
($request->roleId, $xinfa->id, 2, 1,$max_lv);
SQL;

        if ($money->number == $price) {
            $sql .= <<<SQL
DELETE FROM `role_things` WHERE `id` = $money->id;
SQL;

        } else {
            $sql .= <<<SQL
UPDATE `role_things` SET `number` = `number` - $price WHERE `id` = $money->id;
SQL;

        }
        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => 'Ngươi từ cửa hàng tiểu nhị nơi đó mua một quyển ' . $xinfa->name . '.Tiêu phí ' . Helpers::getHansMoney($price) . '！',
        ])));
    }


    /**
     * 卖出心法
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function sellIndex(TcpConnection $connection, Request $request)
    {
        /**
         * 获取所有心法
         *
         */
        $sql = <<<SQL
SELECT `id`, `xinfa_id` FROM `role_xinfas` WHERE `role_id` = $request->roleId AND `equipped` = 0 AND `practiced` = 0 AND `is_sell` = 0;
SQL;

        $role_xinfas = Helpers::queryFetchAll($sql);
        if (!is_array($role_xinfas) or count($role_xinfas) < 1) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Trước mắt trên người của ngươi không có bất luận cái gì có thể bán ra tâm pháp.',
            ])));
        }
        foreach ($role_xinfas as $key => $role_xinfa) {
            $role_xinfa->row = Helpers::getXinfaRowByXinfaId($role_xinfa->xinfa_id);
            if ($role_xinfa->row->experience > 64) {
                unset($role_xinfas[$key]);
                continue;
            }
            if ($role_xinfa->row->experience === 0) {
                $role_xinfa->price = Helpers::getHansMoney(10000);
            } else if ($role_xinfa->row->experience === 8) {
                $role_xinfa->price = Helpers::getHansMoney(20000);
            } else {
                $role_xinfa->price = Helpers::getHansMoney(100000);
            }
            $role_xinfa->sellUrl = 'Func/XinfaShop/sell/' . $role_xinfa->id;
        }
        return $connection->send(\cache_response($request, \view('Func/XinfaShop/sellIndex.twig', [
            'request'     => $request,
            'role_xinfas' => $role_xinfas,
        ])));
    }


    /**
     * 出售心法
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_xinfa_id
     *
     * @return bool|null
     */
    public function sell(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        /**
         * 获取所有心法
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        $role_xinfa = Helpers::queryFetchObject($sql);
        if (!is_object($role_xinfa)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Trước mắt trên người của ngươi không có bất luận cái gì có thể bán ra tâm pháp.',
            ])));
        }
        $role_xinfa->row = Helpers::getXinfaRowByXinfaId($role_xinfa->xinfa_id);
        if ($role_xinfa->row->experience === 0) {
            $role_xinfa->price = 10000;
        } else if ($role_xinfa->row->experience === 8) {
            $role_xinfa->price = 20000;
        } else {
            $role_xinfa->price = 100000;
        }

        /**
         * Xem xét身上是否有钱
         *
         */
        $sql = <<<SQL
SELECT `id`, `number` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

        $money = Helpers::queryFetchObject($sql);
        if (is_object($money)) {
            $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` + $role_xinfa->price WHERE `id` = $money->id;
SQL;

        } else {
            $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, 213, $role_xinfa->price);
SQL;

        }
        $sql .= <<<SQL
DELETE FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        Helpers::execSql($sql);
        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => 'Ngươi bán đi một quyển ' . $role_xinfa->row->name . ' cấp cửa hàng tiểu nhị, đạt được ' . Helpers::getHansMoney($role_xinfa->price) . '。',
        ])));
    }
}
