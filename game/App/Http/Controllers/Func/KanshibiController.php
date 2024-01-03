<?php

namespace App\Http\Controllers\Func;

use App\Libs\Attrs\FlushRoleAttrs;
use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 砍石壁
 *
 */
class KanshibiController
{
    /**
     * Bắt đầu砍石壁
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function start(TcpConnection $connection, Request $request)
    {
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `role_id` = $request->roleId AND `equipped` = 1;
SQL;

        $role_things = Helpers::queryFetchAll($sql);
        if (!is_array($role_things)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi không có trang bị đao hoặc là kiếm như thế nào chém vách đá?',
            ])));
        }
        foreach ($role_things as $role_thing) {
            $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
            if ($role_thing->row->equipment_kind === 1 or $role_thing->row->equipment_kind === 2) {
                $role_thing_id = $role_thing->id;
                $role_equipment = $role_thing;
            }
        }
        if (empty($role_thing_id) or empty($role_equipment) or $role_thing_id == 0) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi không có trang bị đao hoặc là kiếm như thế nào chém vách đá?',
            ])));
        }
        if ($role_equipment->row->attack < 30) {
            return $connection->send(\cache_response($request, \view('Func/Kanshibi/failed.twig', [
                'request'        => $request,
                'role_equipment' => $role_equipment,
            ])));
        }
        if ($role_equipment->durability < $role_equipment->row->max_durability / 2) {
            return $connection->send(\cache_response($request, \view('Func/Kanshibi/failed.twig', [
                'request'        => $request,
                'role_equipment' => $role_equipment,
            ])));
        }

        /**
         * 更新武器属性
         */
        $sql = <<<SQL
UPDATE `role_things` SET `durability` = 0 WHERE `id` = $role_equipment->id;
SQL;

        Helpers::execSql($sql);
        FlushRoleAttrs::fromRoleEquipmentByRoleId($request->roleId);
        return $connection->send(\cache_response($request, \view('Func/Kanshibi/succeed.twig', [
            'request'        => $request,
            'role_equipment' => $role_equipment,
        ])));
    }


    /**
     * 面壁
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function upgrade(TcpConnection $connection, Request $request)
    {
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->weaponKind !== 1 and $role_attrs->weaponKind !== 2) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi không có trang bị đao hoặc là kiếm, vô pháp tiến hành lĩnh ngộ.',
            ])));
        }
        if ($role_attrs->jingshen < 50) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => ' Ngươi quá mệt mỏi, không cách nào tập trung tinh lực.',
            ])));
        }
        if ($role_attrs->weaponKind === 1) {
            $skill_id = 2;
        } else {
            $skill_id = 1;
        }
        $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` = $skill_id;
SQL;

        $role_skill = Helpers::queryFetchObject($sql);
        if (!is_object($role_skill)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $role_skill->row = Helpers::getSkillRowBySkillId($skill_id);
        if ($role_skill->lv < 60) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Bạn nói đúng ' . $role_skill->row->name . ' hiểu biết hiển nhiên quá thấp, vô pháp lĩnh ngộ vách đá nội dung.',
            ])));
        }
        if ($role_skill->lv >= 150) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi cảm thấy trên vách đá chiêu thức thập phần thô thiển, đã mất pháp tăng lên ngươi ' . $role_skill->row->name . '。',
            ])));
        }

        /**
         * 减少精力
         *
         */
        $role_attrs->jingshen -= 50;
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
        $sql = '';
        $upgraded = false;
        if ($experience >= $up_exp) {
            $experience -= $up_exp;
            $lv += 1;
            $role_skill->lv = $lv;
            $up_exp = Helpers::getSkillExp($role_skill);
            $upgraded = true;
        }
        $sql .= <<<SQL
UPDATE `role_skills` SET `experience` = $experience, `lv` = $lv WHERE `id` = $role_skill->id;
SQL;

        Helpers::execSql($sql);
        if ($upgraded) {
            FlushRoleAttrs::fromRoleSkillByRoleId($request->roleId);
        }
        return $connection->send(\cache_response($request, \view('Func/Kanshibi/upgrade.twig', [
            'request'    => $request,
            'role_skill' => $role_skill,
            'lv'         => $lv,
            'percent'    => sprintf('%.1f', $experience / $up_exp * 100),
        ])));
    }
}
