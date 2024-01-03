<?php

namespace App\Libs\Events\Initializers;

/**
 * 初始化装备种类
 */
class EquipmentKindInitializer
{
    public static function hook()
    {
        /**
         * 获取所有装备种类
         */
        $sql = <<<SQL
SELECT * FROM `equipment_kinds`;
SQL;

        $equipment_kinds_st = db()->query($sql);
        $equipment_kinds = $equipment_kinds_st->fetchAll(\PDO::FETCH_ASSOC);
        $equipment_kinds_st->closeCursor();
        cache()->set('equipment_kinds', array_column($equipment_kinds, 'name', 'id'));
    }
}
