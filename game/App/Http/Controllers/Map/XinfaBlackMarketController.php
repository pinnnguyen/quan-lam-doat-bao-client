<?php


namespace App\Http\Controllers\Map;


use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;


/**
 * 心法黑市
 */
class XinfaBlackMarketController
{
    /**
     * 购买首页
     *
     * @param TcpConnection $connection
     * @param Request $request
     *
     * @return bool|null
     */
    public function purchaseIndex(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/purchaseIndex.twig', [
            'request' => $request,
        ])));
    }


    /**
     * 查找所有
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $xinfa_id
     *
     * @return bool|null
     */
    public function searchAll(TcpConnection $connection, Request $request, int $xinfa_id = 0)
    {
        $timestamp = time();
        if ($xinfa_id == 0) {
            $sql = <<<SQL
SELECT `xinfa_id`, count(`xinfa_id`) as `count` FROM `role_xinfas` WHERE `is_sell` = 1 AND `sell_expire` > $timestamp GROUP BY `xinfa_id` HAVING count > 0;
SQL;

            $xinfas = Helpers::queryFetchAll($sql);
            foreach ($xinfas as $xinfa) {
                $xinfa->url = 'Map/XinfaBlackMarket/searchAll/' . $xinfa->xinfa_id;
                $xinfa->row = Helpers::getXinfaRowByXinfaId($xinfa->xinfa_id);
            }
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/list.twig', [
                'request' => $request,
                'xinfas' => $xinfas,
            ])));
        } else {
            $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `xinfa_id` = $xinfa_id AND `is_sell` = 1 AND `sell_expire` > $timestamp;
SQL;

            $xinfas = Helpers::queryFetchAll($sql);
            $xinfa_row = Helpers::getXinfaRowByXinfaId($xinfa_id);
            foreach ($xinfas as $xinfa) {
                $xinfa->url = 'Map/XinfaBlackMarket/view/' . $xinfa->id;
                $xinfa->name = $xinfa_row->name;
                $xinfa->price = Helpers::getHansMoney($xinfa->sell_price);
            }
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/display.twig', [
                'request' => $request,
                'xinfas' => $xinfas,
            ])));
        }
    }


    /**
     * 类型查找
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $kind
     * @param int $xinfa_id
     *
     * @return bool|null
     */
    public function searchByKind(TcpConnection $connection, Request $request, int $kind = 0, int $xinfa_id = 0)
    {
        if ($kind == 0) {
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/searchByKind.twig', [
                'request' => $request,
            ])));
        }
        $timestamp = time();
        $kinds = [1 => 'Công kích', 2 => 'Sinh mệnh', 3 => 'Nội công'];
        if ($xinfa_id == 0) {
            $sql = <<<SQL
SELECT `xinfa_id`, count(`xinfa_id`) as `count` FROM `role_xinfas` INNER JOIN `xinfas` ON `role_xinfas`.`xinfa_id` = `xinfas`.`id` AND `xinfas`.`kind` = '$kinds[$kind]' WHERE `is_sell` = 1 AND `sell_expire` > $timestamp GROUP BY `xinfa_id` HAVING count > 0;
SQL;

            $xinfas = Helpers::queryFetchAll($sql);
            foreach ($xinfas as $xinfa) {
                $xinfa->url = 'Map/XinfaBlackMarket/searchByKind/' . $kind . '/' . $xinfa->xinfa_id;
                $xinfa->row = Helpers::getXinfaRowByXinfaId($xinfa->xinfa_id);
            }
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/list.twig', [
                'request' => $request,
                'xinfas' => $xinfas,
            ])));
        }
        $sql = <<<SQL
