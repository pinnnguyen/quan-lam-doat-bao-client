<?php


namespace App\Http\Controllers\Map;


use App\Libs\Attrs\FlushRoleAttrs;
use App\Libs\Helpers;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;


/**
 * 地图商店
 */
class ShopController
{
    /**
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $shop_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function index(TcpConnection $connection, Request $request, int $shop_id)
    {
        $shop = Helpers::getShop($shop_id);

        $things = [];
        foreach ($shop as $key => $thing_id) {
            $things[$key] = Helpers::getThingRowByThingId($thing_id);
            $things[$key]->viewUrl = 'Map/Shop/view/' . $shop_id . '/' . $thing_id;
        }

        return $connection->send(\cache_response($request, \view('Map/Shop/index.twig', [
            'request' => $request,
            'things'  => $things,
        ])));
    }


    /**
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $shop_id
     * @param int           $thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function view(TcpConnection $connection, Request $request, int $shop_id, int $thing_id)
    {
        $thing = Helpers::getThingRowByThingId($thing_id);
        return $connection->send(\cache_response($request, \view('Map/Shop/view.twig', [
            'request'      => $request,
            'thing'        => $thing,
            'backUrl'      => 'Map/Shop/index/' . $shop_id,
            'buyUrl'       => 'Map/Shop/buy/' . $shop_id . '/' . $thing_id,
            'buyAUrl'      => 'Map/Shop/buy/' . $shop_id . '/' . $thing_id . '/1',
            'buyFiveUrl'   => 'Map/Shop/buy/' . $shop_id . '/' . $thing_id . '/5',
            'buyTwentyUrl' => 'Map/Shop/buy/' . $shop_id . '/' . $thing_id . '/20',
            'buyFiftyUrl'  => 'Map/Shop/buy/' . $shop_id . '/' . $thing_id . '/50',
        ])));
    }


    /**
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $shop_id
     * @param int           $thing_id
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function buy(TcpConnection $connection, Request $request, int $shop_id, int $thing_id, int $number = 0)
    {
        if (($number == 1 or $number == 5 or $number == 20 or $number == 50) or ($number == 0 && strtoupper($request->method()) == 'POST')) {
            if ($number == 0) {
                if ($request->post('number')) {
                    if (is_numeric($request->post('number'))) {
                        $number = intval($request->post('number'));
                        if ($number < 1) {
                            $number = 1;
                        }
                    } else {
                        $number = 1;
                    }
                } else {
                    $number = 1;
                }
            }
            $thing = Helpers::getThingRowByThingId($thing_id);
            if ($thing->kind !== '药品') {
                $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
                $weight = intval($number * $thing->weight);
                if ($weight + $role_attrs->weight >= 100000000) {
                    return $connection->send(\cache_response($request, \view('Base/message.twig', [
                        'request' => $request,
                        'message' => 'Ngươi ba lô trang không dưới nhiều như vậy vật phẩm.',
                    ])));
                }
            }
            $price = intval($number * $thing->money);

            /**
             * 查询玩家余额
             */
            $sql = <<<SQL
SELECT `id`, `number` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

