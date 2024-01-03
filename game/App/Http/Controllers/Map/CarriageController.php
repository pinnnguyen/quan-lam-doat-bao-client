<?php

namespace App\Http\Controllers\Map;

use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 马车
 *
 */
class CarriageController
{
    /**
     * 传送点
     *
     * @var array|int[]
     */
    public static array $maps = [
        'Trường An' => 1,
        'Thành đô' => 114,
        'Lạc Dương' => 226,
        'Vũ Hán' => 218,
        'Thượng Hải' => 331,
        'Lâu Lan' => 647,
        'Quảng Châu' => 397,
        'Bắc Kinh' => 303,
        'Lạp Tát' => 187,
        'Đại lý' => 418,
        'Lan Châu' => 621,
    ];

    /**
     * 定义马车
     *
     * @var array|array[]
     */
    public static array $cityCarriages = [
        1  => ['Thành đô', 'Lạc Dương', 'Vũ Hán', 'Thượng Hải', 'Lâu Lan', 'Quảng Châu', 'Bắc Kinh', 'Lạp Tát', 'Đại lý', 'Lan Châu',], // Trường An
        2  => ['Trường An', 'Lạc Dương', 'Vũ Hán', 'Thượng Hải', 'Lâu Lan', 'Quảng Châu', 'Bắc Kinh', 'Lạp Tát', 'Đại lý', 'Lan Châu',], // Thành đô
        3  => ['Trường An', 'Thành đô', 'Vũ Hán', 'Thượng Hải', 'Lâu Lan', 'Quảng Châu', 'Bắc Kinh', 'Lạp Tát', 'Đại lý', 'Lan Châu',], // Lạc Dương
        4  => ['Trường An', 'Thành đô', 'Lạc Dương', 'Thượng Hải', 'Lâu Lan', 'Quảng Châu', 'Bắc Kinh', 'Lạp Tát', 'Đại lý', 'Lan Châu',], // Vũ Hán
        5  => ['Trường An', 'Thành đô', 'Lạc Dương', 'Vũ Hán', 'Lâu Lan', 'Quảng Châu', 'Bắc Kinh', 'Lạp Tát', 'Đại lý', 'Lan Châu',], // Thượng Hải
        6  => ['Trường An', 'Thành đô', 'Lạc Dương', 'Vũ Hán', 'Thượng Hải', 'Quảng Châu', 'Bắc Kinh', 'Lạp Tát', 'Đại lý', 'Lan Châu',], // Lâu Lan
        7  => ['Trường An', 'Thành đô', 'Lạc Dương', 'Vũ Hán', 'Thượng Hải', 'Lâu Lan', 'Bắc Kinh', 'Lạp Tát', 'Đại lý', 'Lan Châu',], // Quảng Châu
        8  => ['Trường An', 'Thành đô', 'Lạc Dương', 'Vũ Hán', 'Thượng Hải', 'Lâu Lan', 'Quảng Châu', 'Lạp Tát', 'Đại lý', 'Lan Châu',], // Bắc Kinh
        9  => ['Trường An', 'Thành đô', 'Lạc Dương', 'Vũ Hán', 'Thượng Hải', 'Lâu Lan', 'Quảng Châu', 'Bắc Kinh', 'Đại lý', 'Lan Châu',], // Lạp Tát
        10 => ['Trường An', 'Thành đô', 'Lạc Dương', 'Vũ Hán', 'Thượng Hải', 'Lâu Lan', 'Quảng Châu', 'Bắc Kinh', 'Lạp Tát', 'Lan Châu',], // Đại lý
        11 => ['Trường An', 'Thành đô', 'Lạc Dương', 'Vũ Hán', 'Thượng Hải', 'Lâu Lan', 'Quảng Châu', 'Bắc Kinh', 'Lạp Tát', 'Đại lý',], // Lan Châu
    ];


    /**
     * 马车首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $carriages_id
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request, int $carriages_id)
    {
        $city_carriages = self::$cityCarriages[$carriages_id];
        $carriages = [];
        foreach ($city_carriages as $city_carriage) {
            $carriages[] = [
                'city_name' => $city_carriage,
                'ride_url'  => 'Map/Carriage/ride/' . self::$maps[$city_carriage],
            ];
        }
        return $connection->send(\cache_response($request, \view('Map/Carriage/index.twig', [
            'request'   => $request,
            'carriages' => $carriages,
        ])));
    }


    /**
     * 乘坐马车
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $map_id
     *
     * @return bool|null
     */
    public function ride(TcpConnection $connection, Request $request, int $map_id)
    {
        /**
         * Xem xét钱庄余额
         *
         */
        if ($request->roleRow->bank_balance < 4000) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Không có tiền tránh ra, đừng ảnh hưởng ta làm buôn bán.',
            ])));
        }

        /**
         * 扣除钱庄银两
         *
         */
        $request->roleRow->bank_balance -= 4000;
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        $sql = <<<SQL
UPDATE `roles` SET `bank_balance` = `bank_balance` - 4000 WHERE `id` = $request->roleId;
SQL;


        Helpers::execSql($sql);


        /**
         * 设定乘坐Bắt đầu时间戳
         *
         */
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        $role_attrs->startCarriageTimestamp = time();
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);

        return $connection->send(\cache_response($request, \view('Map/Carriage/ride.twig', [
            'request'     => $request,
            'waiting_url' => 'Map/Carriage/waiting/' . $map_id,
        ])));
    }


    /**
     * 乘坐Chờ đợi
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $map_id
     *
     * @return bool|null
     */
    public function waiting(TcpConnection $connection, Request $request, int $map_id)
    {
        /**
         * 获取乘坐Bắt đầu时间戳
         *
         */
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if (time() - $role_attrs->startCarriageTimestamp > 5) {
            $city = array_flip(self::$maps)[$map_id];
            $messages = [
                'Xe ngựa lảo đảo lắc lư, đình đình đi một chút, không biết qua bao lâu……',
                'Xe ngựa ngừng lại.',
                'Xa phu lớn tiếng nói: “Đến lạp, nơi này chính là ' . $city . ' .”Ngay sau đó xốc lên cửa xe.',
            ];
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

        if (time() - $role_attrs->startCarriageTimestamp > 2) {
            return $connection->send(\cache_response($request, \view('Map/Carriage/waiting2.twig', [
                'request'     => $request,
                'waiting_url' => 'Map/Carriage/waiting/' . $map_id,
            ])));
        } else {
            return $connection->send(\cache_response($request, \view('Map/Carriage/waiting.twig', [
                'request'     => $request,
                'waiting_url' => 'Map/Carriage/waiting/' . $map_id,
            ])));
        }
    }
}
