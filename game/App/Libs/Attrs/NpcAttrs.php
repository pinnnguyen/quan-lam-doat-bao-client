<?php

namespace App\Libs\Attrs;

/***
 * NPC 属性
 */
class NpcAttrs
{
    // 是否战斗中
    public bool $isFighting = false;

    // 是否
    public bool $isFought = false;

    // NPC名称
    public ?string $name = null;

    // 门派Id
    public int $sect_id = 0;

    // 修为
    public int $experience = 0;

    // 所属房间Id
    public int $mapId = 0;

    // 刷新NPCId
    public int $npcId = 0;

    // 刷新数量
    public int $number = 0;

    // 气血
    public int $hp = 0;

    // 气血上限
    public int $maxHp = 0;

    // 内力
    public int $mp = 0;

    // 攻击
    public int $attack = 0;

    // 防御
    public int $defence = 0;

    // 躲避
    public int $dodge = 0;

    // 格挡
    public int $block = 0;

    // 装备躲避
    public int $equipmentDodge = 0;

    // 综合技能等级
    public int $comprehensiveSkillLv = 0;

    // 最高技能等级
    public int $maxSkillLv = 0;

    // 综合轻功等级
    public int $comprehensiveQinggongLv = 0;

    // 躲避概率
    public int $dodgeProbability = 0; // 1000

    // 格挡概率
    public int $blockProbability = 0; // 100000

    // 武器名称, 用于展示
    public ?string $weaponName = null;

    // 武器类型, 用于可使用招式等...
    public int $weaponKind = 0;

    // 技能招式数量
    public int $skillTrickNumber = 0;

    // 下一次使用技能
    public int $skillId = 0;

    // 是否守护地图北方
    public int $guardNorth = 0;

    // 是否守护地图东方
    public int $guardEast = 0;

    // 是否守护地图南方
    public int $guardSouth = 0;

    // 是否守护地图西方
    public int $guardWest = 0;
}
