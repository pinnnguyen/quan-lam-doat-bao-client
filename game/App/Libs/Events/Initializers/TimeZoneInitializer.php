<?php

namespace App\Libs\Events\Initializers;

use App\Core\Configs\ServerConfig;

/**
 * 初始化时区
 */
class TimeZoneInitializer
{
    public static function hook()
    {
        date_default_timezone_set(ServerConfig::TIME_ZONE);
    }
}