            $money = Helpers::queryFetchObject($sql);
            if ($money) {
                if ($money->number >= $price) {
                    if ($thing->kind == '药品') {
                        // 是否已经有了
                        $sql = <<<SQL
SELECT * FROM `role_drugs` WHERE `role_id` = $request->roleId AND `thing_id` = $thing->id;
SQL;

                        $role_drug = Helpers::queryFetchObject($sql);
                        if ($role_drug) {
                            $sql = <<<SQL
UPDATE `role_drugs` SET `number` = `number` + $number WHERE `role_id` = $request->roleId AND `thing_id` = $thing->id;
SQL;

                        } else {
                            $sql = <<<SQL
INSERT INTO `role_drugs` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, $thing->id, $number);
SQL;

                        }
                    } else {
                        $sql = str_repeat(<<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`, `status`, `durability`) VALUES ($request->roleId, $thing->id, 1, 4, $thing->max_durability);
SQL,

                            $number);
                    }

                    // 减少金钱
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

                    $message = 'Ngươi dùng ' . Helpers::getHansMoney($price) . ' Mua sắm ' . Helpers::getHansNumber($number) . $thing->unit . $thing->name . '。';
                    if ($thing->kind !== '药品') {
                        FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);
                    }
                } else {
                    $message = 'Không có tiền tránh ra, đừng ảnh hưởng ta làm buôn bán!';
                }
            } else {
                $message = 'Không có tiền tránh ra, đừng ảnh hưởng ta làm buôn bán!';
            }
        } else {
            $message = '什么也没有购买！';
        }
        return $connection->send(\cache_response($request, \view('Map/Shop/buy.twig', [
            'request' => $request,
            'message' => $message,
            'backUrl' => 'Map/Shop/index/' . $shop_id,
        ])));
    }


    /**
     * 卖出物品
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sellIndex(TcpConnection $connection, Request $request)
    {
        /**
         * 获取我的所有物品
         */
        $sql = <<<SQL
SELECT `role_things`.`id`, `thing_id`, `number`, `status`, `things`.`name`, `things`.`unit`, `things`.`money` FROM `role_things` INNER JOIN `things` ON `role_things`.`thing_id` = `things`.`id`  AND `things`.`kind` = '装备' WHERE `role_id` = $request->roleId AND `equipped` = 0;
SQL;

        $role_things = Helpers::queryFetchAll($sql);
        if (empty($role_things)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi không có có thể bán ra vật phẩm.',
            ])));
        }

//        $discounts = [0.1, 0.2, 0.3, 0.4, 0.5];
//         $discounts = [0.02, 0.04, 0.06, 0.08, 0.1];
        $discounts = [0.05, 0.1, 0.15, 0.20, 0.25];
        foreach ($role_things as $role_thing) {
            $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
            $role_thing->sellUrl = 'Map/Shop/sellQuestion/' . $role_thing->id;
            $role_thing->sellPrice = intval($discounts[$role_thing->status] * $role_thing->money);
        }

        return $connection->send(\cache_response($request, \view('Map/Shop/sellIndex.twig', [
            'request'     => $request,
            'role_things' => $role_things,
        ])));
    }


    /**
     * 卖出物品询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sellQuestion(TcpConnection $connection, Request $request, int $role_thing_id)
    {
        /**
         * 获取我的所有物品
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
//        $discounts = [0.1, 0.2, 0.3, 0.4, 0.5];
//         $discounts = [0.02, 0.04, 0.06, 0.08, 0.1];
        $discounts = [0.05, 0.1, 0.15, 0.20, 0.25];
        $role_thing->sellPrice = intval($discounts[$role_thing->status] * $role_thing->row->money);
        $role_thing->sellPostUrl = 'Map/Shop/sellPost/' . $role_thing_id;

        return $connection->send(\cache_response($request, \view('Map/Shop/sellQuestion.twig', [
            'request'    => $request,
            'role_thing' => $role_thing,
        ])));
    }


    /**
     * 卖出物品询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sellPost(TcpConnection $connection, Request $request, int $role_thing_id)
    {
        /**
         * 获取我的所有物品
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);

        $sql = <<<SQL
DELETE FROM `role_things` WHERE `id` = $role_thing_id;
SQL;
javascript:;

        Helpers::execSql($sql);


        if (empty($role_thing)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }

        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
//        $discounts = [0.1, 0.2, 0.3, 0.4, 0.5];
//         $discounts = [0.02, 0.04, 0.06, 0.08, 0.1];
        $discounts = [0.05, 0.1, 0.15, 0.20, 0.25];
        $role_thing->sellPrice = intval($discounts[$role_thing->status] * $role_thing->row->money);

        /**
         * 存入
         */
        $request->roleRow->bank_balance += $role_thing->sellPrice;
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        $sql = <<<SQL
UPDATE `roles` SET `bank_balance` = `bank_balance` + $role_thing->sellPrice WHERE `id` = $request->roleId;
SQL;


        Helpers::execSql($sql);


        /**
         * 更新负重
         */
        FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);

        return $connection->send(\cache_response($request, \view('Map/Shop/sellPost.twig', [
            'request'    => $request,
            'role_thing' => $role_thing,
        ])));
    }
}
