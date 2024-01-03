<?php

namespace App\Http\Controllers\Func;

use App\Libs\Attrs\FlushRoleAttrs;
use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 劈树
 *
 */
class SplitTreeController
{
    /**
     * 劈 一棵小树
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function splitSmallTree(TcpConnection $connection, Request $request)
    {
        $count = cache()->incr('role_split_tree_' . $request->roleId);
        if ($count > 20) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi một tiếng hét to, một chưởng mãnh lực về phía trước đánh ra, tức khắc chỉ nghe “Răng rắc” một tiếng vang lớn, cây nhỏ theo tiếng mà đoạn.',
            ])));
        }
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->weaponKind !== 0 and $role_attrs->weaponKind !== 3) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi cầm vũ khí như thế nào có thể luyện tập hảo cơ bản quyền cước?',
            ])));
        }

        if ($role_attrs->jingshen < 20) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi đang muốn một chưởng bổ về phía cây nhỏ, chợt thấy dị thường mỏi mệt, ngươi khả năng yêu cầu hơi làm nghỉ ngơi.',
            ])));
        }

        /**
         * 获取Cơ bản quyền cước
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` = 5;
SQL;

        $role_skill = Helpers::queryFetchObject($sql);
        if (!is_object($role_skill)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $role_skill->row = Helpers::getSkillRowBySkillId(5);
        if ($role_skill->lv < 60) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi vận đủ nội lực tụ với song chưởng phía trên, đột nhiên xuất chưởng đánh về phía cây nhỏ, cây nhỏ không chút sứt mẻ. Ngươi cảm giác ngươi quyền cước công phu quá kém, yêu cầu trước tìm mặt khác biện pháp tiến hành tăng lên.',
            ])));
        }
        if ($role_skill->lv >= 90) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi một tiếng hét to, một chưởng mãnh lực về phía trước đánh ra, tức khắc chỉ nghe “Răng rắc” một tiếng vang lớn, cây nhỏ theo tiếng mà đoạn.',
            ])));
        }

        /**
         * 减少精力
         *
         */
        $role_attrs->jingshen -= 20;
        $role_attrs->jingshen = $role_attrs->jingshen < 0 ? 0 : $role_attrs->jingshen;
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
        cache()->expire('role_split_tree_' . $request->roleId, 5);
        return $connection->send(\cache_response($request, \view('Func/SplitTree/smallTreeSucceed.twig', [
            'request' => $request,
            'lv'      => $lv,
            'percent' => sprintf('%.1f', $experience / $up_exp * 100),
        ])));
    }


    /**
     * 劈 一棵大树
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function splitBigTree(TcpConnection $connection, Request $request)
    {
        $count = cache()->incr('role_split_tree_' . $request->roleId);
        if ($count > 20) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi một tiếng hét to, một chưởng mãnh lực về phía trước đánh ra, tức khắc chỉ nghe “Răng rắc” một tiếng vang lớn, đại thụ theo tiếng mà đoạn.',
            ])));
        }
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->weaponKind !== 0 and $role_attrs->weaponKind !== 3) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi cầm vũ khí như thế nào có thể luyện tập hảo cơ bản quyền cước?',
            ])));
        }

        if ($role_attrs->jingshen < 20) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi đang muốn một chưởng bổ về phía đại thụ, chợt thấy dị thường mỏi mệt, ngươi khả năng yêu cầu hơi làm nghỉ ngơi.',
            ])));
        }

        /**
         * 获取Cơ bản quyền cước
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` = 5;
SQL;

        $role_skill = Helpers::queryFetchObject($sql);
        if (!is_object($role_skill)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $role_skill->row = Helpers::getSkillRowBySkillId(5);
        if ($role_skill->lv < 90) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi vận đủ nội lực tụ với song chưởng phía trên, đột nhiên xuất chưởng đánh về phía đại thụ, đại thụ không chút sứt mẻ. Ngươi cảm giác ngươi quyền cước công phu quá kém, yêu cầu trước tìm mặt khác biện pháp tiến hành tăng lên.',
            ])));
        }
        if ($role_skill->lv >= 120) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi một tiếng hét to, một chưởng mãnh lực về phía trước đánh ra, tức khắc chỉ nghe “Răng rắc” một tiếng vang lớn, đại thụ theo tiếng mà đoạn.',
            ])));
        }

        /**
         * 减少精力
         *
         */
        $role_attrs->jingshen -= 20;
        $role_attrs->jingshen = $role_attrs->jingshen < 0 ? 0 : $role_attrs->jingshen;
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
        cache()->expire('role_split_tree_' . $request->roleId, 5);
        return $connection->send(\cache_response($request, \view('Func/SplitTree/bigTreeSucceed.twig', [
            'request' => $request,
            'lv'      => $lv,
            'percent' => sprintf('%.1f', $experience / $up_exp * 100),
        ])));
    }


    /**
     * 劈 参天古树
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function splitOldTree(TcpConnection $connection, Request $request)
    {
        $count = cache()->incr('role_split_tree_' . $request->roleId);
        if ($count > 20) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi một tiếng hét to, một chưởng mãnh lực về phía trước đánh ra, tức khắc chỉ nghe “Răng rắc” một tiếng vang lớn, cổ thụ theo tiếng mà đoạn.',
            ])));
        }
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->weaponKind !== 0 and $role_attrs->weaponKind !== 3) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi cầm vũ khí như thế nào có thể luyện tập hảo cơ bản quyền cước?',
            ])));
        }

        if ($role_attrs->jingshen < 20) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi đang muốn một chưởng bổ về phía cổ thụ, chợt thấy dị thường mỏi mệt, ngươi khả năng yêu cầu hơi làm nghỉ ngơi.',
            ])));
        }

        /**
         * 获取Cơ bản quyền cước
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` = 5;
SQL;

        $role_skill = Helpers::queryFetchObject($sql);
        if (!is_object($role_skill)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $role_skill->row = Helpers::getSkillRowBySkillId(5);
        if ($role_skill->lv < 120) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi vận đủ nội lực tụ với song chưởng phía trên, đột nhiên xuất chưởng đánh về phía cổ thụ, cổ thụ không chút sứt mẻ. Ngươi cảm giác ngươi quyền cước công phu quá kém, yêu cầu trước tìm mặt khác biện pháp tiến hành tăng lên.',
            ])));
        }
        if ($role_skill->lv >= 150) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi một tiếng hét to, một chưởng mãnh lực về phía trước đánh ra, tức khắc chỉ nghe “Răng rắc” một tiếng vang lớn, cổ thụ theo tiếng mà đoạn.',
            ])));
        }

        /**
         * 减少精力
         *
         */
        $role_attrs->jingshen -= 20;
        $role_attrs->jingshen = $role_attrs->jingshen < 0 ? 0 : $role_attrs->jingshen;
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
        cache()->expire('role_split_tree_' . $request->roleId, 5);
        return $connection->send(\cache_response($request, \view('Func/SplitTree/oldTreeSucceed.twig', [
            'request' => $request,
            'lv'      => $lv,
            'percent' => sprintf('%.1f', $experience / $up_exp * 100),
        ])));
    }


    /**
     * 一棵小树
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function viewSmallTree(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Func/SplitTree/smallTree.twig', [
            'request' => $request,
        ])));
    }


    /**
     * 一棵大树
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function viewBigTree(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Func/SplitTree/bigTree.twig', [
            'request' => $request,
        ])));
    }


    /**
     * 参天古树
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function viewOldTree(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Func/SplitTree/oldTree.twig', [
            'request' => $request,
        ])));
    }
}
