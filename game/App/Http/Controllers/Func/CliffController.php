<?php

namespace App\Http\Controllers\Func;

use App\Libs\Attrs\FlushRoleAttrs;
use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 悬崖
 *
 */
class CliffController
{
    /**
     * A 悬崖底 爬崖
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function aClimb(TcpConnection $connection, Request $request)
    {
        /**
         * 获取个人属性
         *
         */
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->mp < 100) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => ' Ngươi nội lực khô kiệt, vận không được khinh công.',
            ])));
        }

        /**
         * 获取轻功技能
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` = 6;
SQL;

        $role_skill = Helpers::queryFetchObject($sql);
        if (!is_object($role_skill)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $role_skill->row = Helpers::getSkillRowBySkillId(6);
        if ($role_skill->lv < 60) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Tứ phía trơn bóng nhai đẩu như vách tường, ngươi khinh công không đủ, như thế nào bò cũng bò không đi lên.',
            ])));
        }
        if ($role_skill->lv >= 90) {
            return $connection->send(\cache_response($request, \view('Func/Cliff/aLookAround.twig', [
                'request' => $request,
            ])));
        }

        if (Helpers::getProbability(1, 10)) {
            $role_attrs->hp -= 100;
            $role_attrs->hp = $role_attrs->hp < 0 ? 0 : $role_attrs->hp;
            Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
            cache()->del('role_climb_count_' . $request->roleId);
            return $connection->send(\cache_response($request, \view('Base/messages.twig', [
                'request'  => $request,
                'messages' => [
                    'Ngươi đề ra một ngụm chân khí, đôi tay phát kính, dưới chân liền điểm, đạp trên vách núi xông ra cục đá, thả người hướng về phía trước phàn càng.',
                    'Ngươi cảm thấy một ngụm chân khí dùng hết, khinh công dần dần vô dụng, hoảng loạn bên trong, từ trên vách núi ngã xuống dưới, quăng ngã cái mặt mũi bầm dập.',
                ],
            ])));
        }

        /**
         * 减少Nội lực
         *
         */
        $role_attrs->mp -= 100;
        $role_attrs->mp = $role_attrs->mp < 0 ? 0 : $role_attrs->mp;
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);

        /**
         * 提升技能等级
         *
         */
        $role_skill->is_base = true;
        $experience = $role_skill->experience;
        $lv = $role_skill->lv;
        $up_exp = Helpers::getSkillExp($role_skill);
        $experience += intval($up_exp / 1000);
        $upgraded = false;
        if ($experience >= $up_exp) {
            $experience -= $up_exp;
            $lv += 1;
            $role_skill->lv = $lv;
            $up_exp = Helpers::getSkillExp($role_skill);
            $upgraded = true;
        }
        $sql = <<<SQL
UPDATE `role_skills` SET `experience` = $experience, `lv` = $lv WHERE `id` = $role_skill->id;
SQL;

        Helpers::execSql($sql);
        if ($upgraded) {
            FlushRoleAttrs::fromRoleSkillByRoleId($request->roleId);
        }
        $count = cache()->incr('role_climb_count_' . $request->roleId);
        if ($count >= 10) {
            cache()->del('role_climb_count_' . $request->roleId);
            $around_url = 'Map/Index/delivery/749';
        } else {
            $around_url = 'Func/Cliff/aClimb';
        }
        return $connection->send(\cache_response($request, \view('Func/Cliff/aClimb.twig', [
            'request'    => $request,
            'lv'         => $lv,
            'percent'    => sprintf('%.1f', $experience / $up_exp * 100),
            'around_url' => $around_url,
        ])));
    }


    /**
     * B 小平台 爬崖
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function bClimb(TcpConnection $connection, Request $request)
    {
        /**
         * 获取个人属性
         *
         */
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->mp < 100) {
            return $connection->send(\cache_response($request, \view('Func/Cliff/bFailed.twig', [
                'request' => $request,
                'message' => 'Ngươi nội lực khô kiệt, vận không được khinh công.',
            ])));
        }

        /**
         * 获取轻功技能
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` = 6;
SQL;

        $role_skill = Helpers::queryFetchObject($sql);
        if (!is_object($role_skill)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $role_skill->row = Helpers::getSkillRowBySkillId(6);
        if ($role_skill->lv < 90) {
            return $connection->send(\cache_response($request, \view('Func/Cliff/bFailed.twig', [
                'request' => $request,
                'message' => 'Tứ phía trơn bóng nhai đẩu như vách tường, ngươi khinh công không đủ, như thế nào bò cũng bò không đi lên.',
            ])));
        }
        if ($role_skill->lv >= 120) {
            return $connection->send(\cache_response($request, \view('Func/Cliff/bLookAround.twig', [
                'request' => $request,
            ])));
        }

        if (Helpers::getProbability(1, 10)) {
            $role_attrs->hp -= 100;
            $role_attrs->hp = $role_attrs->hp < 0 ? 0 : $role_attrs->hp;
            Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
            cache()->del('role_climb_count_' . $request->roleId);
            return $connection->send(\cache_response($request, \view('Func/Cliff/bFailed.twig', [
                'request' => $request,
                'message' => 'Ngươi đề ra một ngụm chân khí, đôi tay phát kính, dưới chân liền điểm, đạp trên vách núi xông ra cục đá, thả người hướng về phía trước phàn càng.<br/>Ngươi cảm thấy một ngụm chân khí dùng hết, khinh công dần dần vô dụng, hoảng loạn bên trong, từ trên vách núi ngã xuống dưới, quăng ngã cái mặt mũi bầm dập.',
            ])));
        }

        /**
         * 减少Nội lực
         *
         */
        $role_attrs->mp -= 100;
        $role_attrs->mp = $role_attrs->mp < 0 ? 0 : $role_attrs->mp;
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);

        /**
         * 提升技能等级
         *
         */
        $role_skill->is_base = true;
        $experience = $role_skill->experience;
        $lv = $role_skill->lv;
        $up_exp = Helpers::getSkillExp($role_skill);
        $experience += intval($up_exp / 1000);
        $upgraded = false;
        if ($experience >= $up_exp) {
            $experience -= $up_exp;
            $lv += 1;
            $role_skill->lv = $lv;
            $up_exp = Helpers::getSkillExp($role_skill);
            $upgraded = true;
        }
        $sql = <<<SQL
