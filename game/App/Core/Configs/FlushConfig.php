<?php

namespace App\Core\Configs;

/**
 * Làm mới配置
 */
class FlushConfig
{
    /**
     * 缓存过期时间 单位 秒 固定数据不受限制，如：地图、物品、技能、人物等
     */
    const EXPIRE = 600;

    /**
     * NPCLàm mới间隔 单位 秒
     */
    const NPC_FLUSH = 120;

    /**
     * 地图物品Làm mới间隔 单位 秒
     */
    const MAP_THING = 300;

    /**
     * 游戏角色过期时间 单位 秒
     */
    const ROLE = 600;

    /**
     * 游戏角色信息同步时间 单位 秒 此时间一定要小于 游戏角色过期时间
     */
    const ROLE_SYNC = 60;

    /**
     * 心法经验同步时间 单位 秒
     */
    const XINFA_SYNC = 5;

    /**
     * 战斗每回合时间 单位 秒
     */
    const BATTLE = 3;

    /**
     * 数据库活跃间隔 单位 秒
     */
    const DB_KEEP_ALIVE = 60;

    /**
     * 缓存活跃间隔 单位 秒
     */
    const CACHE_KEEP_ALIVE = 60;

    /**
     * 地图足迹显示时间 单位 秒
     */
    const MAP_FOOTPRINT = 15;

    /**
     * 玩家离线Làm mới间隔 单位 秒
     */
    const ROLE_OFFLINE = 120;

    /**
     * 清理尸体间隔 单位 秒
     */
    const DELETE_BODY = 300;

    /**
     * 排行榜Làm mới间隔 单位 秒
     */
    const TOP = 600;

    /**
     * IP 监控间隔 单位 秒
     */
    const IP_MONITOR = 300;

    /**
     * 保持连接 单位 秒
     */
    const KEEP_ALIVE = 300;

    /**
     * 复活时间 单位 秒
     */
    const REVIVE = 40;
}
