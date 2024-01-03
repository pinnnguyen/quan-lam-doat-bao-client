<?php

namespace App\Libs\Events\Timers;

use App\Libs\Helpers;

/**
 * 清除过期玩家
 *
 */
class ClearExpireMapRoleLogTimer
{
    public static function hook()
    {
        /**
         * 获取所有地图
         *
         */
        if (!is_array(Helpers::$maps)) {
            return;
        }
        foreach (Helpers::$maps as $map) {
            $roles_id = cache()->sMembers('map_roles_' . $map->id);
            if (!is_array($roles_id) or empty($roles_id)) {
                return;
            }
            $roles = cache()->mget(array_map(function ($role_id) {
                return 'role_row_' . $role_id;
            }, $roles_id));
            if (!is_array($roles)) {
                cache()->del('map_roles_' . $map->id);
            }
            foreach ($roles as $key => $role) {
                if (empty($role) or !in_array($role->id, $roles_id)) {
                    cache()->sRem('map_roles_' . $map->id, $roles_id[$key]);
                }
            }
        }
    }
}
