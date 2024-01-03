<?php

namespace App\Http\Controllers\Map;

use App\Libs\Helpers;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * Kiếm Tiền
 */
class PickUpMoneyController
{
    /**
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function index(TcpConnection $connection, Request $request)
    {
        $status = mt_rand(0, 2);
        $not_found = true;
        if ($status === 0) {
            $money = mt_rand(3, 10);
            $message = 'Ngươi mở ra bụi cỏ, tìm được rồi ' . Helpers::getHansMoney($money) . '。';
            $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

            $role_thing = Helpers::queryFetchObject($sql);
            if ($role_thing) {
                $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` + $money WHERE `id` = $role_thing->id;
SQL;

            } else {
                $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, 213, $money);
SQL;

            }

            Helpers::execSql($sql);

            $not_found = false;
        } elseif ($status === 1) {
            $message = 'Ngươi phiên tới phiên đi, đem thảm cỏ đều mở ra, cũng không có phát hiện một văn tiền.';
        } else {
            $message = 'Ngươi tả phiên hữu phiên, bụi cỏ đều phiên lạn, cũng không có tìm được một văn tiền.';
        }
        return $connection->send(\cache_response($request, \view('Map/PickUpMoney/index.twig', [
            'request'  => $request,
            'message'  => $message,
            'notFound' => $not_found,
        ])));
    }
}
