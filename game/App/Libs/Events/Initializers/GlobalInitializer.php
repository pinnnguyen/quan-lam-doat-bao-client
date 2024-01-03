<?php

namespace App\Libs\Events\Initializers;

use App\Libs\Events\Timers\RefreshMapNpcsTimer;
use App\Libs\Events\Timers\RegenerateTopTimer;

/**
 * 全局初始化器
 */
class GlobalInitializer
{
    public static function hook()
    {
        $requests = intval(cache()->get('requests'));
        cache()->flushDB();
        cache()->incrBy('requests', $requests);
        ConsecutiveMissionInitializer::hook();
        DeliverLetterInitializer::hook();
        EquipmentKindInitializer::hook();
        MapInitializer::hook();
        NpcInitializer::hook();
        NpcRankInitializer::hook();
        NpcRankThingInitializer::hook();
        RegionInitializer::hook();
        SectInitializer::hook();
        SettingInitializer::hook();
        ShopInitializer::hook();
        SkillInitializer::hook();
        ThingInitializer::hook();
        XinfaAttackTrickInitializer::hook();
        XinfaHpTrickInitializer::hook();
        XinfaInitializer::hook();
        XinfaMpTrickInitializer::hook();
        MapNpcInitializer::hook();
        RegenerateTopTimer::hook();
        RefreshMapNpcsTimer::hook();
    }
}
