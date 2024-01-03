<?php

namespace App\Libs\Events\Initializers;

/**
 * 初始化生命心法
 */
class XinfaHpTrickInitializer
{
    public static function hook()
    {
        /**
         * 获取所有生命心法
         */
        $sql = <<<SQL
SELECT * FROM `xinfa_hp_tricks` INNER JOIN `xinfas` ON `xinfa_hp_tricks`.`xinfa_id` = `xinfas`.`id`;
SQL;

        $xinfa_hp_tricks_st = db()->query($sql);
        $xinfa_hp_tricks = $xinfa_hp_tricks_st->fetchAll(\PDO::FETCH_ASSOC);
        $xinfa_hp_tricks_st->closeCursor();

        cache()->set('xinfa_hp_tricks', array_column($xinfa_hp_tricks, null, 'xinfa_id'));
    }
}