SELECT `role_xinfas`.`id`, `name`, `sell_price` FROM `role_xinfas` INNER JOIN `xinfas` ON `role_xinfas`.`xinfa_id` = `xinfas`.`id` AND `xinfas`.`kind` = '$kinds[$kind]' WHERE `is_sell` = 1 AND `sell_expire` > $timestamp AND `xinfa_id` = $xinfa_id;
SQL;

        $xinfas = Helpers::queryFetchAll($sql);
        foreach ($xinfas as $xinfa) {
            $xinfa->url = 'Map/XinfaBlackMarket/view/' . $xinfa->id;
            $xinfa->price = Helpers::getHansMoney($xinfa->sell_price);
        }
        return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/display.twig', [
            'request' => $request,
            'xinfas' => $xinfas,
        ])));
    }


    /**
     * 等级查找
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $level
     * @param int $xinfa_id
     *
     * @return bool|null
     */
    public function searchByLevel(TcpConnection $connection, Request $request, int $level = 0, int $xinfa_id = 0)
    {
        if ($level == 0) {
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/searchByLevel.twig', [
                'request' => $request,
            ])));
        }
        $timestamp = time();
        $levels = [
            1 => ['min' => 0, 'max' => 40,],
            2 => ['min' => 41, 'max' => 80,],
            3 => ['min' => 81, 'max' => 160,],
            4 => ['min' => 161, 'max' => 320,],
            5 => ['min' => 321, 'max' => 9999,],
        ];
        if ($xinfa_id == 0) {
            $sql = <<<SQL
SELECT `xinfa_id`, count(`xinfa_id`) as `count` FROM `role_xinfas` WHERE `is_sell` = 1 AND `sell_expire` > $timestamp AND `lv` >= {$levels[$level]['min']} AND `lv` <= {$levels[$level]['max']} GROUP BY `xinfa_id` HAVING count > 0;
SQL;

            $xinfas = Helpers::queryFetchAll($sql);
            foreach ($xinfas as $xinfa) {
                $xinfa->url = 'Map/XinfaBlackMarket/searchByLevel/' . $level . '/' . $xinfa->xinfa_id;
                $xinfa->row = Helpers::getXinfaRowByXinfaId($xinfa->xinfa_id);
            }
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/list.twig', [
                'request' => $request,
                'xinfas' => $xinfas,
            ])));
        }
        $sql = <<<SQL
SELECT `id`, `sell_price` FROM `role_xinfas` WHERE `is_sell` = 1 AND `sell_expire` > $timestamp AND `lv` >= {$levels[$level]['min']} AND `lv` <= {$levels[$level]['max']} AND `xinfa_id` = $xinfa_id;
SQL;

        $xinfas = Helpers::queryFetchAll($sql);
        $xinfa_row = Helpers::getXinfaRowByXinfaId($xinfa_id);
        foreach ($xinfas as $xinfa) {
            $xinfa->url = 'Map/XinfaBlackMarket/view/' . $xinfa->id;
            $xinfa->name = $xinfa_row->name;
            $xinfa->price = Helpers::getHansMoney($xinfa->sell_price);
        }
        return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/display.twig', [
            'request' => $request,
            'xinfas' => $xinfas,
        ])));
    }


    /**
     * 技能查找
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $skill_id
     * @param int $xinfa_id
     *
     * @return bool|null
     */
    public function searchBySkill(TcpConnection $connection, Request $request, int $skill_id = 0, int $xinfa_id = 0)
    {
        if ($skill_id == 0) {
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/searchBySkill.twig', [
                'request' => $request,
            ])));
        }
        $timestamp = time();
        $skills = [1 => 'Kiếm pháp', 2 => 'Đao pháp', 3 => 'Quyền cước', 4 => 'Khinh công', 5 => 'Chống đỡ', 6 => 'Nội công',];
        if ($xinfa_id == 0) {
            $sql = <<<SQL
SELECT `xinfa_id`, count(`xinfa_id`) as `count` FROM `role_xinfas`
INNER JOIN `xinfas` ON `role_xinfas`.`xinfa_id` = `xinfas`.`id`
INNER JOIN `skills` ON `xinfas`.`skill_id` = `skills`.`id` AND `skills`.`kind` = '$skills[$skill_id]'
WHERE `is_sell` = 1 AND `sell_expire` > $timestamp GROUP BY `xinfa_id` HAVING count > 0;
SQL;

            $xinfas = Helpers::queryFetchAll($sql);
            foreach ($xinfas as $xinfa) {
                $xinfa->url = 'Map/XinfaBlackMarket/searchBySkill/' . $skill_id . '/' . $xinfa->xinfa_id;
                $xinfa->row = Helpers::getXinfaRowByXinfaId($xinfa->xinfa_id);
            }
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/list.twig', [
                'request' => $request,
                'xinfas' => $xinfas,
            ])));
        }
        $sql = <<<SQL
