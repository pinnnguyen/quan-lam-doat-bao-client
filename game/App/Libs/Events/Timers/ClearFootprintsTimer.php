<?php

namespace App\Libs\Events\Timers;

/**
 * 清理地图足迹
 *
 */
class ClearFootprintsTimer
{
    public static function hook()
    {
        $map_footprints = cache()->keys('map_footprints_*');
        if ($map_footprints) cache()->del(...$map_footprints);
    }
}
