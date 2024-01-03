<?php

namespace App\Http\Controllers\Map;

use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 神秘老人
 *
 */
class MysteriousOldManController
{
    /**
     * 升级心法列表
     *
     * @param TcpConnection $connection
     * @param Request $request
     *
     * @return bool|null
     */
    public function upgradeList(TcpConnection $connection, Request $request)
    {
        /**
         * 获取玩家所有心法
         *
         */
        $sql = <<<SQL
SELECT `id`, `xinfa_id`, `equipped`, `practiced`, `private_name` FROM `role_xinfas` WHERE `role_id` = $request->roleId AND `is_sell` = 0;
SQL;

        $role_xinfas = Helpers::queryFetchAll($sql);
        if (empty($role_xinfas)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Thần bí lão nhân: Ngươi một quyển tâm pháp đều không có, muốn ta như thế nào chỉ điểm?',
            ])));
        }

        foreach ($role_xinfas as $role_xinfa) {
            $role_xinfa->row = Helpers::getXinfaRowByXinfaId($role_xinfa->xinfa_id);
            $role_xinfa->upgradeQuestionUrl = 'Map/MysteriousOldMan/upgradeQuestion/' . $role_xinfa->id;
        }

        return $connection->send(\cache_response($request, \view('Map/MysteriousOldMan/upgradeList.twig', [
            'request' => $request,
            'role_xinfas' => $role_xinfas,
        ])));
    }


    /**
     * 升级心法Xác định询问
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function upgradeQuestion(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        $role_xinfa = Helpers::queryFetchObject($sql);

        $role_xinfa->row = Helpers::getXinfaRowByXinfaId($role_xinfa->xinfa_id);
        $role_xinfa->next_max_lv = $role_xinfa->max_lv + 1;
        $role_xinfa->upgradeUrl = 'Map/MysteriousOldMan/upgrade/' . $role_xinfa_id;
        $role_xinfa->money = $role_xinfa->next_max_lv * $role_xinfa->next_max_lv;

        return $connection->send(\cache_response($request, \view('Map/MysteriousOldMan/upgradeQuestion.twig', [
            'request' => $request,
            'role_xinfa' => $role_xinfa,
        ])));
    }


    /**
     * 升级心法
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function upgrade(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        if (!cache()->set('lock_role_upgrade_xinfa_' . $request->roleId, 'ok', ['NX', 'EX' => 1])) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Đại hiệp đừng vội!',
            ])));
        }
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        $role_xinfa = Helpers::queryFetchObject($sql);

        $role_xinfa->row = Helpers::getXinfaRowByXinfaId($role_xinfa->xinfa_id);
        $role_xinfa->next_max_lv = $role_xinfa->max_lv + 1;
        $role_xinfa->upgradeUrl = 'Map/MysteriousOldMan/upgrade/' . $role_xinfa_id;
        $role_xinfa->money = $role_xinfa->next_max_lv * $role_xinfa->next_max_lv;

        $need_money = $role_xinfa->money * 100;
        /**
         * 金钱是否足够
         */
        if ($request->roleRow->bank_balance < $need_money) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi ở tiền trang dự trữ bạc trắng không đủ.',
            ])));
        }

        /**
         * 扣除金钱
         */
        $old = $request->roleRow->bank_balance;
        $request->roleRow->bank_balance -= $need_money;
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        $sql = <<<SQL
UPDATE `role_xinfas` SET `max_lv` = `max_lv` + 1 WHERE `id` = $role_xinfa_id;
UPDATE `roles` SET `bank_balance` = `bank_balance` - $need_money WHERE `id` = $request->roleId;
SQL;

        Helpers::execSql($sql);

        loglog(LOG_XINFA_UPGRADE, '升级心法', [
            '玩家' => $request->roleRow->name,
            '心法' => $role_xinfa->row->name,
            '原始ID' => $role_xinfa->id,
            '消耗金钱' => $need_money,
            '钱庄存款' => $old,
            '剩余存款' => $request->roleRow->bank_balance,
        ]);

        return $connection->send(\cache_response($request, \view('Map/MysteriousOldMan/upgrade.twig', [
            'request' => $request,
            'role_xinfa' => $role_xinfa,
        ])));
    }


    /**
     * Tu vi转化经验Xác định询问
     *
     * @param TcpConnection $connection
     * @param Request $request
     *
     * @return bool|null
     */
    public function ExpToExpQuestion(TcpConnection $connection, Request $request)
    {
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        $hold_experience = intdiv($role_attrs->experience, 1000);
        if ($role_attrs->maxSkillLv < 180) {
            $experience = $hold_experience - 5832;
        } else {
            $experience = $hold_experience - pow($role_attrs->maxSkillLv / 10, 3);
            if ($experience > 0) {
                $experience = intval($experience);
            } else {
                $experience = 0;
            }
        }
        if ($experience < 0) {
            $experience = 0;
        }
        /**
         * 获取修炼中的心法
         */
        $sql = <<<SQL
SELECT `id` FROM `role_xinfas` WHERE `role_id` = $request->roleId AND `practiced` = 1;
SQL;

        $role_xinfa = Helpers::queryFetchObject($sql);
        if (!is_object($role_xinfa)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi đều không có tu luyện tâm pháp, còn chuyển hóa cái gì kinh nghiệm?',
            ])));
        }

        return $connection->send(\cache_response($request, \view('Map/MysteriousOldMan/ExpToExpQuestion.twig', [
            'request' => $request,
            'year' => Helpers::getHansNumber($experience),
        ])));
    }


    /**
     * Tu vi转化经验
     *
     * @param TcpConnection $connection
     * @param Request $request
     *
     * @return bool|null
     */
    public function ExpToExp(TcpConnection $connection, Request $request)
    {
        if (!cache()->set('lock_role_exp_to_exp_' . $request->roleId, 'ok', ['NX', 'EX' => 3])) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Mỗi ba giây đồng hồ chỉ có thể thao tác một lần',
            ])));
        }
        if (strtoupper($request->method()) !== 'POST') {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $year = trim($request->post('year'));
        if (!is_numeric($year)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Thỉnh đưa vào một hợp lý con số',
            ])));
        }
        $year = intval($year);
        if ($year < 1) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Thỉnh đưa vào một hợp lý con số',
            ])));
        }
        /**
         * 判断Tu vi是否足够
         */
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        $hold_experience = intdiv($role_attrs->experience, 1000);
        if ($role_attrs->maxSkillLv < 180) {
            $experience = $hold_experience - 5832;
        } else {
            $experience = $hold_experience - pow($role_attrs->maxSkillLv / 10, 3);
            if ($experience > 0) {
                $experience = intval($experience);
            } else {
                $experience = 0;
            }
        }
        if ($experience < 0) {
            $experience = 0;
        }
        if ($experience < $year) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi không có như vậy nhiều tu vi có thể chuyển hóa!',
            ])));
        }

        /**
         * 减少Tu vi
         */
        $now_d = (int)($role_attrs->experience / 1000);
        $role_attrs->experience -= $year * 1000;
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);

        /**
         * 获取修炼中的心法
         */
        $sql = <<<SQL
SELECT `id` FROM `role_xinfas` WHERE `role_id` = $request->roleId AND `practiced` = 1;
SQL;

        $role_xinfa = Helpers::queryFetchObject($sql);

        /**
         * 增加经验
         */
        $gain_exp = $year * 50;
        $sql = <<<SQL
UPDATE `role_xinfas` SET `experience` = `experience` + $gain_exp WHERE `id` = $role_xinfa->id;
SQL;


        Helpers::execSql($sql);
        loglog(LOG_EXPERIENCE, 'Tu vi转化', [
            '玩家' => $request->roleRow->name,
            '当前带修' => $now_d,
            '存入Tu vi' => $year,
            '剩余带修' => (int)($role_attrs->experience / 1000),
        ]);

        return $connection->send(\cache_response($request, \view('Map/MysteriousOldMan/ExpToExp.twig', [
            'request' => $request,
            'year' => Helpers::getHansNumber($year),
            'exp' => $gain_exp,
        ])));
    }
}
