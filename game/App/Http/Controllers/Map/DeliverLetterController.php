<?php

namespace App\Http\Controllers\Map;

use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 送信
 *
 */
class DeliverLetterController
{
    /**
     * 领取送信任务
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function receive(TcpConnection $connection, Request $request)
    {
        /**
         * 检查Tu vi
         *
         */
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->experience > 40000) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngài đã là nhân vật thành danh, sao dám lao ngài đại giá.',
            ])));
        }

        /**
         * 检查身上有没有书信
         *
         */
        $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `is_letter` = 1;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);

        if ($role_thing) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Vị tiểu huynh đệ này, ngươi còn có thư từ không đưa, mau đi truyền tin đi!',
            ])));
        }

        /**
         * 设定 地方 和 人物
         *
         */
        $deliver_letter_target = Helpers::getDeliverLetterTarget();
        $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `is_letter`, `letter_npc_id`, `letter_map_id`) VALUES ($request->roleId, 1, {$deliver_letter_target['npc_id']}, {$deliver_letter_target['map_id']});
SQL;


        Helpers::execSql($sql);


        return $connection->send(\cache_response($request, \view('Map/DeliverLetter/receive.twig', [
            'request' => $request,
            'map'     => Helpers::getMapRowByMapId($deliver_letter_target['map_id']),
            'npc'     => Helpers::getNpcRowByNpcId($deliver_letter_target['npc_id']),
        ])));
    }
}
