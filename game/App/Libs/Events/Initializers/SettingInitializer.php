<?php

namespace App\Libs\Events\Initializers;

/**
 * 游戏设置初始化
 */
class SettingInitializer
{
    public static function hook()
    {
        /**
         * 获取所有配置
         */
        $sql = <<<SQL
SELECT `item`, `value` FROM `settings`;
SQL;

        $settings_st = db()->query($sql);
        $settings = $settings_st->fetchAll(\PDO::FETCH_ASSOC);
        $settings_st->closeCursor();
        $settings = array_column($settings, 'value', 'item');

        cache()->set('settings', $settings);
    }
}
