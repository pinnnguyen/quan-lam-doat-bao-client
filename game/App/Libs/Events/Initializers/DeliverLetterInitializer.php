<?php

namespace App\Libs\Events\Initializers;

/**
 * 送信地址初始化
 */
class DeliverLetterInitializer
{
    public static function hook()
    {
        $sql = <<<SQL
SELECT `npc_id`, `map_id` FROM `map_npcs` INNER JOIN `maps` ON `map_npcs`.`map_id` = `maps`.`id` AND `maps`.`region_id`
IN (11, 10, 19, 2, 1, 28, 24, 12, 17, 16, 7);
SQL;

        $deliver_letter_targets_st = db()->query($sql);
        $deliver_letter_targets = $deliver_letter_targets_st->fetchAll(\PDO::FETCH_ASSOC);
        $deliver_letter_targets_st->closeCursor();
        $deliver_letter_targets = array_column($deliver_letter_targets, null, 'npc_id');
        cache()->set('deliver_letter_targets', $deliver_letter_targets);
    }
}