SELECT `role_xinfas`.`id`, `xinfas`.`name`, `sell_price` FROM `role_xinfas`
INNER JOIN `xinfas` ON `role_xinfas`.`xinfa_id` = `xinfas`.`id`
INNER JOIN `skills` ON `xinfas`.`skill_id` = `skills`.`id` AND `skills`.`kind` = '$skills[$skill_id]'
WHERE `is_sell` = 1 AND `sell_expire` > $timestamp AND `xinfa_id` = $xinfa_id;
SQL;

        $xinfas = Helpers::queryFetchAll($sql);
        foreach ($xinfas as $xinfa) {
            $xinfa->url = 'Map/XinfaBlackMarket/view/' . $xinfa->id;
            $xinfa->price = Helpers::getHansMoney($xinfa->sell_price);
        }
        return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/display.twig', [
            'request' => $request,
            'xinfas' => $xinfas,
        ])));
    }


    /**
     * 门派查找
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $sect_id
     * @param int $xinfa_id
     *
     * @return bool|null
     */
    public function searchBySect(TcpConnection $connection, Request $request, int $sect_id = 100, int $xinfa_id = 0)
    {
        if ($sect_id == 100) {
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/searchBySect.twig', [
                'request' => $request,
            ])));
        }
        $timestamp = time();
        if ($xinfa_id == 0) {
            $sql = <<<SQL
SELECT `xinfa_id`, count(`xinfa_id`) as `count` FROM `role_xinfas`
INNER JOIN `xinfas` ON `role_xinfas`.`xinfa_id` = `xinfas`.`id` AND `xinfas`.`sect_id` = $sect_id
WHERE `is_sell` = 1 AND `sell_expire` > $timestamp GROUP BY `xinfa_id` HAVING count > 0;
SQL;

            $xinfas = Helpers::queryFetchAll($sql);
            foreach ($xinfas as $xinfa) {
                $xinfa->url = 'Map/XinfaBlackMarket/searchBySect/' . $sect_id . '/' . $xinfa->xinfa_id;
                $xinfa->row = Helpers::getXinfaRowByXinfaId($xinfa->xinfa_id);
            }
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/list.twig', [
                'request' => $request,
                'xinfas' => $xinfas,
            ])));
        }
        $sql = <<<SQL
