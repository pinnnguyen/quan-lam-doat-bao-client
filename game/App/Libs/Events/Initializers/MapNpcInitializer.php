<?php

namespace App\Libs\Events\Initializers;

use App\Libs\Attrs\NpcAttrs;
use App\Libs\Helpers;

/**
 * 初始化地图NPC放置
 */
class MapNpcInitializer
{
    public static function hook()
    {
        /**
         * 获取所有放置NPC
         */
        $sql = <<<SQL
SELECT * FROM `map_npcs`;
SQL;

        $map_npcs = Helpers::queryFetchAll($sql);

        $cache_map_npcs = [];
        $cache_map_npcs_attrs = [];
        foreach ($map_npcs as $map_npc) {
            for ($i = 1; $i <= $map_npc->number; $i++) {
                $key = 'map_npc_attrs_' . $map_npc->map_id . '_' . $map_npc->npc_id . '_' . $i;
                $cache_map_npcs[$map_npc->map_id][] = $key;
                $cache_map_npcs_attrs[$key] = self::getMapNpcAttrs($map_npc->map_id, $map_npc->npc_id, $i,
                    $map_npc->guard_north, $map_npc->guard_west, $map_npc->guard_east, $map_npc->guard_south);
            }
        }

        cache()->set('map_npcs', $cache_map_npcs);
        cache()->set('map_npcs_attrs', $cache_map_npcs_attrs);
    }


