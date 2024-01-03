<?php

namespace App\Http\Controllers\Role;

use App\Core\Configs\GameConfig;
use App\Libs\Attrs\FlushRoleAttrs;
use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 商城
 *
 */
class ShopController
{
    /**
     * 道具列表
     *
     * @var array|array[]
     */
    public static array $djs = [
        1  => [
            'name'        => 'Gấp đôi tiềm năng đan',
            'description' => 'Một viên thần kỳ màu đỏ tiểu thuốc viên, ăn nó về sau sẽ có thần kỳ lực lượng.',
            'status'      => 'Hữu hiệu thời gian: 2 giờ',
            'price'       => 50,
            'unit'        => 'Viên',
            'is_sell'     => true,
        ],
        2  => [
            'name'        => 'Gấp đôi tu hành đan',
            'description' => 'Một viên thần kỳ màu vàng tiểu thuốc viên, ăn nó về sau sẽ có thần kỳ lực lượng.',
            'status'      => 'Hữu hiệu thời gian: 2 giờ',
            'price'       => 50,
            'unit'        => 'Viên',
            'is_sell'     => true,
        ],
        3  => [
            'name'        => 'Gấp đôi tiềm năng đan（精华）',
            'description' => 'Một viên thần kỳ màu đỏ tiểu thuốc viên, ăn nó về sau sẽ có thần kỳ lực lượng.',
            'status'      => 'Hữu hiệu thời gian: 12 giờ',
            'price'       => 300,
            'unit'        => 'Viên',
            'is_sell'     => true,
        ],
        4  => [
            'name'        => 'Gấp đôi tu hành đan（精华）',
            'description' => 'Một viên thần kỳ màu vàng tiểu thuốc viên, ăn nó về sau sẽ có thần kỳ lực lượng.',
            'status'      => 'Hữu hiệu thời gian: 12 giờ',
            'price'       => 300,
            'unit'        => 'Viên',
            'is_sell'     => true,
        ],
        5  => [
            'name'        => 'Bổ kim thạch',
            'description' => 'Một khối đen tuyền cục đá, mặt trên có kỳ quái hoa văn, nhìn qua là khối vẫn thiết.',
            'status'      => 'Sử dụng số lần: 1 thứ',
            'price'       => 50,
            'unit'        => 'Khối',
            'is_sell'     => true,
        ],
        6  => [
            'name'        => 'Bổ kim thạch（精华）',
            'description' => 'Một khối đen tuyền cục đá, mặt trên có kỳ quái hoa văn, nhìn qua là khối vẫn thiết.',
            'status'      => 'Sử dụng số lần: 10 thứ',
            'price'       => 500,
            'unit'        => 'Khối',
            'is_sell'     => true,
        ],
        7  => [
            'name'        => 'Truyền tống thạch',
            'description' => 'Một khối màu trắng cục đá, mặt trên khắc có phù văn, tựa hồ có thể truyền tống đến địa phương nào.',
            'status'      => 'Sử dụng số lần: 200 thứ',
            'price'       => 600,
            'unit'        => 'Khối',
            'is_sell'     => true,
        ],
        8  => [
            'name'        => 'Tơ vàng gỗ nam',
            'description' => 'Một loại thực quý báu bó củi, dùng cho xây dựng thêm kho hàng.',
            'status'      => 'Sử dụng số lần: 1 thứ',
            'price'       => 200,
            'unit'        => 'Khối',
            'is_sell'     => true,
        ],
        9  => [
            'name'        => 'Hoàng kim bảo rương chìa khóa ( tiểu )',
            'description' => 'Một phen có thể mở ra hoàng kim bảo rương ( tiểu ) chìa khóa.',
            'status'      => 'Sử dụng số lần: 1 thứ',
            'price'       => 100,
            'unit'        => 'Rương',
            'is_sell'     => true,
        ],
        10 => [
            'name'        => 'Hoàng kim bảo rương chìa khóa ( trung )',
            'description' => 'Một phen có thể mở ra hoàng kim bảo rương ( trung ) chìa khóa.',
            'status'      => 'Sử dụng số lần: 1 thứ',
            'price'       => 500,
            'unit'        => 'Rương',
            'is_sell'     => true,
        ],
        11 => [
            'name'        => 'Hoàng kim bảo rương chìa khóa ( đại )',
            'description' => 'Một phen có thể mở ra hoàng kim bảo rương ( đại ) chìa khóa.',
            'status'      => 'Sử dụng số lần: 1 thứ',
            'price'       => 1000,
            'unit'        => 'Rương',
            'is_sell'     => true,
        ],
        12 => [
            'name'        => 'Tâm pháp bảo rương chìa khóa',
            'description' => 'Một phen có thể mở ra tâm pháp bảo rương chìa khóa.',
            'status'      => 'Sử dụng số lần: 1 thứ',
            'price'       => 300,
            'unit'        => 'Rương',
            'is_sell'     => true,
        ],
        13 => [
            'name'        => 'Vũ khí bảo rương chìa khóa',
            'description' => 'Một phen có thể mở ra vũ khí bảo rương chìa khóa.',
            'status'      => 'Sử dụng số lần: 1 thứ',
            'price'       => 100,
            'unit'        => 'Rương',
            'is_sell'     => true,
        ],
        14 => [
            'name'        => 'Giày bảo rương chìa khóa',
            'description' => 'Một phen có thể mở ra giày bảo rương chìa khóa.',
            'status'      => 'Sử dụng số lần: 1 thứ',
            'price'       => 100,
            'unit'        => 'Rương',
            'is_sell'     => true,
        ],
        15 => [
            'name'        => 'Quần áo bảo rương chìa khóa',
            'description' => 'Một phen có thể mở ra quần áo bảo rương chìa khóa.',
            'status'      => 'Sử dụng số lần: 1 thứ',
            'price'       => 100,
            'unit'        => 'Rương',
            'is_sell'     => true,
        ],
        16 => [
            'name'        => 'Áo giáp bảo rương chìa khóa',
            'description' => 'Một phen có thể mở ra áo giáp bảo rương chìa khóa.',
            'status'      => 'Sử dụng số lần: 1 thứ',
            'price'       => 100,
            'unit'        => 'Rương',
            'is_sell'     => true,
        ],
        17 => [
            'name'        => 'Miễn tử kim bài',
            'description' => 'Một Khối có thể miễn tao giết chóc kim bài.',
            'status'      => 'Hữu hiệu thời gian: 2 giờ',
            'price'       => 1000,
            'unit'        => 'Khối',
            'is_sell'     => true,
        ],
        18 => [
            'name'        => 'Gấp đôi tâm pháp đan',
            'description' => 'Một viên thần kỳ màu xanh lục tiểu thuốc viên, ăn nó về sau sẽ có thần kỳ lực lượng.',
            'status'      => 'Hữu hiệu thời gian: 2 giờ',
            'price'       => 50,
            'unit'        => 'Viên',
            'is_sell'     => true,
        ],
        19 => [
            'name'        => 'Gấp đôi tâm pháp đan（精华）',
            'description' => 'Một viên thần kỳ màu xanh lục tiểu thuốc viên, ăn nó về sau sẽ có thần kỳ lực lượng.',
            'status'      => 'Hữu hiệu thời gian: 12 giờ',
            'price'       => 300,
            'unit'        => 'Viên',
            'is_sell'     => true,
        ],
        20 => [
            'name'        => 'Gấp ba tâm pháp đan',
            'description' => 'Một viên thần kỳ màu xanh lục tiểu thuốc viên, ăn nó về sau sẽ có thần kỳ lực lượng.',
            'status'      => 'Hữu hiệu thời gian: 2 giờ',
            'price'       => 150,
            'unit'        => 'Viên',
            'is_sell'     => false,
        ],
        21 => [
            'name'        => 'Ngàn năm nhân sâm',
            'description' => 'Một loại thực quý báu dược liệu, dùng sau vĩnh cửu gia tăng 10% chiến đấu khôi phục.',
            'status'      => 'Hữu hiệu thời gian: Vĩnh cửu',
            'price'       => 1000000,
            'unit'        => 'Căn',
            'is_sell'     => false,
        ],
        22 => [
            'name'        => 'Gấp ba tiềm năng đan',
            'description' => 'Một viên thần kỳ màu đỏ tiểu thuốc viên, ăn nó về sau sẽ có thần kỳ lực lượng.( không thể cùng gấp đôi chồng lên, nếu đồng thời sử dụng lấy gấp ba vì chuẩn. )',
            'status'      => 'Hữu hiệu thời gian: 2 giờ',
            'price'       => 150,
            'unit'        => 'Viên',
            'is_sell'     => true,
        ],
        23 => [
            'name'        => 'Gấp ba tu hành đan',
            'description' => 'Một viên thần kỳ màu vàng tiểu thuốc viên, ăn nó về sau sẽ có thần kỳ lực lượng.( không thể cùng gấp đôi chồng lên, nếu đồng thời sử dụng lấy gấp ba vì chuẩn. )',
            'status'      => 'Hữu hiệu thời gian: 2 giờ',
            'price'       => 150,
            'unit'        => 'Viên',
            'is_sell'     => true,
        ],
    ];