SELECT `role_xinfas`.`id`, `xinfas`.`name`, `sell_price` FROM `role_xinfas`
INNER JOIN `xinfas` ON `role_xinfas`.`xinfa_id` = `xinfas`.`id` AND `xinfas`.`sect_id` = $sect_id
WHERE `is_sell` = 1 AND `sell_expire` > $timestamp AND `xinfa_id` = $xinfa_id;
SQL;

        $xinfas = Helpers::queryFetchAll($sql);
        foreach ($xinfas as $xinfa) {
            $xinfa->url = 'Map/XinfaBlackMarket/view/' . $xinfa->id;
            $xinfa->price = Helpers::getHansMoney($xinfa->sell_price);
        }
        return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/display.twig', [
            'request' => $request,
            'xinfas' => $xinfas,
        ])));
    }


    /**
     * Tâm Pháp
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function view(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        $xinfa = Helpers::queryFetchObject($sql);
        if ($xinfa) {
            $xinfa->row = Helpers::getXinfaRowByXinfaId($xinfa->xinfa_id);
            $description = mb_substr($xinfa->row->description, 0, 50);

            if (mb_strlen($xinfa->row->description) > 50) {
                $displayDescription = true;
            } else {
                $displayDescription = false;
            }
            if ($xinfa->row->skill_id) {
                $xinfa->skill = Helpers::getSkillRowBySkillId($xinfa->row->skill_id);
            }
            if ($xinfa->row->sect_id) {
                $xinfa->sect = Helpers::getSect($xinfa->row->sect_id);
            } else {
                $xinfa->sect = 'Bình thường bá tánh';
            }

            $xinfa->need_experience = $xinfa->lv * $xinfa->lv * $xinfa->base_experience;
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/view.twig', [
                'request' => $request,
                'xinfa' => $xinfa,
                'description' => $description,
                'displayDescription' => $displayDescription,
                'descriptionUrl' => 'Map/XinfaBlackMarket/description/' . $role_xinfa_id,
                'buyQuestionUrl' => 'Map/XinfaBlackMarket/buyQuestion/' . $role_xinfa_id,
            ])));
        } else {
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/message.twig', [
                'request' => $request,
                'message' => 'Vị này đại hiệp, ngươi đã tới chậm, tâm pháp đã bán ra hoặc là hạ giá.',
            ])));
        }
    }


    /**
     * Tâm Pháp描述
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function description(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        $xinfa = Helpers::queryFetchObject($sql);
        if ($xinfa) {
            $xinfa->row = Helpers::getXinfaRowByXinfaId($xinfa->xinfa_id);
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/description.twig', [
                'request' => $request,
                'xinfa' => $xinfa,
                'backUrl' => 'Map/XinfaBlackMarket/view/' . $role_xinfa_id,
            ])));
        } else {
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/message.twig', [
                'request' => $request,
                'message' => 'Vị này đại hiệp, ngươi đã tới chậm, tâm pháp đã bán ra hoặc là hạ giá.',
            ])));
        }
    }


    /**
     * Mua sắm tâm pháp 询问
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function buyQuestion(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        $xinfa = Helpers::queryFetchObject($sql);
        if ($xinfa) {
            $xinfa->row = Helpers::getXinfaRowByXinfaId($xinfa->xinfa_id);
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/buyQuestion.twig', [
                'request' => $request,
                'xinfa' => $xinfa,
                'backUrl' => 'Map/XinfaBlackMarket/view/' . $role_xinfa_id,
                'buyUrl' => 'Map/XinfaBlackMarket/buy/' . $role_xinfa_id,
            ])));
        } else {
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/message.twig', [
                'request' => $request,
                'message' => 'Vị này đại hiệp, ngươi đã tới chậm, tâm pháp đã bán ra hoặc là hạ giá.',
            ])));
        }
    }


    /**
     * Mua sắm tâm pháp Xác nhận
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function buy(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        if (!cache()->set('lock_role_xinfa_' . $role_xinfa_id, 'ok', ['NX', 'PX' => 50])) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Tạm thời không thể mua sắm tâm pháp',
            ])));
        }

        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id AND `is_sell` = 1;
SQL;

        $xinfa = Helpers::queryFetchObject($sql);
        if ($xinfa) {
            $xinfa->row = Helpers::getXinfaRowByXinfaId($xinfa->xinfa_id);
            /**
             * 是否是自己的心法
             *
             */
            if ($xinfa->role_id == $request->roleId) {
                return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/message.twig', [
                    'request' => $request,
                    'message' => 'Vị này đại hiệp, ngươi không thể mua sắm chính mình tâm pháp.',
                ])));
            }

            /**
             * 钱庄是否有钱
             *
             */
            if ($request->roleRow->bank_balance < $xinfa->sell_price) {
                return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/message.twig', [
                    'request' => $request,
                    'message' => 'Vị này đại hiệp, ngươi ở tiền trang tiền tiết kiệm không đủ để mua sắm này bổn' . $xinfa->row->name . '。',
                ])));
            }

            /**
             * Xem xét自己心法背包是否装得下
             *
             */
            $sql = <<<SQL
SELECT `id` FROM `role_xinfas` WHERE `role_id` = $request->roleId;
SQL;

            $role_xinfas = Helpers::queryFetchAll($sql);
            if (count($role_xinfas) >= 10) {
                return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/message.twig', [
                    'request' => $request,
                    'message' => 'Vị này đại hiệp, ngươi tâm pháp ba lô đã trang không được.',
                ])));
            }

            /**
             * 转移心法
             *
             */
            $sql = <<<SQL
UPDATE `role_xinfas` SET `role_id` = $request->roleId, `is_sell` = 0, `sell_price` = 0, `sell_expire` = 0 WHERE `id` = $role_xinfa_id;
SQL;

            /**
             * 扣除、转移金钱
             *
             */
            $request->roleRow->bank_balance -= $xinfa->sell_price;
            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
            $sql .= <<<SQL
UPDATE `roles` SET `bank_balance` = `bank_balance` - $xinfa->sell_price WHERE `id` = $request->roleId;
SQL;

            $seller = Helpers::getRoleRowByRoleId($xinfa->role_id);
            if (!empty($seller)) {
                $seller->bank_balance += $xinfa->sell_price;
                Helpers::setRoleRowByRoleId($xinfa->role_id, $seller);
                /**
                 * 推送广播
                 *
                 */
                cache()->rPush('role_broadcast_' . $seller->id, [
                    'kind' => 6,
                    'content' => $request->roleRow->name . 'Từ chợ đen mua sắm ngươi' . $xinfa->row->name .
                        ',Ngươi đạt được' . Helpers::getHansMoney($xinfa->sell_price) . '(Đã tồn nhập tiền trang)。',
                ]);
            }
            $sql .= <<<SQL
