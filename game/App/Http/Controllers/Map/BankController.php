<?php

namespace App\Http\Controllers\Map;

use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 钱庄
 *
 */
class BankController
{
    /**
     * 查旬存款
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $npc_id
     *
     * @return bool|null
     */
    public function check(TcpConnection $connection, Request $request, int $npc_id = 0)
    {
        if ($npc_id == 0) {
            $message = 'Lặng lẽ nói cho ngươi:';
        } else {
            $npc = Helpers::getNpcRowByNpcId($npc_id);
            $message = $npc->name . 'Lặng lẽ nói cho ngươi:';
        }
        if ($request->roleRow->bank_balance > 0) {
            $message .= 'Ngài ở tệ hiệu buôn cùng tồn tại có' . Helpers::getHansMoney($request->roleRow->bank_balance) . '。';
        } else {
            $message .= 'Ngài ở tệ hiệu buôn chưa tồn trả tiền.';
        }
        return $connection->send(\cache_response($request, \view('Map/Bank/check.twig', [
            'request' => $request,
            'message' => $message,
        ])));
    }


    /**
     * 存款首页
     *
     * @param TcpConnection $connection
     * @param Request $request
     *
     * @return bool|null
     */
    public function saveIndex(TcpConnection $connection, Request $request)
    {
        $sql = <<<SQL
SELECT `id`, `number` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        if ($role_thing) {
            $bag_money = $role_thing->number;
        } else {
            $bag_money = 0;
        }
        return $connection->send(\cache_response($request, \view('Map/Bank/saveIndex.twig', [
            'request' => $request,
            'bag_money' => $bag_money,
        ])));
    }


    /**
     * 存钱询问界面
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $money_kind
     *
     * @return bool|null
     */
    public function save(TcpConnection $connection, Request $request, int $money_kind = 1)
    {
        if ($money_kind == 1) {
            $unit = 'Lưỡng hoàng kim';
            $save_post_url = 'Map/Bank/savePost/1';
        } elseif ($money_kind == 2) {
            $unit = 'Lưỡng bạch ngân';
            $save_post_url = 'Map/Bank/savePost/2';
        } else {
            $unit = 'Văn đồng tiền';
            $save_post_url = 'Map/Bank/savePost/3';
        }
        $sql = <<<SQL
SELECT `id`, `number` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        if ($role_thing) {
            if ($money_kind == 1) {
                $bag_money = intdiv($role_thing->number, 10000) * 10000;
            } elseif ($money_kind == 2) {
                $bag_money = intdiv($role_thing->number % 10000, 100) * 100;
            } else {
                $bag_money = $role_thing->number % 100;
            }
        } else {
            $bag_money = 0;
        }
        return $connection->send(\cache_response($request, \view('Map/Bank/save.twig', [
            'request' => $request,
            'bag_money' => $bag_money,
            'savePostUrl' => $save_post_url,
            'unit' => $unit,
        ])));
    }


