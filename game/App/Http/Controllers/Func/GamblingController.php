<?php

namespace App\Http\Controllers\Func;

use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 赌博
 *
 */
class GamblingController
{
    /**
     * 玩一把
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function play(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Func/Gambling/play.twig', [
            'request' => $request,
        ])));
    }


    /**
     * 押大小
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $select
     *
     * @return bool|null
     */
    public function select(TcpConnection $connection, Request $request, int $select)
    {
        return $connection->send(\cache_response($request, \view('Func/Gambling/select.twig', [
            'request' => $request,
            'bet_url' => 'Func/Gambling/bet/' . $select,
        ])));
    }


    /**
     * 下注
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $select
     *
     * @return bool|null
     */
    public function bet(TcpConnection $connection, Request $request, int $select)
    {
        /**
         * 判断输入
         *
         */
        if (strtoupper($request->method() !== 'POST')) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Nhập sai, số tiền chỉ có thể là 1-200 Hoàng Kim Lượng',
            ])));
        }
        $number = $request->post('number');
        if (!is_numeric($number)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Nhập sai, số tiền chỉ có thể là 1-200 Hoàng Kim Lượng',
            ])));
        }
        $number = intval($number);
        if ($number < 1) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Nhập sai, số tiền chỉ có thể là 1-200 Hoàng Kim Lượng',
            ])));
        }
        if ($number > 200) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Nhập sai, số tiền chỉ có thể là 1-200 Hoàng Kim Lượng',
            ])));
        }
        $number *= 10000;

        /**
         * 判断银行余额
         *
         */
        $role_row = Helpers::getRoleRowByRoleId($request->roleId);
        if ($role_row->bank_balance < $number) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Tiền trang tiền tiết kiệm không đủ, thỉnh trước đem Hoàng Kim tồn nhập tiền trang.',
            ])));
        }

        START:

        /**
         * 生成结果
         *
         */
        $results = [];
        $results[] = mt_rand(1, 6);
        $results[] = mt_rand(1, 6);
        $results[] = mt_rand(1, 6);

        /**
         * 计算结果
         */
        $count = array_sum($results);
        if ($count > 10) {
            $result = 1;
        } else {
            $result = 2;
        }

        /**
         * 判断结果
         *
         */
        $messages = [];
        $messages[] = 'Mức cược của bạn:' . ($select === 1 ? '大' : '小');
        $messages[] = 'Số tiền bạn đặt cọc:' . Helpers::getHansMoney($number);
        $messages[] = 'Hiện đang mở「' . Helpers::getHansNumber($results[0]) . '」「' . Helpers::getHansNumber($results[1]) . '」「' . Helpers::getHansNumber($results[2]) . '」';
        if (($results[0] === $results[1] and $results[0] === $results[2]) or $result === $select) {
            if (Helpers::getProbability(6, 100)) {
                goto START;
            }
            $messages[] = 'Bạn thắng ' . Helpers::getHansMoney($number) . '。';
            $messages[] = 'Tiền thắng đã được gửi vào tài khoản ngân hàng của bạn.';
            $role_row->bank_balance += $number;
            $sql = <<<SQL
UPDATE `roles` SET `bank_balance` = `bank_balance` + $number WHERE `id` = $request->roleId;
SQL;

        } else {
            $messages[] = '你输了' . Helpers::getHansMoney($number) . '。';
            $messages[] = 'Khoản tiền gửi của bạn đã được khấu trừ khỏi tài khoản ngân hàng của bạn.';
            $role_row->bank_balance -= $number;
            $sql = <<<SQL
UPDATE `roles` SET `bank_balance` = `bank_balance` - $number WHERE `id` = $request->roleId;
SQL;

        }

        /**
         * 保存结果
         *
         */
        Helpers::setRoleRowByRoleId($request->roleId, $role_row);
        Helpers::execSql($sql);
        return $connection->send(\cache_response($request, \view('Func/Gambling/messages.twig', [
            'request'  => $request,
            'messages' => $messages,
        ])));
    }


    /**
     * Xem xét规则
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function rule(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Func/Gambling/rule.twig', [
            'request' => $request,
        ])));

    }
}