UPDATE `roles` SET `bank_balance` = `bank_balance` + $xinfa->sell_price WHERE `id` = $xinfa->role_id;
SQL;


            Helpers::execSql($sql);

            loglog(LOG_BLACKMARKET_TRANSACTIONS, '黑市Giao dịch tâm pháp', [
                '买方玩家' => $request->roleRow->name,
                '卖方玩家' => empty($seller) ? $xinfa->role_id : $seller->name,
                '心法' => $xinfa->row->name,
                '原始 ID' => $xinfa->id,
                '金额' => $xinfa->sell_price,
            ]);
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/message.twig', [
                'request' => $request,
                'message' => 'Ngươi tiêu phí' . Helpers::getHansMoney($xinfa->sell_price) . 'Đặt mua' . $xinfa->row->name . ',Phí dụng đã từ ngươi tiền trang tiền tiết kiệm khấu trừ.',
            ])));
        } else {
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/message.twig', [
                'request' => $request,
                'message' => 'Vị này đại hiệp, ngươi đã tới chậm, tâm pháp đã bán ra hoặc là hạ giá.',
            ])));
        }
    }


    /**
     * 寄售心法首页
     *
     * @param TcpConnection $connection
     * @param Request $request
     *
     * @return bool|null
     */
    public function sellIndex(TcpConnection $connection, Request $request)
    {
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `role_id` = $request->roleId AND `equipped` = 0 AND `is_sell` = 0 AND `practiced` = 0;
SQL;

        $role_xinfas = Helpers::queryFetchAll($sql);
        if ($role_xinfas) {
            foreach ($role_xinfas as $role_xinfa) {
                $role_xinfa->row = Helpers::getXinfaRowByXinfaId($role_xinfa->xinfa_id);
                $role_xinfa->sellUrl = 'Map/XinfaBlackMarket/sell/' . $role_xinfa->id;
            }
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/sellIndex.twig', [
                'request' => $request,
                'role_xinfas' => $role_xinfas,
            ])));
        } else {
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/message.twig', [
                'request' => $request,
                'message' => 'Ngươi không có có thể gởi bán tâm pháp.',
            ])));
        }
    }


    /**
     * 寄售心法 定价
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function sell(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        $role_xinfa = Helpers::queryFetchObject($sql);
        if ($role_xinfa) {
            $role_xinfa->row = Helpers::getXinfaRowByXinfaId($role_xinfa->xinfa_id);
            $role_xinfa->sellPostUrl = 'Map/XinfaBlackMarket/sellPost/' . $role_xinfa->id;
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/sell.twig', [
                'request' => $request,
                'role_xinfa' => $role_xinfa,
            ])));
        } else {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi không có có thể gởi bán tâm pháp.',
            ])));
        }
    }


    /**
     * 售卖心法 Xác định
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function sellPost(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        if (!cache()->set('lock_role_xinfa_' . $role_xinfa_id, 'ok', ['NX', 'PX' => 50])) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Tạm thời không thể thượng giá tâm pháp',
            ])));
        }

        if (strtoupper($request->method()) != 'POST') {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $price = trim($request->post('price'));
        if (empty($price) or !is_numeric($price)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $price = intval($price);
        if ($price < 1) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        if ($price > 10000000000) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Vị này thiếu hiệp, đổi một cái thực tế một chút giá cả đi. ( kỳ vọng giá cả nhiều nhất không vượt qua một trăm triệu hai hoàng kim )',
            ])));
        }
        $price = $price * 100;
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        $role_xinfa = Helpers::queryFetchObject($sql);
        if ($role_xinfa) {
            if ($role_xinfa->role_id != $request->roleId) {
                return $connection->send(\cache_response($request, \view('Base/message.twig', [
                    'request' => $request,
                    'message' => 'Ngươi không có có thể gởi bán tâm pháp.',
                ])));
            }
            $role_xinfa->row = Helpers::getXinfaRowByXinfaId($role_xinfa->xinfa_id);
            $role_xinfa->sellPostUrl = 'Map/XinfaBlackMarket/sellPost/' . $role_xinfa->id;
            $expire = time() + 86400;
            $sql = <<<SQL
UPDATE `role_xinfas` SET `is_sell` = 1, `sell_price` = $price, `sell_expire` = $expire WHERE `id` = $role_xinfa_id;
SQL;


            Helpers::execSql($sql);


            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi gởi bán một quyển' . $role_xinfa->row->name . ',Yết giá' . Helpers::getHansMoney($price) . ',24 giờ nội không người mua sắm đem từ chợ đen hạ giá.',
            ])));
        } else {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi không có có thể gởi bán tâm pháp.',
            ])));
        }
    }


    /**
     * 挂出心法
     *
     * @param TcpConnection $connection
     * @param Request $request
     *
     * @return bool|null
     */
    public function myselfIndex(TcpConnection $connection, Request $request)
    {
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `role_id` = $request->roleId AND `is_sell` = 1;
SQL;

        $role_xinfas = Helpers::queryFetchAll($sql);
        if ($role_xinfas) {
            foreach ($role_xinfas as $role_xinfa) {
                $role_xinfa->row = Helpers::getXinfaRowByXinfaId($role_xinfa->xinfa_id);
                $role_xinfa->viewUrl = 'Map/XinfaBlackMarket/viewMyself/' . $role_xinfa->id;
                $role_xinfa->is_expire = $role_xinfa->sell_expire < time();
            }
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/myselfIndex.twig', [
                'request' => $request,
                'role_xinfas' => $role_xinfas,
            ])));
        } else {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi không có đang ở gởi bán tâm pháp.',
            ])));
        }
    }


    /**
     * Xem xét我的
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function viewMyself(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        $xinfa = Helpers::queryFetchObject($sql);
        if ($xinfa) {
            $xinfa->row = Helpers::getXinfaRowByXinfaId($xinfa->xinfa_id);
            $description = mb_substr($xinfa->row->description, 0, 50);

            if (mb_strlen($xinfa->row->description) > 50) {
                $displayDescription = true;
            } else {
                $displayDescription = false;
            }
            if ($xinfa->row->skill_id) {
                $xinfa->skill = Helpers::getSkillRowBySkillId($xinfa->row->skill_id);
            }
            if ($xinfa->row->sect_id) {
                $xinfa->sect = Helpers::getSect($xinfa->row->sect_id);
            } else {
                $xinfa->sect = 'Bình thường bá tánh';
            }

            $xinfa->need_experience = $xinfa->lv * $xinfa->lv * $xinfa->base_experience;
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/viewMyself.twig', [
                'request' => $request,
                'xinfa' => $xinfa,
                'description' => $description,
                'displayDescription' => $displayDescription,
                'descriptionUrl' => 'Map/XinfaBlackMarket/descriptionMyself/' . $role_xinfa_id,
                'downUrl' => 'Map/XinfaBlackMarket/down/' . $role_xinfa_id,
            ])));
        } else {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Vị này đại hiệp, ngươi đã tới chậm, tâm pháp đã bán ra hoặc là hạ giá.',
            ])));
        }
    }


    /**
     * Tâm Pháp描述
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function descriptionMyself(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        $xinfa = Helpers::queryFetchObject($sql);
        if ($xinfa) {
            $xinfa->row = Helpers::getXinfaRowByXinfaId($xinfa->xinfa_id);
            return $connection->send(\cache_response($request, \view('Map/XinfaBlackMarket/description.twig', [
                'request' => $request,
                'xinfa' => $xinfa,
                'backUrl' => 'Map/XinfaBlackMarket/viewMyself/' . $role_xinfa_id,
            ])));
        } else {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Vị này đại hiệp, ngươi đã tới chậm, tâm pháp đã bán ra hoặc là hạ giá.',
            ])));
        }
    }


    /**
     * 取回在售心法
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function down(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        if (!cache()->set('lock_role_xinfa_' . $role_xinfa_id, 'ok', ['NX', 'PX' => 50])) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Tạm thời không thể hạ giá tâm pháp',
            ])));
        }

        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        $xinfa = Helpers::queryFetchObject($sql);
        if ($xinfa) {
            $xinfa->row = Helpers::getXinfaRowByXinfaId($xinfa->xinfa_id);
            $sql = <<<SQL
UPDATE `role_xinfas` SET `sell_expire` = 0, `sell_price` = 0, `is_sell` = 0 WHERE `id` = $role_xinfa_id;
SQL;


            Helpers::execSql($sql);


            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi từ chợ đen thu hồi một quyển' . $xinfa->row->name,
            ])));
        } else {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Vị này đại hiệp, ngươi đã tới chậm, tâm pháp đã bán ra hoặc là hạ giá.',
            ])));
        }
    }
}