UPDATE `role_skills` SET `experience` = $experience, `lv` = $lv WHERE `id` = $role_skill->id;
SQL;

        Helpers::execSql($sql);
        if ($upgraded) {
            FlushRoleAttrs::fromRoleSkillByRoleId($request->roleId);
        }
        $count = cache()->incr('role_climb_count_' . $request->roleId);
        if ($count >= 10) {
            cache()->del('role_climb_count_' . $request->roleId);
            $around_url = 'Map/Index/delivery/748';
        } else {
            $around_url = 'Func/Cliff/bClimb';
        }
        return $connection->send(\cache_response($request, \view('Func/Cliff/bClimb.twig', [
            'request'    => $request,
            'lv'         => $lv,
            'percent'    => sprintf('%.1f', $experience / $up_exp * 100),
            'around_url' => $around_url,
        ])));
    }


    /**
     * C 半崖
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function cClimb(TcpConnection $connection, Request $request)
    {
        /**
         * 获取个人属性
         *
         */
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->mp < 100) {
            return $connection->send(\cache_response($request, \view('Func/Cliff/cFailed.twig', [
                'request' => $request,
                'message' => 'Ngươi nội lực khô kiệt, vận không được khinh công.',
            ])));
        }

        /**
         * 获取轻功技能
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` = 6;
SQL;

        $role_skill = Helpers::queryFetchObject($sql);
        if (!is_object($role_skill)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $role_skill->row = Helpers::getSkillRowBySkillId(6);
        if ($role_skill->lv < 120) {
            return $connection->send(\cache_response($request, \view('Func/Cliff/cFailed.twig', [
                'request' => $request,
                'message' => 'Tứ phía trơn bóng nhai đẩu như vách tường, ngươi khinh công không đủ, như thế nào bò cũng bò không đi lên.',
            ])));
        }
        if ($role_skill->lv >= 150) {
            return $connection->send(\cache_response($request, \view('Func/Cliff/cLookAround.twig', [
                'request' => $request,
            ])));
        }

        if (Helpers::getProbability(1, 10)) {
            $role_attrs->hp -= 100;
            $role_attrs->hp = $role_attrs->hp < 0 ? 0 : $role_attrs->hp;
            Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
            cache()->del('role_climb_count_' . $request->roleId);
            return $connection->send(\cache_response($request, \view('Func/Cliff/cFailed.twig', [
                'request' => $request,
                'message' => 'Ngươi đề ra một ngụm chân khí, đôi tay phát kính, dưới chân liền điểm, đạp trên vách núi xông ra cục đá, thả người hướng về phía trước phàn càng.<br/>Ngươi cảm thấy một ngụm chân khí dùng hết, khinh công dần dần vô dụng, hoảng loạn bên trong, từ trên vách núi ngã xuống dưới, quăng ngã cái mặt mũi bầm dập.',
            ])));
        }

        /**
         * 减少Nội lực
         *
         */
        $role_attrs->mp -= 100;
        $role_attrs->mp = $role_attrs->mp < 0 ? 0 : $role_attrs->mp;
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);

        /**
         * 提升技能等级
         *
         */
        $role_skill->is_base = true;
        $experience = $role_skill->experience;
        $lv = $role_skill->lv;
        $up_exp = Helpers::getSkillExp($role_skill);
        $experience += intval($up_exp / 1000);
        $upgraded = false;
        if ($experience >= $up_exp) {
            $experience -= $up_exp;
            $lv += 1;
            $role_skill->lv = $lv;
            $up_exp = Helpers::getSkillExp($role_skill);
            $upgraded = true;
        }
        $sql = <<<SQL
