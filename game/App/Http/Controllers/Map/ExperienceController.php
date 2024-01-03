<?php

namespace App\Http\Controllers\Map;

use App\Libs\Attrs\FlushRoleAttrs;
use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 集气门 存Tu vi
 *
 */
class ExperienceController
{
    /**
     * 存修首页
     *
     * @param TcpConnection $connection
     * @param Request $request
     *
     * @return bool|null
     */
    public function save(TcpConnection $connection, Request $request)
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
        $experience = Helpers::getHansNumber($experience) . '年';
        return $connection->send(\cache_response($request, \view('Map/Experience/save.twig', [
            'request' => $request,
            'experience' => $experience,
        ])));
    }


    /**
     * 取修首页
     *
     * @param TcpConnection $connection
     * @param Request $request
     *
     * @return bool|null
     */
    public function withdraw(TcpConnection $connection, Request $request)
    {
        if ($request->roleRow->saved_experience < 1) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi chưa bao giờ có tích tụ quá tu hành, đâu ra thu hồi vừa nói.',
            ])));
        }
        $experience = Helpers::getHansNumber($request->roleRow->saved_experience) . '年';
        return $connection->send(\cache_response($request, \view('Map/Experience/withdraw.twig', [
            'request' => $request,
            'experience' => $experience,
        ])));
    }


    /**
     * 取出Tu vi
     *
     * @param TcpConnection $connection
     * @param Request $request
     *
     * @return bool|null
     */
    public function withdrawPost(TcpConnection $connection, Request $request)
    {
        if (!cache()->set('lock_role_exp_' . $request->roleId, 'ok', ['NX', 'EX' => 3])) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Mỗi ba giây đồng hồ chỉ có thể thao tác một lần',
            ])));
        }
        if ($request->roleRow->saved_experience < 1) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi chưa bao giờ có tích tụ quá tu hành, đâu ra thu hồi vừa nói.',
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
                'message' => Helpers::randomSentence(),
            ])));
        }
        $year = intval($year);
        if ($year < 1) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        if ($year > $request->roleRow->saved_experience) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi có thể lấy ra tu vi không đủ' . Helpers::getHansNumber($year) . ' niên.',
            ])));
        }
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);

        $locker = 'role_experience_lock_' . $request->roleId;
        $token = microtime(true) . mt_rand(111111, 999999);
        $r = lock()->set($locker, $token, ['NX', 'PX' => 300,]);
        if (!$r) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Tạm thời vô pháp lấy tu vi',
            ])));
        }

        /**
         * 增加玩家Tu vi
         *
         */
        $now_d = (int)($role_attrs->experience / 1000);
        $role_attrs->experience += $year * 1000;

        /**
         * 减少玩家存修
         *
         */
        $now_c = $request->roleRow->saved_experience;
        $request->roleRow->saved_experience -= $year;

        /**
         * 保存玩家数据
         *
         */
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
        $sql = <<<SQL
UPDATE `roles` SET `saved_experience` = `saved_experience` - $year, `experience` = $role_attrs->experience WHERE `id` = $request->roleId;
SQL;


        Helpers::execSql($sql);

        FlushRoleAttrs::Update($role_attrs, $request->roleId);
        lock()->evalSha("if redis.call('get', KEYS[1]) == ARGV[1] then
redis.call('del', KEYS[1]) end", [$locker, $token,], 1);

        loglog(LOG_EXPERIENCE, '取出Tu vi', [
            '玩家' => $request->roleRow->name,
            '当前带修' => $now_d,
            '当前存修' => $now_c,
            '取出Tu vi' => $year,
            '剩余带修' => (int)($role_attrs->experience / 1000),
            '剩余存修' => $request->roleRow->saved_experience,
        ]);
        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => 'Ngươi thu hồi ' . Helpers::getHansNumber($year) . ' Năm tu hành.',
        ])));
    }


    /**
     * 存入Tu vi
     *
     * @param TcpConnection $connection
     * @param Request $request
     *
     * @return bool|null
     */
    public function savePost(TcpConnection $connection, Request $request)
    {
        if (!cache()->set('lock_role_exp_' . $request->roleId, 'ok', ['NX', 'EX' => 3])) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Mỗi ba giây đồng hồ chỉ có thể thao tác một lần',
            ])));
        }
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
                'message' => Helpers::randomSentence(),
            ])));
        }
        $year = intval($year);
        if ($year < 1) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        if ($year > $experience) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi có thể tích tụ tu vi không đủ' . Helpers::getHansNumber($year) . '年。',
            ])));
        }

        $locker = 'role_experience_lock_' . $request->roleId;
        $token = microtime(true) . mt_rand(111111, 999999);
        $r = lock()->set($locker, $token, ['NX', 'PX' => 300,]);
        if (!$r) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Tạm thời vô pháp tồn tu vi',
            ])));
        }
        /**
         * 玩家减少Tu vi
         *
         */
        $now_d = (int)($role_attrs->experience / 1000);
        $role_attrs->experience -= $year * 1000;

        /**
         * 增加玩家存修
         *
         */
        $now_c = $request->roleRow->saved_experience;
        $request->roleRow->saved_experience += $year;

        /**
         * 保存玩家数据
         *
         */
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        $sql = <<<SQL
UPDATE `roles` SET `saved_experience` = `saved_experience` + $year, `experience` = $role_attrs->experience WHERE `id` = $request->roleId;
SQL;


        Helpers::execSql($sql);

        FlushRoleAttrs::Update($role_attrs, $request->roleId);
        lock()->evalSha("if redis.call('get', KEYS[1]) == ARGV[1] then
redis.call('del', KEYS[1]) end", [$locker, $token,], 1);

        loglog(LOG_EXPERIENCE, '存入Tu vi', [
            '玩家' => $request->roleRow->name,
            '当前带修' => $now_d,
            '当前存修' => $now_c,
            '存入Tu vi' => $year,
            '剩余带修' => (int)($role_attrs->experience / 1000),
            '剩余存修' => $request->roleRow->saved_experience,
        ]);
        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => 'Ngươi cộng tích tụ ' . Helpers::getHansNumber($year) . ' Năm tu hành.',
        ])));
    }


    /**
     * 查询存修
     *
     * @param TcpConnection $connection
     * @param Request $request
     *
     * @return bool|null
     */
    public function check(TcpConnection $connection, Request $request)
    {
        if ($request->roleRow->saved_experience > 0) {
            $experience = Helpers::getHansNumber($request->roleRow->saved_experience) . '年';
        }
        return $connection->send(\cache_response($request, \view('Map/Experience/check.twig', [
            'request' => $request,
            'experience' => $experience ?? null,
        ])));
    }
}