    /**
     * 购买道具 首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function dj(TcpConnection $connection, Request $request)
    {
        /**
         * 获取元宝数
         *
         */
        $sql = <<<SQL
SELECT `yuanbao` FROM `roles` WHERE `id` = $request->roleId;
SQL;

        $yuanbao = Helpers::queryFetchObject($sql);
        $djs = self::$djs;
        foreach ($djs as $key => $dj) {
            $djs[$key]['viewUrl'] = 'Role/Shop/djView/' . $key;
        }
        $vip = match (true) {
            $request->roleRow->vip_score >= GameConfig::VIP10_SCORE => 10,
            $request->roleRow->vip_score >= GameConfig::VIP9_SCORE  => 9,
            $request->roleRow->vip_score >= GameConfig::VIP8_SCORE  => 8,
            $request->roleRow->vip_score >= GameConfig::VIP7_SCORE  => 7,
            $request->roleRow->vip_score >= GameConfig::VIP6_SCORE  => 6,
            $request->roleRow->vip_score >= GameConfig::VIP5_SCORE  => 5,
            $request->roleRow->vip_score >= GameConfig::VIP4_SCORE  => 4,
            $request->roleRow->vip_score >= GameConfig::VIP3_SCORE  => 3,
            $request->roleRow->vip_score >= GameConfig::VIP2_SCORE  => 2,
            $request->roleRow->vip_score >= GameConfig::VIP1_SCORE  => 1,
            default                                                 => 0,
        };
        return $connection->send(\cache_response($request, \view('Role/Shop/dj.twig', [
            'request' => $request,
            'djs'     => $djs,
            'yuanbao' => $yuanbao->yuanbao ?? 0,
            'vip'     => $vip,
        ])));
    }


    /**
     * 购买道具 询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $key
     *
     * @return bool|null
     */
    public function djView(TcpConnection $connection, Request $request, int $key)
    {
        $dj = self::$djs[$key];
        $dj['buyQuestionUrl'] = 'Role/Shop/djBuyQuestion/' . $key;
        $dj['buyOneQuestionUrl'] = 'Role/Shop/djBuyQuestion/' . $key . '/1';
        $dj['buyFiveQuestionUrl'] = 'Role/Shop/djBuyQuestion/' . $key . '/5';
        $dj['buyTenQuestionUrl'] = 'Role/Shop/djBuyQuestion/' . $key . '/10';
        $dj['buyFiftyQuestionUrl'] = 'Role/Shop/djBuyQuestion/' . $key . '/50';
        return $connection->send(\cache_response($request, \view('Role/Shop/djView.twig', [
            'request' => $request,
            'dj'      => $dj,
        ])));
    }


    /**
     * 购买询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $key
     * @param int           $number
     *
     * @return bool|null
     */
    public function djBuyQuestion(TcpConnection $connection, Request $request, int $key, int $number = 0)
    {
        if (strtoupper($request->method()) === 'POST') {
            $number = trim($request->post('number'));
            if (!is_numeric($number)) {
                return $connection->send(\cache_response($request, \view('Base/message.twig', [
                    'request' => $request,
                    'message' => Helpers::randomSentence(),
                ])));
            }
            $number = intval($number);
        }
        if ($number < 1 or $number > 99999999) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Thỉnh đưa vào một hợp lý con số',
            ])));
        }
        $dj = self::$djs[$key];
        $dj['buyUrl'] = 'Role/Shop/djBuy/' . $key . '/' . $number;
        return $connection->send(\cache_response($request, \view('Role/Shop/djBuyQuestion.twig', [
            'request' => $request,
            'dj'      => $dj,
            'price'   => $dj['price'] * $number,
            'number'  => $number,
        ])));
    }


    /**
     * 购买
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $key
     * @param int           $number
     *
     * @return bool|null
     */
    public function djBuy(TcpConnection $connection, Request $request, int $key, int $number)
    {
        /**
         * 获取元宝数
         *
         */
        $sql = <<<SQL
SELECT `yuanbao` FROM `roles` WHERE `id` = $request->roleId;
SQL;

        $yuanbao = Helpers::queryFetchObject($sql);
        $price = self::$djs[$key]['price'] * $number;
        if (!$yuanbao or $yuanbao->yuanbao < $price) {
            return $connection->send(\cache_response($request, \view('Role/Shop/message.twig', [
                'request' => $request,
                'message' => 'Ngươi nguyên bảo không đủ.',
            ])));
        }

        if ($key == 6 or $key == 7) {
            if ($key == 6) {
                $times = 10;
            } else {
                $times = 200;
            }
            $sql = str_repeat(<<<SQL
INSERT INTO `role_djs` (`role_id`, `dj_id`, `number`, `times`) VALUES ($request->roleId, $key, 1, $times);
SQL,

                $number);
        } else {
            /**
             * 传是否存在道具
             */
            $sql = <<<SQL
SELECT * FROM `role_djs` WHERE `role_id` = $request->roleId AND `dj_id` = $key;
SQL;

            $role_dj = Helpers::queryFetchObject($sql);

            if ($role_dj) {
                $sql = <<<SQL
UPDATE `role_djs` SET `number` = `number` + $number WHERE `id` = $role_dj->id;
SQL;

            } else {
                $sql = <<<SQL
INSERT INTO `role_djs` (`role_id`, `dj_id`, `number`) VALUES ($request->roleId, $key, $number);
SQL;

            }

        }
        $sql .= <<<SQL
UPDATE `roles` SET `yuanbao` = `yuanbao` - $price, `vip_score` = `vip_score` + $price WHERE `id` = $request->roleId;
SQL;
        $request->roleRow->vip_score += $price;
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        $log = '【商城消费】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') .
            '】在商城购买道具【' . self::$djs[$key]['name'] . '】' . $number . self::$djs[$key]['unit'] . '，花费' . $price .
            '元宝，购买前余额：' . $yuanbao->yuanbao . '，购买后余额：' . ($yuanbao->yuanbao - $price) . '。';
        $sql .= <<<SQL
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;

        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Role/Shop/message.twig', [
            'request' => $request,
            'message' => 'Ngươi tiêu phí ' . $price . ' Nguyên bảo mua ' . Helpers::getHansNumber($number) . self::$djs[$key]['unit'] . self::$djs[$key]['name'] . '。',
        ])));
    }


    /**
     * 商城 心法列表
     *
     * @var array|int[][]
     */
    public static array $xinfas = [
        70  => ['price' => 59900],
        93  => ['price' => 59900],
        68  => ['price' => 59900],
        37  => ['price' => 69900],
        119 => ['price' => 79900],
        141 => ['price' => 79900],
        137 => ['price' => 79900],
    ];


    /**
     * Mua sắm tâm pháp 首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function xinfa(TcpConnection $connection, Request $request)
    {
        /**
         * 获取元宝数
         *
         */
        $sql = <<<SQL
