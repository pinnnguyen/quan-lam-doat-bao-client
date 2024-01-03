<?php

namespace App\Libs\Events\Timers;

use App\Libs\Helpers;
use App\Libs\Objects\RoleRow;

/**
 * 清理离线玩家
 *
 */
class ClearOfflineRoleTimer
{
    public static function hook()
    {
        /**
         * 获取所玩家数据
         *
         */
        $roles_key = cache()->keys('role_row_*');
        if (is_array($roles_key)) {
            foreach ($roles_key as $role_key) {
                $role_row = cache()->get($role_key);
                if ($role_row) {
                    /**
                     * 检查是否离线
                     *
                     */
                    $role_id = cache()->get('role_id_' . $role_row->sid);
                    if (empty($role_id)) {
                        self::sync($role_row);
                    }
                }
            }
        }
    }


    /**
     * 同步玩家信息
     *
     * @param RoleRow $role_row
     */
    public static function sync(RoleRow &$role_row, bool $clear_row_attrs = true)
    {
        /**
         * 同步连续任务
         *
         */
        $mission = json_decode($role_row->mission);
        if ($mission) {
            if ($mission->verified and $mission->expireTimestamp < time()) {
                /**
                 * 取消任务
                 *
                 */
                $sql = <<<SQL
UPDATE `roles` SET `mission` = NULL WHERE `id` = $role_row->id;
SQL;


                Helpers::execSql($sql);
            }
            $mission = '\'' . $role_row->mission . '\'';
        } else {
            $mission = 'NULL';
        }

        /**
         * 删除地图在线状态
         *
         */
        cache()->sRem('map_roles_' . $role_row->map_id, $role_row->id);

        /**
         * 获取 role_attrs
         */
        $role_attrs = Helpers::getRoleAttrsByRoleId($role_row->id);

        if ($role_attrs) {
            $sql = '';
            if ($role_attrs->weaponRoleThingId !== 0) {
                $sql .= <<<SQL
UPDATE `role_things` SET `durability` = $role_attrs->weaponDurability WHERE `id` = $role_attrs->weaponRoleThingId;
SQL;

            }
            if ($role_attrs->clothesRoleThingId !== 0) {
                $sql .= <<<SQL
UPDATE `role_things` SET `durability` = $role_attrs->clothesDurability WHERE `id` = $role_attrs->clothesRoleThingId;
SQL;

            }
            if ($role_attrs->armorRoleThingId !== 0) {
                $sql .= <<<SQL
UPDATE `role_things` SET `durability` = $role_attrs->armorDurability WHERE `id` = $role_attrs->armorRoleThingId;
SQL;

            }
            if ($role_attrs->shoesRoleThingId !== 0) {
                $sql .= <<<SQL
UPDATE `role_things` SET `durability` = $role_attrs->shoesDurability WHERE `id` = $role_attrs->shoesRoleThingId;
SQL;

            }
            /**
             * 同步地图、气血、内力、潜能、精神、修为
             *
             */
            $sql .= <<<SQL
UPDATE `roles` SET `kills` = $role_row->kills,
                   `killed` = $role_row->killed,
                   `red` = $role_row->red,
                   `release_time` = $role_row->release_time,
                   `click_times` = $role_row->click_times,
                   `login_times` = $role_row->login_times,
                   `map_id` = $role_row->map_id,
                   `hp` = $role_attrs->hp,
                   `mp` = $role_attrs->mp,
                   `qianneng` = $role_attrs->qianneng,
                   `jingshen` = $role_attrs->jingshen,
                   `mission` = $mission,
                   `experience` = $role_attrs->experience WHERE `id` = $role_row->id;
SQL;

            Helpers::execSql($sql);
        }

        /**
         * 清理数据、属性
         *
         */
        if ($clear_row_attrs) {
            cache()->del('role_row_' . $role_row->id, 'role_attrs_' . $role_row->id);
        } else {
            cache()->del('role_row_' . $role_row->id);
        }
    }
}
