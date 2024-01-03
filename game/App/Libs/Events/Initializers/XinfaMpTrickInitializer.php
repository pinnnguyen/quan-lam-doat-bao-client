<?php

namespace App\Libs\Events\Initializers;

/**
 * 初始化内功心法
 */
class XinfaMpTrickInitializer
{
    public static function hook()
    {
        /**
         * 获取所有内功心法
         */
        $sql = <<<SQL
SELECT * FROM `xinfa_mp_tricks` INNER JOIN `xinfas` ON `xinfa_mp_tricks`.`xinfa_id` = `xinfas`.`id`;
SQL;

        $xinfa_mp_tricks_st = db()->query($sql);
        $xinfa_mp_tricks = $xinfa_mp_tricks_st->fetchAll(\PDO::FETCH_ASSOC);
        $xinfa_mp_tricks_st->closeCursor();

        cache()->set('xinfa_mp_tricks', array_column($xinfa_mp_tricks, null, 'xinfa_id'));
    }
}