SELECT `yuanbao` FROM `roles` WHERE `id` = $request->roleId;
SQL;

        $yuanbao = Helpers::queryFetchObject($sql);
        $xinfas = self::$xinfas;
        foreach ($xinfas as $key => $xinfa) {
            $xinfas[$key]['row'] = Helpers::getXinfaRowByXinfaId($key);
            $xinfas[$key]['viewUrl'] = 'Role/Shop/xinfaView/' . $key;
            if ($xinfas[$key]['row']->sect_id) {
                $xinfas[$key]['sect'] = Helpers::getSect($xinfas[$key]['row']->sect_id);
            } else {
                $xinfas[$key]['sect'] = 'Bình thường bá tánh';
            }
        }
        $vip = match (true) {
            $request->roleRow->vip_score >= GameConfig::VIP10_SCORE => 10,
            $request->roleRow->vip_score >= GameConfig::VIP9_SCORE  => 9,
            $request->roleRow->vip_score >= GameConfig::VIP8_SCORE  => 8,
            $request->roleRow->vip_score >= GameConfig::VIP7_SCORE  => 7,
            $request->roleRow->vip_score >= GameConfig::VIP6_SCORE  => 6,
            $request->roleRow->vip_score >= GameConfig::VIP5_SCORE  => 5,
            $request->roleRow->vip_score >= GameConfig::VIP4_SCORE  => 4,
            $request->roleRow->vip_score >= GameConfig::VIP3_SCORE  => 3,
            $request->roleRow->vip_score >= GameConfig::VIP2_SCORE  => 2,
            $request->roleRow->vip_score >= GameConfig::VIP1_SCORE  => 1,
            default                                                 => 0,
        };
        return $connection->send(\cache_response($request, \view('Role/Shop/xinfa.twig', [
            'request' => $request,
            'xinfas'  => $xinfas,
            'yuanbao' => $yuanbao->yuanbao ?? 0,
            'vip'     => $vip,
        ])));
    }


    /**
     * 心法 Xem xét
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $key
     *
     * @return bool|null
     */
    public function xinfaView(TcpConnection $connection, Request $request, int $key)
    {
        $xinfa = self::$xinfas[$key];
        $xinfa['row'] = Helpers::getXinfaRowByXinfaId($key);
        if ($xinfa['row']->skill_id) {
            $xinfa['skill'] = Helpers::getSkillRowBySkillId($xinfa['row']->skill_id);
        }
        if ($xinfa['row']->sect_id) {
            $xinfa['sect'] = Helpers::getSect($xinfa['row']->sect_id);
        } else {
            $xinfa['sect'] = 'Bình thường bá tánh';
        }
        $xinfa['buyUrl'] = 'Role/Shop/xinfaBuy/' . $key;
        return $connection->send(\cache_response($request, \view('Role/Shop/xinfaView.twig', [
            'request' => $request,
            'xinfa'   => $xinfa,
        ])));
    }


    /**
     * Mua sắm tâm pháp
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $key
     *
     * @return bool|null
     */
    public function xinfaBuy(TcpConnection $connection, Request $request, int $key)
    {
        /**
         * 获取元宝数
         *
         */
        $sql = <<<SQL
SELECT `yuanbao` FROM `roles` WHERE `id` = $request->roleId;
SQL;

        $yuanbao = Helpers::queryFetchObject($sql);
        $price = self::$xinfas[$key]['price'];
        if (!$yuanbao or $yuanbao->yuanbao < $price) {
            return $connection->send(\cache_response($request, \view('Role/Shop/message.twig', [
                'request' => $request,
                'message' => 'Ngươi nguyên bảo không đủ.',
            ])));
        }

        /**
         * Tâm Pháp数量
         *
         */
        $sql = <<<SQL
SELECT `id` FROM `role_xinfas` WHERE `role_id` = $request->roleId;
SQL;

        $role_xinfas = Helpers::queryFetchObject($sql);

        if (is_array($role_xinfas)) {
            $count = count($role_xinfas);
        } else {
            $count = 0;
        }
        if ($count >= 10) {
            return $connection->send(\cache_response($request, \view('Role/Shop/message.twig', [
                'request' => $request,
                'message' => 'Ngươi tâm pháp ba lô đã đầy, không thể mua sắm tâm pháp',
            ])));
        }

        $xinfa = Helpers::getXinfaRowByXinfaId($key);
        if ($xinfa->experience == 216) {
            $base_experience = 5;
        } else {
            $base_experience = 6;
        }

        $sql = <<<SQL
INSERT INTO `role_xinfas` (`role_id`, `xinfa_id`, `lv`, `max_lv`, `base_experience`) 
VALUES ($request->roleId, $key, 1, 80, $base_experience);
UPDATE `roles` SET `yuanbao` = `yuanbao` - $price, `vip_score` = `vip_score` + $price WHERE `id` = $request->roleId;
SQL;


        $request->roleRow->vip_score += $price;
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        $log = '【商城消费】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') .
            '】在商城Mua sắm tâm pháp【' . $xinfa->name . '】花费' . $price .
            '元宝，购买前余额：' . $yuanbao->yuanbao . '，购买后余额：' . ($yuanbao->yuanbao - $price) . '。';
        $sql .= <<<SQL
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;

        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Role/Shop/message.twig', [
            'request' => $request,
            'message' => 'Ngươi tiêu phí ' . $price . ' Nguyên bảo mua Một quyển' . $xinfa->name . '。',
        ])));
    }


    /**
     * 商城装备列表
     *
     * @var array|int[][]
     */
    public static array $equipments = [
        188 => ['price' => 2400],
        189 => ['price' => 4800],
        224 => ['price' => 7200],
        225 => ['price' => 8400],
        226 => ['price' => 9800],
        250 => ['price' => 1800],
        249 => ['price' => 1800],
        185 => ['price' => 2400],
        186 => ['price' => 4800],
        227 => ['price' => 8400],
        187 => ['price' => 9800],
        191 => ['price' => 3200],
        228 => ['price' => 4800],
        229 => ['price' => 8000],
        230 => ['price' => 3200],
        231 => ['price' => 4800],
        232 => ['price' => 8000],
        233 => ['price' => 3200],
        248 => ['price' => 6400],
        234 => ['price' => 9400],
        256 => ['price' => 7200],
        28  => ['price' => 500],
        121 => ['price' => 500],
        76  => ['price' => 500],
    ];


    /**
     * 购买装备 首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function equipment(TcpConnection $connection, Request $request)
    {
        /**
         * 获取元宝数
         *
         */
        $sql = <<<SQL
