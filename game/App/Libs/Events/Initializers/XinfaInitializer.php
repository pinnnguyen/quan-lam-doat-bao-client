<?php

namespace App\Libs\Events\Initializers;

use App\Libs\Objects\XinfaRow;
use PDO;

/**
 * 初始化心法
 */
class XinfaInitializer
{
    public static function hook()
    {
        /**
         * 获取所有心法
         */
        $sql = <<<SQL
SELECT * FROM `xinfas`;
SQL;

        $xinfas_st = db()->query($sql);
        $xinfas = $xinfas_st->fetchAll(PDO::FETCH_CLASS, XinfaRow::class);
        $xinfas_st->closeCursor();
        /**
         * 缓存所有心法
         */
        cache()->set('xinfas', array_column($xinfas, null, 'id'));
    }
}