    /**
     * 提交存款
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $money_kind
     *
     * @return bool|null
     */
    public function savePost(TcpConnection $connection, Request $request, int $money_kind = 1)
    {
        if (!cache()->set('lock_role_bank_' . $request->roleId, 'ok', ['NX', 'EX' => 10])) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngài ở tệ hiệu buôn chưa tồn trả tiền.',
            ])));
        }
        if (strtoupper($request->method()) !== 'POST') {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $number = trim($request->post('number'));
        if (!is_numeric($number)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $number = intval($number);
        if ($number < 1) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        if ($money_kind == 1) {
            $tong_number = $number * 10000;
        } elseif ($money_kind == 2) {
            $tong_number = $number * 100;
        } else {
            $tong_number = $number;
        }
        $sql = <<<SQL
SELECT `id`, `number` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        if ($role_thing) {
            $bag_money = $role_thing->number;
        } else {
            $bag_money = 0;
        }

        if ($tong_number > $bag_money) {
            if ($money_kind == 1) {
                $message = 'Trên người của ngươi mang hoàng kim không đủ!';
            } elseif ($money_kind == 2) {
                $message = 'Trên người của ngươi mang bạc trắng không đủ!';
            } else {
                $message = 'Trên người của ngươi mang đồng tiền không đủ!';
            }
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => $message,
            ])));
        }

        /**
         * 存入
         *
         */
        $request->roleRow->bank_balance += $tong_number;
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        $sql = <<<SQL
UPDATE `roles` SET `bank_balance` = `bank_balance` + $tong_number WHERE `id` = $request->roleId;
SQL;


        /**
         * 减少背包
         *
         */
        if ($tong_number < $bag_money) {
            $sql .= <<<SQL
UPDATE `role_things` SET `number` = `number` - $tong_number WHERE `id` = $role_thing->id;
SQL;

        } else {
            $sql .= <<<SQL
DELETE FROM `role_things` WHERE `id` = $role_thing->id;
SQL;

        }

        Helpers::execSql($sql);


        if ($money_kind == 1) {
            $message = 'Bạn lấy ra ' . Helpers::getHansNumber($number) . 'Hai hoàng kim, tồn vào tiền trang.';
        } elseif ($money_kind == 2) {
            $message = 'Bạn lấy ra ' . Helpers::getHansNumber($number) . 'Lượng bạc trắng, tồn vào tiền trang.';
        } else {
            $message = 'Bạn lấy ra ' . Helpers::getHansNumber($number) . 'Văn đồng tiền, tồn vào tiền trang.';
        }
        cache()->set('role_flush_weight_' . $request->roleId, true);
        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => $message,
        ])));
    }


    /**
     * 取款首页
     *
     * @param TcpConnection $connection
     * @param Request $request
     *
     * @return bool|null
     */
    public function withdrawIndex(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Map/Bank/withdrawIndex.twig', [
            'request' => $request,
            'balance' => $request->roleRow->bank_balance,
        ])));
    }


    /**
     * 取钱询问界面
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $money_kind
     *
     * @return bool|null
     */
    public function withdraw(TcpConnection $connection, Request $request, int $money_kind = 1)
    {
        if ($money_kind == 1) {
            $unit = 'Hai hoàng kim';
            $withdraw_post_url = 'Map/Bank/withdrawPost/1';
        } elseif ($money_kind == 2) {
            $unit = 'Lượng bạc trắng';
            $withdraw_post_url = 'Map/Bank/withdrawPost/2';
        } else {
            $unit = 'Văn đồng tiền';
            $withdraw_post_url = 'Map/Bank/withdrawPost/3';
        }

        return $connection->send(\cache_response($request, \view('Map/Bank/withdraw.twig', [
            'request' => $request,
            'balance' => $request->roleRow->bank_balance,
            'withdrawPostUrl' => $withdraw_post_url,
            'unit' => $unit,
        ])));
    }


    /**
     * 提交取款
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $money_kind
     *
     * @return bool|null
     */
    public function withdrawPost(TcpConnection $connection, Request $request, int $money_kind = 1)
    {
        if (!cache()->set('lock_role_bank_' . $request->roleId, 'ok', ['NX', 'EX' => 10])) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Mỗi mười giây chỉ có thể thao tác một lần',
            ])));
        }
        if (strtoupper($request->method()) !== 'POST') {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $number = trim($request->post('number'));
        if (!is_numeric($number)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $number = intval($number);
        if ($number < 1) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        if ($money_kind == 1) {
            $tong_number = $number * 10000;
        } elseif ($money_kind == 2) {
            $tong_number = $number * 100;
        } else {
            $tong_number = $number;
        }

        $sql = <<<SQL
SELECT `bank_balance` FROM `roles` WHERE `id` = $request->roleId;
SQL;

        $role = Helpers::queryFetchObject($sql);

        if ($tong_number > $role->bank_balance) {
            if ($money_kind == 1) {
                $message = 'Trên người của ngươi mang hoàng kim không đủ!';
            } elseif ($money_kind == 2) {
                $message = 'Trên người của ngươi mang bạc trắng không đủ!';
            } else {
                $message = 'Trên người của ngươi mang đồng tiền không đủ!';
            }
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => $message,
            ])));
        }

        /**
         * 取出
         *
         */
        $request->roleRow->bank_balance -= $tong_number;
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);

        /**
         * 增加背包
         *
         */
        $sql = <<<SQL
SELECT `id`, `number` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        if ($role_thing) {
            $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` + $tong_number WHERE `id` = $role_thing->id;
SQL;

        } else {
            $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, 213, $tong_number);
SQL;

        }
        $sql .= <<<SQL
UPDATE `roles` SET `bank_balance` = `bank_balance` - $tong_number WHERE `id` = $request->roleId;
SQL;


        Helpers::execSql($sql);


        if ($money_kind == 1) {
            $message = 'Ngươi từ tiền trang lấy ra ' . Helpers::getHansNumber($number) . 'Hai hoàng kim。';
        } elseif ($money_kind == 2) {
            $message = 'Ngươi từ tiền trang lấy ra ' . Helpers::getHansNumber($number) . 'Lượng bạc trắng。';
        } else {
            $message = 'Ngươi từ tiền trang lấy ra ' . Helpers::getHansNumber($number) . 'Văn đồng tiền。';
        }
        cache()->set('role_flush_weight_' . $request->roleId, true);
        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => $message,
        ])));
    }
}