UPDATE `role_skills` SET `experience` = $experience, `lv` = $lv WHERE `id` = $role_skill->id;
SQL;

        Helpers::execSql($sql);
        if ($upgraded) {
            FlushRoleAttrs::fromRoleSkillByRoleId($request->roleId);
        }
        $count = cache()->incr('role_climb_count_' . $request->roleId);
        if ($count >= 10) {
            cache()->del('role_climb_count_' . $request->roleId);
            $around_url = 'Map/Index/delivery/751';
        } else {
            $around_url = 'Func/Cliff/cClimb';
        }
        return $connection->send(\cache_response($request, \view('Func/Cliff/cClimb.twig', [
            'request'    => $request,
            'lv'         => $lv,
            'percent'    => sprintf('%.1f', $experience / $up_exp * 100),
            'around_url' => $around_url,
        ])));
    }


    /**
     * D 悬崖
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function dClimb(TcpConnection $connection, Request $request)
    {
        /**
         * 获取个人属性
         *
         */
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->mp < 100) {
            return $connection->send(\cache_response($request, \view('Func/Cliff/dFailed.twig', [
                'request' => $request,
                'message' => 'Ngươi nội lực khô kiệt, vận không được khinh công.',
            ])));
        }

        /**
         * 获取轻功技能
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` = 6;
SQL;

        $role_skill = Helpers::queryFetchObject($sql);
        if (!is_object($role_skill)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $role_skill->row = Helpers::getSkillRowBySkillId(6);
        if ($role_skill->lv >= 150) {
            return $connection->send(\cache_response($request, \view('Func/Cliff/dLookAround.twig', [
                'request' => $request,
            ])));
        }

        if (Helpers::getProbability(1, 10)) {
            $role_attrs->hp -= 100;
            $role_attrs->hp = $role_attrs->hp < 0 ? 0 : $role_attrs->hp;
            Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
            cache()->del('role_climb_count_' . $request->roleId);
            return $connection->send(\cache_response($request, \view('Func/Cliff/dFailed.twig', [
                'request' => $request,
                'message' => 'Ngươi đề ra một ngụm chân khí, đôi tay phát kính, dưới chân liền điểm, đạp trên vách núi xông ra cục đá, thả người hướng về phía trước phàn càng. <br/> ngươi cảm thấy một ngụm chân khí dùng hết, khinh công dần dần vô dụng, hoảng loạn bên trong, từ trên vách núi ngã xuống dưới, quăng ngã cái mặt mũi bầm dập.',
            ])));
        }

        /**
         * 减少Nội lực
         *
         */
        $role_attrs->mp -= 100;
        $role_attrs->mp = $role_attrs->mp < 0 ? 0 : $role_attrs->mp;
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);

        /**
         * 提升技能等级
         *
         */
        $role_skill->is_base = true;
        $experience = $role_skill->experience;
        $lv = $role_skill->lv;
        $up_exp = Helpers::getSkillExp($role_skill);
        $experience += intval($up_exp / 1000);
        $upgraded = false;
        if ($experience >= $up_exp) {
            $experience -= $up_exp;
            $lv += 1;
            $role_skill->lv = $lv;
            $up_exp = Helpers::getSkillExp($role_skill);
            $upgraded = true;
        }
        $sql = <<<SQL
UPDATE `role_skills` SET `experience` = $experience, `lv` = $lv WHERE `id` = $role_skill->id;
SQL;

        Helpers::execSql($sql);
        if ($upgraded) {
            FlushRoleAttrs::fromRoleSkillByRoleId($request->roleId);
        }
        $count = cache()->incr('role_climb_count_' . $request->roleId);
        if ($count >= 10) {
            cache()->del('role_climb_count_' . $request->roleId);
            $around_url = 'Map/Index/delivery/751';
        } else {
            $around_url = 'Func/Cliff/cClimb';
        }
        return $connection->send(\cache_response($request, \view('Func/Cliff/dClimb.twig', [
            'request'    => $request,
            'lv'         => $lv,
            'percent'    => sprintf('%.1f', $experience / $up_exp * 100),
            'around_url' => $around_url,
        ])));
    }


    /**
     * B 下崖
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function bDown(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Func/Cliff/bDown.twig', [
            'request' => $request,
        ])));
    }


    /**
     * C 下崖
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function cDown(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Func/Cliff/cDown.twig', [
            'request' => $request,
        ])));
    }


    /**
     * D 下崖
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function dDown(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Func/Cliff/dDown.twig', [
            'request' => $request,
        ])));
    }


    /**
     * E 下崖
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function eDown(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Func/Cliff/eDown.twig', [
            'request' => $request,
        ])));
    }
}
