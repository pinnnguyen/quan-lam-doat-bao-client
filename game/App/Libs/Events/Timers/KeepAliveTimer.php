<?php

namespace App\Libs\Events\Timers;

/**
 * 保持活跃
 *
 */
class KeepAliveTimer
{
    public static function hook()
    {
        $_st = db()->prepare("SELECT `id` FROM `settings` WHERE `id` = 1;");
        $_st->execute();
        $_st->closeCursor();
        cache()->ping();
    }
}
