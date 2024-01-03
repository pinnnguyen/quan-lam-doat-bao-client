<?php

namespace App\Libs\Events\Initializers;

class ShopInitializer
{
    public static function hook()
    {
        /**
         * 获取所有商店
         */
        $sql = <<<SQL
SELECT `id` FROM `shops`;
SQL;

        $shops_st = db()->query($sql);
        $shops = $shops_st->fetchAll(\PDO::FETCH_ASSOC);
        $shops_st->closeCursor();
        $shops = array_column($shops, 'id', 'id');

        /**
         * 获取所有商店售卖物品
         */
        $sql = <<<SQL
SELECT `shop_id`, `thing_id` FROM `shop_things` INNER JOIN `things` ON `thing_id` = `things`.`id` ORDER BY `kind`, `money`;
SQL;

        $shop_things_st = db()->query($sql);
        $shop_things = $shop_things_st->fetchAll(\PDO::FETCH_ASSOC);
        $shop_things_st->closeCursor();

        $cache_shops = [];
        foreach ($shop_things as $shop_thing) {
            if (in_array($shop_thing['shop_id'], $shops)) {
                $cache_shops[$shop_thing['shop_id']][] = $shop_thing['thing_id'];
            }
        }

        cache()->set('shops', $cache_shops);

    }
}
