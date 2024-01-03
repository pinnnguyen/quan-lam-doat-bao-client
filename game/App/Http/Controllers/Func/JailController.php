<?php
/**
 * @date   2022/4/28 10:37
 * @author pinerge@gmail.com
 */
declare(strict_types=1);

namespace App\Http\Controllers\Func;

use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

class JailController
{
    private int $price = 500000;

    /**
     * @param \Workerman\Connection\TcpConnection $connection
     * @param \Workerman\Protocols\Http\Request $request
     *
     * @return null|bool
     */
    public function bribery(TcpConnection $connection, Request $request)
    {
        $money = ($request->roleRow->release_time - time()) / ($request->roleRow->red * 300) * $request->roleRow->red * $this->price;
        return $connection->send(\cache_response($request, \view('Func/Jail/bribery.twig', [
            'request' => $request,
            'money' => (int)$money,
        ])));
    }


    /**
     * @param \Workerman\Connection\TcpConnection $connection
     * @param \Workerman\Protocols\Http\Request $request
     *
     * @return null|bool
     */
    public function briberyPost(TcpConnection $connection, Request $request)
    {
        if (!cache()->set('lock_role_jail_' . $request->roleId, 'ok', ['NX', 'EX' => 1])) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Vui lòng thử lại',
            ])));
        }

        $money = ($request->roleRow->release_time - time()) / ($request->roleRow->red * 300) * $request->roleRow->red * $this->price;
        $money = (int)$money;
        if ($request->roleRow->bank_balance < $money) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người cai ngục hét lên: Hãy quay lại và ở lại cho khỏe!',
            ])));
        }

        $request->roleRow->bank_balance -= $money;
        $request->roleRow->red = 0;
        $request->roleRow->release_time = 0;
        $sql = <<<SQL
UPDATE `roles` SET `bank_balance` = `bank_balance` - $money, `red` = 0, `release_time` = 0 WHERE `id` = $request->roleId;
SQL;


        Helpers::execSql($sql);

        cache()->sRem('map_roles_' . $request->roleRow->map_id, $request->roleId);
        $request->roleRow->map_id = 6;
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        cache()->sAdd('map_roles_' . $request->roleRow->map_id, $request->roleId);

        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => 'Người cai ngục nhận vàng của bạn: bạn đi đi, lần sau hãy cẩn thận!',
        ])));
    }
}