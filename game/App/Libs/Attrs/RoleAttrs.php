<?php

namespace App\Libs\Attrs;

/**
 * 角色属性
 *
 */
class RoleAttrs
{

    // 是否战斗中
    public bool $isFighting = false;

    // 复活时间戳(死亡时间戳+40秒)
    public int $reviveTimestamp = 0;

    // 是否昏迷中
    public bool $isComa = false;

    // 昏迷时间戳
    public int $comaTimestamp = 0;

    // 背包重量
    public int $weight = 0;

    // 年龄
    public int $age = 0;

    // 开始钓鱼时间戳
    public int $startFishingTimestamp = 0;

    // 开始坐车时间戳
    public int $startCarriageTimestamp = 0;

    // 开始睡觉时间戳
    public int $startSleepTimestamp = 0;

    // 气血
    public int $hp = 0;

    // 气血上限
    public int $maxHp = 0;

    // 内力
    public int $mp = 0;

    // 内力上限
    public int $maxMp = 0;

    // 攻击力
    public int $attack = 0;

    // 防御力
    public int $defence = 0;

    // 躲避
    public int $dodge = 0;

    // 格挡
    public int $block = 0;

    // 精神
    public int $jingshen = 0;

    // 精神上限
    public int $maxJingshen = 0;

    // 潜能
    public int $qianneng = 0;

    // 潜能上限
    public int $maxQianneng = 0;

    // 修为
    public int $experience = 0;

    // 躲避概率
    public int $dodgeProbability = 0; // 1000

    // 格挡概率
    public int $blockProbability = 0; // 100000

    /**
     * 原生属性
     *
     * @var int
     */
    public int $rowBaseJingshen = 0;

    /**
     * 装备 属性
     *
     * @var int
     */
    // 装备攻击力
    public int $equipmentAttack = 0;

    // 装备防御
    public int $equipmentDefence = 0;

    // 装备躲避
    public int $equipmentDodge = 0;

    // 当前装备武器类型
    public int $weaponKind = 0;

    // 当前装备武器名称
    public ?string $weaponName = null;

    // 当前装备武器类型Id
    public int $weaponRoleThingId = 0;
    public int $weaponThingId = 0;
    public int $weaponStatus = 0;
    public int $weaponDurability = 0;

    public ?string $clothesName = null;
    public int $clothesRoleThingId = 0;
    public int $clothesThingId = 0;
    public int $clothesStatus = 0;
    public int $clothesDurability = 0;

    public ?string $armorName = null;
    public int $armorRoleThingId = 0;
    public int $armorThingId = 0;
    public int $armorStatus = 0;
    public int $armorDurability = 0;

    public ?string $shoesName = null;
    public int $shoesRoleThingId = 0;
    public int $shoesThingId = 0;
    public int $shoesStatus = 0;
    public int $shoesDurability = 0;

    /**
     * 技能属性
     *
     * @var int
     */
    public int $sectSkillId = 0;
    public int $sectSkillLv = 0;
    public int $baseSkillLv = 0;
    public int $skillAttack = 0;
    public int $maxSkillLv = 0;
    public int $comprehensiveSkillLv = 0;
    public int $comprehensiveQinggongLv = 0;
    public int $maxSectSkillLv = 0;
    public int $skillHp = 0;
    public int $skillMp = 0;
    public int $skillDodge = 0;
    public int $skillBlock = 0;

    /**
     * 心法属性
     *
     * @var int
     */
    public int $xinfaHp = 0;
    public int $xinfaMp = 0;
    public int $attackXinfaId = 0;
    public int $attackXinfaLv = 0;
    public float $attackXinfaBaseDamage = 0;
    public int $xinfaExtraDamage = 0;


    public int $double_xinfa;
    public int $triple_xinfa;

    public int $renshen;

//    public int $weight = 0;
}
