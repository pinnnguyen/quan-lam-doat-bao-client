<?php

namespace App\Http\Controllers\Role;

use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 消息
 *
 */
class MessageController
{
    /**
     * Chuyển đổi tin nhắn
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $switch
     *
     * @return bool|null
     */
    public function switch(TcpConnection $connection, Request $request, int $switch = 0)
    {
        if ($switch != 0) {
            switch ($switch) {
                case 1:
                    $request->roleRow->switch_public = 1;
                    $sql = <<<SQL
UPDATE `roles` SET `switch_public` = 1 WHERE `id` = $request->roleId;
SQL;

                    break;
                case 2:
                    $request->roleRow->switch_public = 0;
                    $sql = <<<SQL
UPDATE `roles` SET `switch_public` = 0 WHERE `id` = $request->roleId;
SQL;

                    break;
                case 3:
                    $request->roleRow->switch_stranger = 1;
                    $sql = <<<SQL
UPDATE `roles` SET `switch_stranger` = 1 WHERE `id` = $request->roleId;
SQL;

                    break;
                case 4:
                    $request->roleRow->switch_stranger = 0;
                    $sql = <<<SQL
UPDATE `roles` SET `switch_stranger` = 0 WHERE `id` = $request->roleId;
SQL;

                    break;
                case 5:
                    $request->roleRow->switch_arena = 1;
                    $sql = <<<SQL
UPDATE `roles` SET `switch_arena` = 1 WHERE `id` = $request->roleId;
SQL;

                    break;
                case 6:
                    $request->roleRow->switch_arena = 0;
                    $sql = <<<SQL
UPDATE `roles` SET `switch_arena` = 0 WHERE `id` = $request->roleId;
SQL;

                    break;
                case 7:
                    $request->roleRow->switch_faction = 1;
                    $sql = <<<SQL
UPDATE `roles` SET `switch_faction` = 1 WHERE `id` = $request->roleId;
SQL;

                    break;
                case 8:
                    $request->roleRow->switch_faction = 0;
                    $sql = <<<SQL
UPDATE `roles` SET `switch_faction` = 0 WHERE `id` = $request->roleId;
SQL;

                    break;
                case 9:
                    $request->roleRow->switch_rumour = 1;
                    $sql = <<<SQL
UPDATE `roles` SET `switch_rumour` = 1 WHERE `id` = $request->roleId;
SQL;

                    break;
                case 10:
                    $request->roleRow->switch_rumour = 0;
                    $sql = <<<SQL
UPDATE `roles` SET `switch_rumour` = 0 WHERE `id` = $request->roleId;
SQL;

                    break;
                case 11:
                    $request->roleRow->switch_jianghu = 1;
                    $sql = <<<SQL
UPDATE `roles` SET `switch_jianghu` = 1 WHERE `id` = $request->roleId;
SQL;

                    break;
                default:
                    $request->roleRow->switch_jianghu = 0;
                    $sql = <<<SQL
UPDATE `roles` SET `switch_jianghu` = 0 WHERE `id` = $request->roleId;
SQL;

                    break;
            }
            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);

            Helpers::execSql($sql);

        }
        return $connection->send(\cache_response($request, \view('Role/Message/switch.twig', [
            'request' => $request,
        ])));
    }
}
