<?php

namespace App\Libs\Events\Timers;

use App\Core\Configs\FlushConfig;
use App\Libs\Helpers;

class SyncRoleStatusTimer
{
    public static function hook()
    {
        $age_interval = FlushConfig::ROLE_SYNC;
        /**
         * 获取所有玩家 id
         *
         */
        $role_ids = cache()->mget(cache()->keys('role_id_*'));
        if (!is_array($role_ids) or count($role_ids) < 1) {
            return;
        }


        /**
         * 单进程循环同步所有玩家信息
         *
         */
        $sql = '';
        foreach ($role_ids as $role_id) {
            /**
             * 获取玩家 row 和 attrs
             *
             */
            $role_row = Helpers::getRoleRowByRoleId($role_id);
            $role_attrs = Helpers::getRoleAttrsByRoleId($role_id);
            if (empty($role_row) or empty($role_attrs)) {
                continue;
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
                   `age` = `age` + $age_interval,
                   `click_times` = $role_row->click_times,
                   `login_times` = $role_row->login_times,
                   `map_id` = $role_row->map_id,
                   `hp` = $role_attrs->hp,
                   `mp` = $role_attrs->mp,
                   `qianneng` = $role_attrs->qianneng,
                   `jingshen` = $role_attrs->jingshen,
                   `experience` = $role_attrs->experience WHERE `id` = $role_row->id;
SQL;

        }
        if ($sql != '') {

            Helpers::execSql($sql);
        }
    }
}
