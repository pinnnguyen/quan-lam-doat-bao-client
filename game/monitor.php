<?php


use App\Core\Configs\FlushConfig;
use App\Core\Configs\ServerConfig;
use App\Libs\Events\Timers\BattlefieldTimer;
use Workerman\Timer;
use Workerman\Worker;


require_once __DIR__.'/vendor/autoload.php';

$worker = new Worker();

$worker::$logFile = ServerConfig::LOG_FILE;
$worker::$stdoutFile = ServerConfig::STDOUT_FILE;

$worker -> onWorkerStart = function(Worker $worker){
    echo "启动Monitor\r\n";
    $worker -> name = 'Timer_'.($worker -> id + 1);
    // 进程初始化
    \App\Libs\Events\Initializers\ProcessInitializer::hook();
    // 初始化排行榜
    \App\Libs\Events\Timers\RegenerateTopTimer::hook();
    // 战斗定时器
    Timer::add(FlushConfig::BATTLE, [BattlefieldTimer::class, 'hook']);
    // NPC刷新定时器
    Timer::add(FlushConfig::NPC_FLUSH, [\App\Libs\Events\Timers\RefreshMapNpcsTimer::class, 'hook']);
    // 地图物品刷新定时器
    Timer::add(FlushConfig::MAP_THING, [\App\Libs\Events\Timers\RefreshMapThingsTimer::class, 'hook']);
    // 游戏角色信息同步定时器
    Timer::add(FlushConfig::ROLE_SYNC, [\App\Libs\Events\Timers\SyncRoleStatusTimer::class, 'hook']);
    // 心法经验同步定时器
    Timer::add(FlushConfig::XINFA_SYNC, [\App\Libs\Events\Timers\GiveXinfaExperienceTimer::class, 'hook']);
    // 地图足迹刷新定时器
    Timer::add(FlushConfig::MAP_FOOTPRINT, [\App\Libs\Events\Timers\ClearFootprintsTimer::class, 'hook']);
    // 玩家离线刷新定时器
    Timer::add(FlushConfig::ROLE_OFFLINE, [\App\Libs\Events\Timers\ClearOfflineRoleTimer::class, 'hook']);
    // 清理尸体定时器
    Timer::add(FlushConfig::DELETE_BODY, [\App\Libs\Events\Timers\ClearPersonalEffectsTimer::class, 'hook']);
    // 排行榜刷新定时器
    Timer::add(FlushConfig::TOP, [\App\Libs\Events\Timers\RegenerateTopTimer::class, 'hook']);
    // 清除过期玩家定时器
    Timer::add(FlushConfig::ROLE_SYNC, [\App\Libs\Events\Timers\ClearExpireMapRoleLogTimer::class, 'hook']);
    // IP监控定时器
    Timer::add(FlushConfig::IP_MONITOR, [\App\Libs\Events\Timers\ClearIpTimer::class, 'hook']);
};

$worker::runAll();
