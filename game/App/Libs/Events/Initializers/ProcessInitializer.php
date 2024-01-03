<?php

namespace App\Libs\Events\Initializers;

use App\Core\Configs\ServerConfig;

/**
 * 进程初始化器
 */
class ProcessInitializer
{
    public static function hook()
    {
        ServerConfig::$startMicroTime = microtime(true);
        TimeZoneInitializer::hook();
        DbConnectionInitializer::hook();

        // $base_dir = dirname(__DIR__, 4);
        // self::compress($base_dir . '/App/Http/Views', $base_dir . '/App/Runtime/Views/Compress');
    }


    static function compress($from_dir, $to_dir): void
    {
        if (!is_dir($to_dir)) mkdir($to_dir);
        $items = scandir($from_dir);
        unset($items[0], $items[1]);
        foreach ($items as $item) {
            if (is_dir($from_dir . '/' . $item)) {
                self::compress($from_dir . '/' . $item, $to_dir . '/' . $item);
                continue;
            }
            file_put_contents($to_dir . '/' . $item, preg_replace(['/\s+/', '/\s+<br\/>\s+/'], [' ', '<br/>'], file_get_contents($from_dir . '/' . $item)));
        }
    }
}
