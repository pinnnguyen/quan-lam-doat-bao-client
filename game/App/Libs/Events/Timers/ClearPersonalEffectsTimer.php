<?php

namespace App\Libs\Events\Timers;

use App\Libs\Helpers;

/**
 * 清理个人过期随身物品
 * 尸体、昏迷的人、书信
 *
 */
class ClearPersonalEffectsTimer
{
    public static function hook()
    {
        /**
         * 尸体
         *
         */
        $sql = <<<SQL
SELECT `id`, `body_expire` FROM `role_things` WHERE `is_body` = 1;
SQL;

        $bodies = Helpers::queryFetchAll($sql);
        if (is_array($bodies) and count($bodies) > 0) {
            $sql = '';
            foreach ($bodies as $body) {
                if ($body->body_expire < time()) {
                    $sql .= <<<SQL
DELETE FROM `role_things` WHERE `id` = $body->id;
SQL;

                }
            }
            if ($sql != '') {

                Helpers::execSql($sql);

            }
        }

        /**
         * 信件
         *
         */
        $sql = <<<SQL
SELECT `id`, `role_id` FROM `role_things` WHERE `is_letter` = 1;
SQL;

        $letters = Helpers::queryFetchAll($sql);
        if (is_array($letters) and count($letters) > 0) {
            $sql = '';
            foreach ($letters as $letter) {
                /**
                 * 获取在线玩家
                 */
                $role_row = Helpers::getRoleRowByRoleId($letter->role_id);
                if ($role_row) {
                    /**
                     * 查询是否离线
                     */
                    $role_id = cache()->get('role_id_' . $role_row->sid);
                    if (!$role_id) {
                        $sql .= <<<SQL
DELETE FROM `role_things` WHERE `id` = $letter->id;
SQL;

                    }
                } else {
                    $sql .= <<<SQL
DELETE FROM `role_things` WHERE `id` = $letter->id;
SQL;

                }
            }
            if ($sql != '') {

                Helpers::execSql($sql);

            }
        }
    }
}
