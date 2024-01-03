<?php

namespace App\Libs\Attrs;

/***
 * 连续任务属性
 */
class ConsecutiveMission
{
    // 过期时间戳
    public int $expireTimestamp = 0;

    // 总数
    public int $times = 0;

    // 档数 推导出任务类型
    public int $circle = 0;

    // 目标地点 NPC所在地、打探消息地
    public int $mapId = 0;

    // 目标地点 NPC所在地、打探消息地的地区
    public string $regionName = '';

    // 目标人物 对话人物、击杀人物
    public int $npcId = 0;

    // 宝石类型 1=玛瑙、2=翡翠、3=人参、4=玉佩
    public int $gemKind = 0;

    // 宝石目标数量
    public int $gemNumber = 0;

    // 已经获得宝石数量
    public int $gemGainNumber = 0;

    // 目标装备ID
    public int $equipmentThingId = 0;

    // 任务状态 打怪、打探消息、对话
    public bool $status = false;

    // 是否已经验证
    public bool $verified = true;
}
