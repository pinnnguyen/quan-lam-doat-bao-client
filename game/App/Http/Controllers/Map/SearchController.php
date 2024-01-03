<?php

namespace App\Http\Controllers\Map;

use App\Libs\Helpers;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 搜索
 */
class SearchController
{
    /**
     * 搜索描述
     *
     * @var array|string[]
     */
    public static array $searchDescriptions = [
        'Ngươi ở chỗ này sờ soạng, xem có hay không cái gì đáng giá đồ vật.',
        'Ngươi thăm hạ thân tử, nhẹ nhàng ở chung quanh trên mặt đất đánh vài cái.',
        'Ngươi thở dài, tiếp tục nhìn đông nhìn tây xem xét.',
        'Ngươi di một tiếng, cong lưng trên mặt đất gẩy đẩy thứ gì.',
        'Ngươi bắt đầu ở chỗ này sờ soạng, xem có hay không cái gì đáng giá đồ vật.',
    ];

    /**
     * 书籍
     *
     * 刀法精要 204
     * 剑术指南 235
     * 拳脚入门 210
     * 丝罗巾 207
     * 招架要旨 194
     * 玉佩 197
     * 基本杖法 236 -
     * 扇法指南 237 -
     * 斧法精要 238 -
     * 基本棒法 243 -
     *
     * @var array|int[]
     */
    public static array $books = [204, 235, 210, 207, 194, 197,];


    /**
     * 首页
     *
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
        /**
         * 判断Tinh thần值是否足够
         */
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->jingshen < 50) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi đột nhiên cảm thấy một trận choáng váng, tinh thần có chút hoảng hốt, đi trước khách điếm nghỉ ngơi nghỉ ngơi lại đến đi.',
            ])));
        }

        $role_attrs->jingshen -= 50;
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);

        if (Helpers::getProbability(20, 100)) {
            $book = self::$books[array_rand(self::$books)];
            $thing = Helpers::getThingRowByThingId($book);
            $result_description = 'Ngươi đột nhiên phát hiện một ' . $thing->unit . $thing->name . ',Vội vàng cất vào trong lòng ngực.';

            /**
             * Cho秘籍
             */
            $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, $book, 1);
SQL;


            Helpers::execSql($sql);

        } else {
            $result_description = 'Chính là ngươi tìm nửa ngày, kết quả vẫn là không thu hoạch được gì, chỉ phải từ bỏ.';
        }


        return $connection->send(\cache_response($request, \view('Map/Search/index.twig', [
            'request'            => $request,
            'search_description' => self::$searchDescriptions[array_rand(self::$searchDescriptions)],
            'result_description' => $result_description,
        ])));
    }
}
