<?php

namespace App\Libs\Events\Initializers;

use App\Libs\Events\Timers\RefreshMapNpcsTimer;
use App\Libs\Events\Timers\RegenerateTopTimer;

/**
 * Timer 进程初始化
 *
 */
class TimerProcessInitializer
{
    public static function hook()
    {
        \App\Libs\Events\Initializers\ProcessInitializer::hook();
        RegenerateTopTimer::hook();
        RefreshMapNpcsTimer::hook();
    }
}