    /**
     * 计算NPC战斗属性
     *
     * @param int $map_id
     * @param int $npc_id
     * @param int $number
     * @param int $n
     * @param int $w
     * @param int $e
     * @param int $s
     *
     * @return object
     */
    public static function getMapNpcAttrs(int $map_id, int $npc_id, int $number, int $n, int $w, int $e, int $s): object
    {
        $npc_attrs = new NpcAttrs();
        $npc = Helpers::getNpcRowByNpcId($npc_id);

        $npc_attrs->name = $npc->name;

        $npc_attrs->sect_id = $npc->sect_id;

        $npc_attrs->experience = $npc->experience;

        $npc_attrs->guardNorth = $n;
        $npc_attrs->guardWest = $w;
        $npc_attrs->guardEast = $e;
        $npc_attrs->guardSouth = $s;

        $npc_attrs->mapId = $map_id;
        $npc_attrs->npcId = $npc_id;
        $npc_attrs->number = $number;

        $npc_attrs->maxHp += $npc->base_hp;

        $npc_attrs->maxHp += 10 * $npc->base_neigong_lv;
        $npc_attrs->mp += 10 * $npc->base_neigong_lv;

//        if ($npc->age < 50) {
        $npc_attrs->maxHp += $npc->age * 20;
        $npc_attrs->mp += $npc->age * 20;
//        } else {
//            $npc_attrs->maxHp += 50 * 20;
//            $npc_attrs->mp += 50 * 20;
//        }
//        if ($npc_attrs->experience < 40000000) {
        $npc_attrs->maxHp += intval($npc_attrs->experience / 40000);
        $npc_attrs->mp += intval($npc_attrs->experience / 40000);
//        } else {
//            $npc_attrs->maxHp += 40000000 / 40000;
//            $npc_attrs->mp += 40000000 / 40000;
//        }

        $npc_attrs->hp = $npc_attrs->maxHp;


        if ($npc->weapon > 0) {
            $weapon = Helpers::getThingRowByThingId($npc->weapon);
            $npc_attrs->weaponKind = $weapon->equipment_kind;
            $npc_attrs->weaponName = $weapon->name;
            $npc_attrs->attack += $weapon->attack;
            $npc_attrs->defence += $weapon->defence;
        }
        if ($npc->clothes > 0) {
            $clothes = Helpers::getThingRowByThingId($npc->clothes);
            $npc_attrs->defence += $clothes->defence;
        }
        if ($npc->armor > 0) {
            $armor = Helpers::getThingRowByThingId($npc->armor);
            $npc_attrs->defence += $armor->defence;
        }
        if ($npc->shoes > 0) {
            $shoes = Helpers::getThingRowByThingId($npc->shoes);
            $npc_attrs->dodge += $shoes->dodge;
            $npc_attrs->equipmentDodge = $shoes->dodge;
        }

        $npc_attrs->dodge += intval($npc->sect_qinggong_lv * 0.3 + $npc->base_qinggong_lv * 0.15);
        $npc_attrs->dodgeProbability = $npc_attrs->dodge;

        $npc_attrs->block += intval($npc->base_zhaojia_lv * 0.5 + $npc->sect_skill_lv);
        $npc_attrs->blockProbability = $npc_attrs->block * 50;

        $npc_attrs->comprehensiveQinggongLv = intval($npc->base_qinggong_lv * 0.5) + $npc->sect_qinggong_lv;

        $base_quanjiao_lv = $npc->base_quanjiao_lv;
        $base_daofa_lv = $npc->base_daofa_lv;
        $base_jianfa_lv = $npc->base_jianfa_lv;
        $base_qinggong_lv = $npc->base_qinggong_lv;
        $base_zhaojia_lv = $npc->base_zhaojia_lv;
        $sect_qinggong_lv = $npc->sect_qinggong_lv;
        $sect_zhaojia_lv = $npc->sect_skill_lv;
        $sect_quanjiao_lv = 0;
        $sect_daofa_lv = 0;
        $sect_jianfa_lv = 0;
        if ($npc->sect_skill > 0) {
            $skill = Helpers::getSkillRowBySkillId($npc->sect_skill);
            switch ($skill->kind) {
                case '拳脚':
                    $sect_quanjiao_lv = $npc->sect_skill_lv;
                    break;
                case '刀法':
                    $sect_daofa_lv = $npc->sect_skill_lv;
                    break;
                case '剑法':
                    $sect_jianfa_lv = $npc->sect_skill_lv;
                    break;
            }
        }
        $npc_attrs->comprehensiveSkillLv = max([
            intval($base_jianfa_lv * 0.5 + $sect_jianfa_lv),
            intval($base_quanjiao_lv * 0.5 + $sect_quanjiao_lv),
            intval($base_daofa_lv * 0.5 + $sect_daofa_lv),
            intval($base_zhaojia_lv * 0.5 + $sect_zhaojia_lv),
           intval( $base_qinggong_lv * 0.5 + $sect_qinggong_lv),
        ]);

        $npc_attrs->maxSkillLv = max([
            $npc->base_jianfa_lv,
            $npc->base_daofa_lv,
            $npc->base_quanjiao_lv,
            $npc->base_neigong_lv,
            $npc->base_qinggong_lv,
            $npc->base_zhaojia_lv,
            $npc->sect_qinggong_lv,
            $npc->sect_skill_lv,
        ]);

        if ($npc->sect_skill > 0) {
            $skill = Helpers::getSkillRowBySkillId($npc->sect_skill);
            $npc_attrs->skillId = $skill->id;
            if ($skill->kind === '剑法') {
                $npc_attrs->attack += intval($npc->sect_skill_lv * 1 + $npc->base_jianfa_lv * 0.5);
            } elseif ($skill->kind === '刀法') {
                $npc_attrs->attack += intval($npc->sect_skill_lv * 1 + $npc->base_daofa_lv * 0.5);
            } else {
                $npc_attrs->attack += intval($npc->sect_skill_lv * 1 + $npc->base_quanjiao_lv * 0.5);
            }

            $npc_attrs->skillTrickNumber = match (true) {
			    $npc->sect_skill_lv >= 1000 and $skill->lv1000_damage > 0 => 15,
			    $npc->sect_skill_lv >= 700 and $skill->lv700_damage > 0 => 14,
                $npc->sect_skill_lv >= 480 and $skill->lv480_damage > 0 => 13,
                $npc->sect_skill_lv >= 420 and $skill->lv420_damage > 0 => 12,
                $npc->sect_skill_lv >= 360 and $skill->lv360_damage > 0 => 11,
                $npc->sect_skill_lv >= 300 and $skill->lv300_damage > 0 => 10,
                $npc->sect_skill_lv >= 240 and $skill->lv240_damage > 0 => 9,
                $npc->sect_skill_lv >= 180 and $skill->lv180_damage > 0 => 8,
                $npc->sect_skill_lv >= 160 and $skill->lv160_damage > 0 => 7,
                $npc->sect_skill_lv >= 120 and $skill->lv120_damage     => 6,
                $npc->sect_skill_lv >= 80 and $skill->lv80_damage > 0   => 5,
                $npc->sect_skill_lv >= 40 and $skill->lv40_damage > 0   => 4,
                $npc->sect_skill_lv >= 20 and $skill->lv20_damage > 0   => 3,
                $npc->sect_skill_lv >= 10 and $skill->lv10_damage > 0   => 2,
                default                                                 => 1,
            };
        } else {
            $npc_attrs->attack += intval($npc->base_quanjiao_lv * 0.5);
        }

        /**
         * 非天龙门血量加两倍
         */
        if (!in_array($npc->id, [432, 433, 434, 435, 436, 437, 438, 439, 440, 441, 442, 443, 444,])) {
            $npc_attrs->maxHp *= 2;
            $npc_attrs->hp *= 2;
            $npc_attrs->mp *= 2;
        }

        return $npc_attrs;
    }
}
