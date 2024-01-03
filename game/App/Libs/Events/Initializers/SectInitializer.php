<?php

namespace App\Libs\Events\Initializers;

/**
 * 初始化门派
 */
class SectInitializer
{
    public static function hook()
    {
        /**
         * 获取所有门派
         */
        $sql = <<<SQL
SELECT * FROM `sects`;
SQL;

        $sects_st = db()->query($sql);
        $sects = $sects_st->fetchAll(\PDO::FETCH_ASSOC);
        $sects_st->closeCursor();

        cache()->set('sects', array_column($sects, 'name', 'id'));
    }
}