SELECT `yuanbao` FROM `roles` WHERE `id` = $request->roleId;
SQL;

        $yuanbao = Helpers::queryFetchObject($sql);
        $equipments = self::$equipments;
        foreach ($equipments as $key => $equipment) {
            $equipments[$key]['row'] = Helpers::getThingRowByThingId($key);
            $equipments[$key]['viewUrl'] = 'Role/Shop/equipmentView/' . $key;
        }
        $vip = match (true) {
            $request->roleRow->vip_score >= GameConfig::VIP10_SCORE => 10,
            $request->roleRow->vip_score >= GameConfig::VIP9_SCORE  => 9,
            $request->roleRow->vip_score >= GameConfig::VIP8_SCORE  => 8,
            $request->roleRow->vip_score >= GameConfig::VIP7_SCORE  => 7,
            $request->roleRow->vip_score >= GameConfig::VIP6_SCORE  => 6,
            $request->roleRow->vip_score >= GameConfig::VIP5_SCORE  => 5,
            $request->roleRow->vip_score >= GameConfig::VIP4_SCORE  => 4,
            $request->roleRow->vip_score >= GameConfig::VIP3_SCORE  => 3,
            $request->roleRow->vip_score >= GameConfig::VIP2_SCORE  => 2,
            $request->roleRow->vip_score >= GameConfig::VIP1_SCORE  => 1,
            default                                                 => 0,
        };
        return $connection->send(\cache_response($request, \view('Role/Shop/equipment.twig', [
            'request'    => $request,
            'equipments' => $equipments,
            'yuanbao'    => $yuanbao->yuanbao ?? 0,
            'vip'        => $vip,
        ])));
    }


    /**
     * 装备 Xem xét
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $key
     *
     * @return bool|null
     */
    public function equipmentView(TcpConnection $connection, Request $request, int $key)
    {
        $equipment = self::$equipments[$key];
        $equipment['row'] = Helpers::getThingRowByThingId($key);
        $equipment['buyUrl'] = 'Role/Shop/equipmentBuy/' . $key;
        return $connection->send(\cache_response($request, \view('Role/Shop/equipmentView.twig', [
            'request'   => $request,
            'equipment' => $equipment,
        ])));
    }


    /**
     * 购买装备
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $key
     *
     * @return bool|null
     */
    public function equipmentBuy(TcpConnection $connection, Request $request, int $key)
    {
        /**
         * 获取元宝数
         *
         */
        $sql = <<<SQL
SELECT `yuanbao` FROM `roles` WHERE `id` = $request->roleId;
SQL;

        $yuanbao = Helpers::queryFetchObject($sql);
        $price = self::$equipments[$key]['price'];
        if (!$yuanbao or $yuanbao->yuanbao < $price) {
            return $connection->send(\cache_response($request, \view('Role/Shop/message.twig', [
                'request' => $request,
                'message' => 'Ngươi nguyên bảo không đủ.',
            ])));
        }

        $equipment = Helpers::getThingRowByThingId($key);

        $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `status`, `durability`, `number`) 
VALUES ($request->roleId, $key, 4, $equipment->max_durability, 1);
UPDATE `roles` SET `yuanbao` = `yuanbao` - $price, `vip_score` = `vip_score` + $price WHERE `id` = $request->roleId;
SQL;


        $request->roleRow->vip_score += $price;
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        $log = '【商城消费】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') .
            '】在商城购买装备【' . $equipment->name . '】花费' . $price .
            '元宝，购买前余额：' . $yuanbao->yuanbao . '，购买后余额：' . ($yuanbao->yuanbao - $price) . '。';
        $sql .= <<<SQL
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;

        Helpers::execSql($sql);

        FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);

        return $connection->send(\cache_response($request, \view('Role/Shop/message.twig', [
            'request' => $request,
            'message' => 'Ngươi tiêu phí ' . $price . ' Nguyên bảo mua một ' . $equipment->unit . $equipment->name . '。',
        ])));
    }


    /**
     * 赞助
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function zz(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Role/Shop/zz.twig', [
            'request' => $request,
        ])));
    }
}
