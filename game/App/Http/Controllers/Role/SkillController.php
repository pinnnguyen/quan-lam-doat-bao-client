<?php

namespace App\Http\Controllers\Role;

use App\Libs\Attrs\FlushRoleAttrs;
use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 角色技能
 *
 */
class SkillController
{
    /**
     * 技能列表
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        /**
         * 获取角色所有技能
         */
        $sql = <<<SQL
SELECT `role_skills`.`id`, `set_role_skill_id`, `lv`, `experience`, `name`, `is_base` FROM `role_skills` INNER JOIN `skills`
ON `role_skills`.`skill_id` = `skills`.`id` WHERE `role_skills`.`role_id` = $request->roleId;
SQL;

        $role_skills = Helpers::queryFetchAll($sql);
        /**
         * 获取已经配置的技能
         */
        $sets = [];
        foreach ($role_skills as $role_skill) {
            $role_skill->viewUrl = 'Role/Skill/view/' . $role_skill->id;
            if ($role_skill->set_role_skill_id) {
                $sets[] = $role_skill->set_role_skill_id;
            }
            $role_skill->percent = Helpers::getPercent($role_skill->experience, Helpers::getSkillExp($role_skill));
            if ($role_skill->percent >= 100) {
                $role_skill->percent = 99;
            }
        }

        foreach ($role_skills as $role_skill) {
            if (in_array($role_skill->id, $sets)) {
                $role_skill->set = true;
            } else {
                $role_skill->set = false;
            }
        }

        return $connection->send(\cache_response($request, \view('Role/Skill/index.twig', [
            'request'           => $request,
            'role_skills'       => $role_skills,
            'role_skills_count' => count($role_skills),
        ])));
    }


    /**
     * Xem xét技能
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_skill_id
     *
     * @return bool|null
     */
    public function view(TcpConnection $connection, Request $request, int $role_skill_id)
    {
        /**
         * 获取当前技能
         */
        $sql = <<<SQL
SELECT `role_skills`.`id`, `lv`, `experience`, `name`, `description`, `kind`, `is_base` FROM `role_skills` INNER JOIN `skills`
ON `skill_id` = `skills`.`id` WHERE `role_skills`.`id` = $role_skill_id LIMIT 1;
SQL;

        $role_skill = Helpers::queryFetchObject($sql);

        /**
         * 获取可配置技能
         */
        if ($role_skill->is_base) {
            if ($role_skill->kind == '招架') {
                $sql = <<<SQL
SELECT `role_skills`.`id`, `lv`, `experience`, `name`,  `is_base` FROM `role_skills` INNER JOIN `skills` ON `skill_id` = `skills`.`id`
WHERE `kind` IN ('拳脚', '刀法', '剑法') AND `is_base` != 1 AND `role_id` = $request->roleId;
SQL;

            } else {
                $sql = <<<SQL
SELECT `role_skills`.`id`, `lv`, `experience`, `name`,  `is_base` FROM `role_skills` INNER JOIN `skills` ON `skill_id` = `skills`.`id`
WHERE `kind` = '$role_skill->kind' AND `is_base` != 1 AND `role_id` = $request->roleId;
SQL;

            }
            $role_skills = Helpers::queryFetchAll($sql);
            foreach ($role_skills as $_role_skill) {
                if ($role_skill->kind == '招架') {
                    $_role_skill->setUrl = 'Role/Skill/set/' . $_role_skill->id . '/1';
                } else {
                    $_role_skill->setUrl = 'Role/Skill/set/' . $_role_skill->id;
                }
            }
        }
        return $connection->send(\cache_response($request, \view('Role/Skill/view.twig', [
            'request'     => $request,
            'role_skill'  => $role_skill,
            'role_skills' => $role_skills ?? null,
        ])));
    }


    /**
     * 配置门派技能
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_skill_id
     * @param int           $zhaojia
     *
     * @return bool|null
     */
    public function set(TcpConnection $connection, Request $request, int $role_skill_id, int $zhaojia = 0)
    {
        /**
         * 查询该技能
         */
        $sql = <<<SQL
SELECT `role_skills`.`id`, `kind`, `name` FROM `role_skills` INNER JOIN `skills` ON `skill_id` = `skills`.`id`
WHERE `role_skills`.`id` = $role_skill_id LIMIT 1;
SQL;

        $role_skill = Helpers::queryFetchObject($sql);

        /**
         * 修改配置技能
         */
        if ($zhaojia) {
            $sql = <<<SQL
UPDATE `role_skills` INNER JOIN `skills` ON `skill_id` = `skills`.`id` AND `kind` = '招架'
AND `is_base` = 1 SET `set_role_skill_id` = $role_skill_id WHERE `role_id` = $request->roleId;
SQL;

        } else {
            $sql = <<<SQL
UPDATE `role_skills` INNER JOIN `skills` ON `skill_id` = `skills`.`id` AND `kind` = '$role_skill->kind'
AND `is_base` = 1 SET `set_role_skill_id` = $role_skill_id WHERE `role_id` = $request->roleId;
SQL;

        }

        Helpers::execSql($sql);


        /**
         * Làm mới属性
         */
        FlushRoleAttrs::fromRoleSkillByRoleId($request->roleId);

        return $connection->send(\cache_response($request, \view('Role/Skill/set.twig', [
            'request'    => $request,
            'role_skill' => $role_skill,
        ])));
    }
}
