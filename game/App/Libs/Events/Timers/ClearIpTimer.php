<?php

namespace App\Libs\Events\Timers;

/**
 * 清理 IP
 *
 */
class ClearIpTimer
{
    public static function hook()
    {
        $ips = cache()->keys('ip_*');
        if ($ips) cache()->del(...$ips);
    }
}
