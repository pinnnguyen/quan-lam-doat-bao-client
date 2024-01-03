<?php

namespace App\Libs\Events\Timers;

use App\Libs\Attrs\RoleAttrs;
use App\Libs\Helpers;

/**
 * 复活
 *
 */
class ReviveTimer
{
    public static function hook(int $role_id)
    {
        $role_attrs = Helpers::getRoleAttrsByRoleId($role_id);
        if ($role_attrs instanceof RoleAttrs) {
            $role_attrs->hp = $role_attrs->maxHp;
            $role_attrs->mp = $role_attrs->mp > $role_attrs->maxMp ? $role_attrs->mp : $role_attrs->maxMp;
            $role_attrs->reviveTimestamp = 0;
            Helpers::setRoleAttrsByRoleId($role_id, $role_attrs);
        }
    }
}
