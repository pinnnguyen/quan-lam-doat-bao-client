<?php

namespace App\Libs\Events\Initializers;

use App\Libs\Helpers;

/**
 * 初始化攻击心法招式
 */
class XinfaAttackTrickInitializer
{
    public static function hook()
    {
        /**
         * 获取所有心法招式
         */
        $sql = <<<SQL
SELECT * FROM `xinfa_attack_tricks`;
SQL;

        $xinfa_attack_tricks = Helpers::queryFetchAll($sql);

        /**
         * 缓存所有心法招式
         */
        cache()->set('xinfa_attack_tricks', array_column($xinfa_attack_tricks, null, 'xinfa_id'));
    }
}
