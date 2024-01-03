<?php

namespace App\Http\Controllers\Map;

use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 坐船
 *
 */
class ShipController
{
    /**
     * 大船
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $map_id
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request, int $map_id)
    {
        if ($map_id == 344) {
            return $connection->send(\cache_response($request, \view('Map/Ship/index.twig', [
                'request'    => $request,
                'name'       => 'Đông Hải Đào Hoa Đảo',
                'message'    => 'Đào Hoa Đảo hàng năm sương mù tràn ngập, ngoại lai khách nhân một không cẩn thận liền sẽ lạc đường.',
                'aboard_url' => 'Map/Ship/aboard/' . $map_id,
            ])));
        }
    }


    /**
     * Lên thuyền
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $map_id
     *
     * @return bool|null
     */
    public function aboard(TcpConnection $connection, Request $request, int $map_id)
    {
        /**
         * 获取随身银两
         *
         */
        $sql = <<<SQL
SELECT `id`, `number` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);

        if (empty($role_thing) or $role_thing->number < 10000) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người chèo thuyền đối với ngươi nói: Vị này khách quan, ngài tiền không đủ nha, tại hạ không thể độ ngài quá hải.',
            ])));
        }
        return $connection->send(\cache_response($request, \view('Map/Ship/prepare.twig', [
            'request'   => $request,
            'start_url' => 'Map/Ship/start/' . $map_id,
        ])));
    }


    /**
     * Tiếp tục
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $map_id
     *
     * @return bool|null
     */
    public function start(TcpConnection $connection, Request $request, int $map_id)
    {
        /**
         * 获取随身银两
         *
         */
        $sql = <<<SQL
SELECT `id`, `number` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);

        if (empty($role_thing) or $role_thing->number < 10000) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người chèo thuyền đối với ngươi nói: Vị này khách quan, ngài tiền không đủ nha, tại hạ không thể độ ngài quá hải.',
            ])));
        }

        /**
         * 扣除金钱
         */
        if ($role_thing->number > 10000) {
            $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` - 10000 WHERE `id` = $role_thing->id;
SQL;

        } else {
            $sql = <<<SQL
DELETE FROM `role_things` WHERE `id` = $role_thing->id;
SQL;

        }
        Helpers::execSql($sql);

        $messages = [
            'Ngươi ở trên biển đi thật lâu thật lâu......',
            'Người chèo thuyền đột nhiên nói: Khách quan, phía trước chính là Đào Hoa Đảo......',
            'Ngươi đi tới Đào Hoa Đảo.',
        ];

        if ($map_id == 344) {
            $messages = [
                'Ngươi ở trên biển đi thật lâu thật lâu......',
                'Người chèo thuyền đột nhiên nói: Khách quan, phía trước chính là Đào Hoa Đảo......',
                'Ngươi đi tới Đào Hoa Đảo.',
            ];
        }

        cache()->rPush('role_messages_' . $request->roleId, ...$messages);

        /**
         * 删除地图玩家自身记录、添加自己到新地图玩家记录
         *
         */
        cache()->sRem('map_roles_' . $request->roleRow->map_id, $request->roleId);
        $request->roleRow->map_id = $map_id;
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        cache()->sAdd('map_roles_' . $request->roleRow->map_id, $request->roleId);

        return (new IndexController())->index($connection, $request);
    }
}
