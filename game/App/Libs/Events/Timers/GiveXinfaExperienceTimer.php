<?php

namespace App\Libs\Events\Timers;

use App\Core\Configs\FlushConfig;
use App\Libs\Attrs\FlushRoleAttrs;
use App\Libs\Helpers;

/**
 * 给予心法经验
 *
 */
class GiveXinfaExperienceTimer
{
    public static function hook()
    {
        /**
         * 获取所有修炼中的心法
         */
        $sql = <<<SQL
SELECT `id`, `max_lv`, `lv`, `experience`, `role_id`, `base_experience` FROM `role_xinfas` WHERE `practiced` = 1;
SQL;

        $xinfas = Helpers::queryFetchAll($sql);

        if (count($xinfas) > 0) {
            $sql = '';
            $roles = [];
            foreach ($xinfas as $xinfa) {
                $role_attrs = Helpers::getRoleAttrsByRoleId($xinfa->role_id);
                if (is_object($role_attrs) and $role_attrs->isFighting) {
                    $start_lv = $xinfa->lv;
                    $start_experience = $xinfa->experience;
                    $ratio = Helpers::getXinfaExperienceRatio();
                    if ($role_attrs->double_xinfa > time()) {
                        $ratio += 1;
                    }
                    if ($role_attrs->triple_xinfa > time()) {
                        $ratio += 2;
                    }
                    $lv = $xinfa->lv;
                    $experience = 0;
                    $gain_experience = intval(FlushConfig::XINFA_SYNC * 800 / 60 * $ratio) + $xinfa->experience;

                    CAL:
                    $need_experience = $lv * $lv * $xinfa->base_experience;
                    if ($gain_experience > $need_experience) {
                        if ($xinfa->max_lv < $lv) {
                            $experience = $gain_experience;
                        } else {
                            $gain_experience -= $need_experience;
                            $lv = $lv + 1;
                            $roles[] = $xinfa->role_id;
                            goto CAL;
                        }
                    } elseif ($gain_experience == $need_experience) {
                        if ($xinfa->max_lv < $lv) {
                            $experience = $gain_experience;
                        } else {
                            $lv = $lv + 1;
                            $roles[] = $xinfa->role_id;
                        }
                    } else {
                        $experience = $gain_experience;
                    }
                    $mid_lv = $lv - $start_lv;
                    $mid_experience = $start_experience - $experience;
                    $sql .= <<<SQL
UPDATE `role_xinfas` SET `experience` = `experience` - $mid_experience, `lv` = `lv` + $mid_lv WHERE `id` = $xinfa->id;
SQL;

                }
            }
            if ($sql !== '') {

                Helpers::execSql($sql);

            }
            if (!empty($roles)) {
                $roles = array_unique($roles);
                array_map(function ($role) {
                    FlushRoleAttrs::fromRoleXinfaByRoleId($role);
                }, $roles);
            }
        }
    }
}
