<?php

namespace App\Libs\Attrs;

use App\Libs\Helpers;
use PDO;

/**
 * 更新角色属性
 *
 */
class FlushRoleAttrs{
    /**
     * 通过角色 id 刷新角色来自角色原生数据的属性
     *
     * @param int $role_id
     */
    public static function fromRoleRowByRoleId(int $role_id){
        $role_row = Helpers ::getRoleRowByRoleId($role_id);
        $role_attrs = Helpers ::getRoleAttrsByRoleId($role_id);
        if(empty($role_attrs)){
            $role_attrs = new RoleAttrs();
            $role_attrs -> hp = $role_row -> hp;
            $role_attrs -> mp = $role_row -> mp;
            $role_attrs -> qianneng = $role_row -> qianneng;
            $role_attrs -> jingshen = $role_row -> jingshen;
            $role_attrs -> experience = $role_row -> experience;

            $role_attrs -> double_xinfa = $role_row -> double_xinfa;
            $role_attrs -> triple_xinfa = $role_row -> triple_xinfa;
            $role_attrs -> renshen = $role_row -> renshen;

            $role_attrs -> age = $role_row -> age;
        }
        $role_attrs -> rowBaseJingshen = $role_row -> base_jingshen;

        /**
         * 更新角色属性
         *
         */
        self ::Update($role_attrs, $role_id);
    }

    /**
     * 更新角色属性
     *
     * @param RoleAttrs $role_attrs
     * @param int $role_id
     */
    public static function Update(RoleAttrs $role_attrs, int $role_id){
        $role_attrs -> maxJingshen = $role_attrs -> experience / 1000 * 0.25 + 1000 + $role_attrs -> rowBaseJingshen;
        $role_attrs -> maxQianneng = $role_attrs -> experience / 1000 * 2.5 + 1000;

        $role_attrs -> attack = $role_attrs -> equipmentAttack + $role_attrs -> skillAttack;
        $role_attrs -> defence = $role_attrs -> equipmentDefence;
        $role_attrs -> dodge = $role_attrs -> equipmentDodge + $role_attrs -> skillDodge;
        $role_attrs -> block = $role_attrs -> skillBlock;

        if($role_attrs -> age < 72 * 3600 * 50){
            $role_attrs -> maxHp = $role_attrs -> age / 72 / 3600 * 20;
            $role_attrs -> maxMp = $role_attrs -> age / 72 / 3600 * 20;
        }else{
            $role_attrs -> maxHp = 50 * 20;
            $role_attrs -> maxMp = 50 * 20;
        }
        if($role_attrs -> experience < 40000000){
            $role_attrs -> maxHp += $role_attrs -> experience / 40000;
            $role_attrs -> maxMp += $role_attrs -> experience / 40000;
        }else{
            $role_attrs -> maxHp += 40000000 / 40000;
            $role_attrs -> maxMp += 40000000 / 40000;
        }
        $role_attrs -> maxHp += $role_attrs -> skillHp + $role_attrs -> xinfaHp;
        $role_attrs -> maxMp += $role_attrs -> skillMp + $role_attrs -> xinfaMp;

        $role_attrs -> dodgeProbability = $role_attrs -> dodge;
        $role_attrs -> blockProbability = $role_attrs -> block * 50;

        Helpers ::setRoleAttrsByRoleId($role_id, $role_attrs);
    }

    /**
     * 通过角色 id 刷新角色来自角色装备的属性
     *
     */
    public static function fromRoleEquipmentByRoleId(int $role_id){
        /**
         * 获取已穿戴装备数据
         *
         */
        $sql = <<<SQL
SELECT `id`, `thing_id`, `status`, `durability` FROM `role_things` WHERE `role_id` = $role_id AND `equipped` = 1;
SQL;

        $role_things = Helpers ::queryFetchAll($sql);
        /**
         * 获取角色属性
         *
         */
        $role_attrs = Helpers ::getRoleAttrsByRoleId($role_id);

        /**
         * 初始化角色属性
         *
         */
        $role_attrs -> equipmentAttack = 0;
        $role_attrs -> equipmentDefence = 0;
        $role_attrs -> equipmentDodge = 0;

        $role_attrs -> weaponKind = 0;
        $role_attrs -> weaponName = null;
        $role_attrs -> weaponRoleThingId = 0;
        $role_attrs -> weaponThingId = 0;
        $role_attrs -> weaponDurability = 0;
        $role_attrs -> weaponStatus = 0;

        $role_attrs -> clothesName = null;
        $role_attrs -> clothesRoleThingId = 0;
        $role_attrs -> clothesThingId = 0;
        $role_attrs -> clothesDurability = 0;
        $role_attrs -> clothesStatus = 0;

        $role_attrs -> armorName = null;
        $role_attrs -> armorRoleThingId = 0;
        $role_attrs -> armorThingId = 0;
        $role_attrs -> armorDurability = 0;
        $role_attrs -> armorStatus = 0;

        $role_attrs -> shoesName = null;
        $role_attrs -> shoesRoleThingId = 0;
        $role_attrs -> shoesThingId = 0;
        $role_attrs -> shoesDurability = 0;
        $role_attrs -> shoesStatus = 0;

        foreach($role_things as $role_thing){
            $equipment = Helpers ::getThingRowByThingId($role_thing -> thing_id);
            if(!empty($equipment -> equipment_kind) && Helpers ::getEquipmentKindNameByEquipmentKindId($equipment -> equipment_kind) === '衣服'){
                if($role_thing -> durability > 0){
                    $role_attrs -> equipmentDefence += $equipment -> defence;
                }
                $role_attrs -> clothesName = $equipment -> name;
                $role_attrs -> clothesRoleThingId = $role_thing -> id;
                $role_attrs -> clothesThingId = $role_thing -> thing_id;
                $role_attrs -> clothesDurability = $role_thing -> durability;
                $role_attrs -> clothesStatus = $role_thing -> status;
                break;
            }
        }

        foreach($role_things as $role_thing){
            $equipment = Helpers ::getThingRowByThingId($role_thing -> thing_id);
            if(!empty($equipment -> equipment_kind) && Helpers ::getEquipmentKindNameByEquipmentKindId($equipment -> equipment_kind) === '内甲'){
                if($role_thing -> durability > 0){
                    $role_attrs -> equipmentDefence += $equipment -> defence;
                }
                $role_attrs -> armorName = $equipment -> name;
                $role_attrs -> armorRoleThingId = $role_thing -> id;
                $role_attrs -> armorThingId = $role_thing -> thing_id;
                $role_attrs -> armorDurability = $role_thing -> durability;
                $role_attrs -> armorStatus = $role_thing -> status;
                break;
            }
        }

        foreach($role_things as $role_thing){
            $equipment = Helpers ::getThingRowByThingId($role_thing -> thing_id);
            if(!empty($equipment -> equipment_kind) && Helpers ::getEquipmentKindNameByEquipmentKindId($equipment -> equipment_kind) === '鞋子'){
                if($role_thing -> durability > 0){
                    $role_attrs -> equipmentDodge += $equipment -> dodge;
                }
                $role_attrs -> shoesName = $equipment -> name;
                $role_attrs -> shoesRoleThingId = $role_thing -> id;
                $role_attrs -> shoesThingId = $role_thing -> thing_id;
                $role_attrs -> shoesDurability = $role_thing -> durability;
                $role_attrs -> shoesStatus = $role_thing -> status;
                break;
            }
        }

        foreach($role_things as $role_thing){
            $equipment = Helpers ::getThingRowByThingId($role_thing -> thing_id);
            if(!empty($equipment -> equipment_kind)){
                $e = Helpers ::getEquipmentKindNameByEquipmentKindId($equipment -> equipment_kind);
                if($e === '剑' || $e === '刀' || $e === '爪'){
                    if($role_thing -> durability > 0){
                        $role_attrs -> equipmentAttack += $equipment -> attack;
                        $role_attrs -> equipmentDefence += $equipment -> defence;
                    }
                    $role_attrs -> weaponKind = $equipment -> equipment_kind;
                    $role_attrs -> weaponName = $equipment -> name;
                    $role_attrs -> weaponRoleThingId = $role_thing -> id;
                    $role_attrs -> weaponThingId = $role_thing -> thing_id;
                    $role_attrs -> weaponDurability = $role_thing -> durability;
                    $role_attrs -> weaponStatus = $role_thing -> status;
                    break;
                }
            }

        }


        /**
         * 更新角色属性
         *
         */
        self ::Update($role_attrs, $role_id);
    }

    /**
     * 通过角色 id 刷新角色来自角色技能的属性
     *
     */
    public static function fromRoleSkillByRoleId(int $role_id){
        /**
         * 获取所有基本技能
         *
         */
        $sql = <<<SQL
SELECT `id`, `skill_id`, `set_role_skill_id`, `lv` FROM `role_skills` WHERE `role_id` = $role_id;
SQL;

        $role_skills_st = db() -> query($sql);
        $role_skills = $role_skills_st -> fetchAll(PDO::FETCH_ASSOC);
        $role_skills_st -> closeCursor();
        $role_skills = array_column($role_skills, null, 'id');

        /**
         * 获取玩家属性
         *
         */
        $role_attrs = Helpers ::getRoleAttrsByRoleId($role_id);

        /**
         * 初始化玩家属性
         *
         */
        $role_attrs -> maxSkillLv = max(array_column($role_skills, 'lv'));

        $base_quanjiao_lv = 0;
        $base_daofa_lv = 0;
        $base_jianfa_lv = 0;
        $base_qinggong_lv = 0;
        $base_zhaojia_lv = 0;
        $sect_qinggong_lv = 0;
        $sect_zhaojia_lv = 0;
        $sect_quanjiao_lv = 0;
        $sect_daofa_lv = 0;
        $sect_jianfa_lv = 0;
        $role_attrs -> sectSkillId = 0;
        $role_attrs -> sectSkillLv = 0;
        $role_attrs -> skillAttack = 0;
        $role_attrs -> baseSkillLv = 0;
        $role_attrs -> comprehensiveSkillLv = 0;
        $role_attrs -> comprehensiveQinggongLv = 0;
        $role_attrs -> skillHp = 0;
        $role_attrs -> skillMp = 0;
        $role_attrs -> skillDodge = 0;
        $role_attrs -> skillBlock = 0;

        foreach($role_skills as $role_skill){
            $skill = Helpers ::getSkillRowBySkillId($role_skill['skill_id']);
            if(!empty($skill -> is_base)){
                switch($skill -> kind){
                    case '拳脚':
                        $base_quanjiao_lv = $role_skill['lv'];
                        if($role_skill['set_role_skill_id'] > 0){
                            $sect_quanjiao_lv = $role_skills[$role_skill['set_role_skill_id']]['lv'];
                        }
                        if($role_attrs -> weaponKind == 0 or $role_attrs -> weaponKind == 3){
                            $role_attrs -> baseSkillLv = $role_skill['lv'];
                            $role_attrs -> skillAttack += $role_skill['lv'] * 0.5;
                            if($role_skill['set_role_skill_id'] > 0){
                                $role_attrs -> sectSkillId = $role_skills[$role_skill['set_role_skill_id']]['skill_id'];
                                $role_attrs -> sectSkillLv = $role_skills[$role_skill['set_role_skill_id']]['lv'];
                                $role_attrs -> skillAttack += $role_attrs -> sectSkillLv;
                            }
                        }
                        break;
                    case '刀法':
                        $base_daofa_lv = $role_skill['lv'];
                        if($role_skill['set_role_skill_id'] > 0){
                            $sect_daofa_lv = $role_skills[$role_skill['set_role_skill_id']]['lv'];
                        }
                        if($role_attrs -> weaponKind == 1){
                            $role_attrs -> baseSkillLv = $role_skill['lv'];
                            $role_attrs -> skillAttack += $role_skill['lv'] * 0.5;
                            if($role_skill['set_role_skill_id'] > 0){
                                $role_attrs -> sectSkillId = $role_skills[$role_skill['set_role_skill_id']]['skill_id'];
                                $role_attrs -> sectSkillLv = $role_skills[$role_skill['set_role_skill_id']]['lv'];
                                $role_attrs -> skillAttack += $role_attrs -> sectSkillLv;
                            }
                        }
                        break;
                    case '剑法':
                        $base_jianfa_lv = $role_skill['lv'];
                        if($role_skill['set_role_skill_id'] > 0){
                            $sect_jianfa_lv = $role_skills[$role_skill['set_role_skill_id']]['lv'];
                        }
                        if($role_attrs -> weaponKind == 2){
                            $role_attrs -> baseSkillLv = $role_skill['lv'];
                            $role_attrs -> skillAttack += $role_skill['lv'] * 0.5;
                            if($role_skill['set_role_skill_id'] > 0){
                                $role_attrs -> sectSkillId = $role_skills[$role_skill['set_role_skill_id']]['skill_id'];
                                $role_attrs -> sectSkillLv = $role_skills[$role_skill['set_role_skill_id']]['lv'];
                                $role_attrs -> skillAttack += $role_attrs -> sectSkillLv;
                            }
                        }
                        break;
                    case '内功':
                        $role_attrs -> skillHp += $role_skill['lv'] * 10;
                        $role_attrs -> skillMp += $role_skill['lv'] * 10;
                        break;
                    case '轻功':
                        $base_qinggong_lv = $role_skill['lv'];
                        if($role_skill['set_role_skill_id'] > 0){
                            $sect_qinggong_lv = $role_skills[$role_skill['set_role_skill_id']]['lv'];
                        }
                        $role_attrs -> skillDodge += $role_skill['lv'] * 0.15;
                        $role_attrs -> comprehensiveQinggongLv += $role_skill['lv'] * 0.5;
                        if($role_skill['set_role_skill_id'] > 0){
                            $role_attrs -> skillDodge += $role_skills[$role_skill['set_role_skill_id']]['lv'] * 0.3;
                            $role_attrs -> comprehensiveQinggongLv += $role_skills[$role_skill['set_role_skill_id']]['lv'];
                        }
                        break;
                    case '招架':
                        $base_zhaojia_lv = $role_skill['lv'];
                        if($role_skill['set_role_skill_id'] > 0){
                            $sect_zhaojia_lv = $role_skills[$role_skill['set_role_skill_id']]['lv'];
                        }
                        $role_attrs -> skillBlock += $role_skill['lv'] * 0.5;
                        if($role_skill['set_role_skill_id'] > 0){
                            $role_attrs -> skillBlock += $role_skills[$role_skill['set_role_skill_id']]['lv'];
                        }
                        break;
                }
            }
        }

        $role_attrs -> maxSectSkillLv = max([$sect_jianfa_lv, $sect_quanjiao_lv, $sect_daofa_lv,]);
        $role_attrs -> comprehensiveSkillLv = max([$base_jianfa_lv * 0.5 + $sect_jianfa_lv, $base_quanjiao_lv * 0.5 + $sect_quanjiao_lv, $base_daofa_lv * 0.5 + $sect_daofa_lv, $base_zhaojia_lv * 0.5 + $sect_zhaojia_lv, $base_qinggong_lv * 0.5 + $sect_qinggong_lv,]);

        /**
         * 更新角色属性
         *
         */
        self ::Update($role_attrs, $role_id);
    }

    /**
     * 通过角色 id 刷新角色来自角色心法的属性
     *
     */
    public static function fromRoleXinfaByRoleId(int $role_id){
        /**
         * 获取角色已装备心法
         *
         */
        $sql = <<<SQL
SELECT `id`, `xinfa_id`, `lv` FROM `role_xinfas` WHERE `role_id` = $role_id AND `equipped` = 1;
SQL;

        $role_xinfas = Helpers ::queryFetchAll($sql);

        /**
         * 获取角色属性
         *
         */
        $role_attrs = Helpers ::getRoleAttrsByRoleId($role_id);

        /**
         * 初始化角色心法属性
         *
         */
        $role_attrs -> xinfaHp = 0;
        $role_attrs -> xinfaMp = 0;
        $role_attrs -> attackXinfaId = 0;
        $role_attrs -> attackXinfaLv = 0;
        $role_attrs -> attackXinfaBaseDamage = 0;
        $role_attrs -> xinfaExtraDamage = 0;


        foreach($role_xinfas as $role_xinfa){
            $xinfa = Helpers ::getXinfaRowByXinfaId($role_xinfa -> xinfa_id);
            if(!empty($xinfa -> kind) && $xinfa -> kind === '生命'){
                $role_attrs -> xinfaHp += Helpers ::getXinfaHpBuff($role_xinfa -> xinfa_id, $role_xinfa -> lv);
                break;
            }
        }

        foreach($role_xinfas as $role_xinfa){
            $xinfa = Helpers ::getXinfaRowByXinfaId($role_xinfa -> xinfa_id);
            if(!empty($xinfa -> kind) && $xinfa -> kind === '攻击'){
                $role_attrs -> attackXinfaId = $role_xinfa -> xinfa_id;
                $role_attrs -> attackXinfaLv = $role_xinfa -> lv;
                $role_attrs -> attackXinfaBaseDamage = [0 => 1, 8 => 2, 64 => 2.5, 216 => 3.5, 512 => 5][$xinfa -> experience];
                $levels = [0, 40, 80, 160, 240, 400, 560, 720, 880, 1000,];
                $xinfa_tricks = Helpers ::getXinfaAttackTrick($role_xinfa -> xinfa_id);
                foreach($levels as $level){
                    if($xinfa_tricks ->{'lv'.$level.'_name'} === '无' && $level <= $role_attrs -> attackXinfaLv){
                        $role_attrs -> xinfaExtraDamage += $xinfa_tricks ->{'lv'.$level.'_damage'};
                    }
                }
                break;
            }
        }

        foreach($role_xinfas as $role_xinfa){
            $xinfa = Helpers ::getXinfaRowByXinfaId($role_xinfa -> xinfa_id);
            if(!empty($xinfa -> kind) && $xinfa -> kind === '内功'){
                $xinfa_mp = Helpers ::getXinfaMpBuff($role_xinfa -> xinfa_id, $role_xinfa -> lv);
                $role_attrs -> xinfaHp += $xinfa_mp['hp'];
                $role_attrs -> xinfaMp += $xinfa_mp['mp'];
                break;
            }
        }


        /**
         * 更新角色属性
         *
         */
        self ::Update($role_attrs, $role_id);
    }

    /**
     * 通过角色 id 刷新角色的随身物品负重
     *
     */
    public static function fromRoleThingByRoleId(int $role_id){
        /**
         * 获取角色已装备心法
         *
         */
        $sql = <<<SQL
SELECT `thing_id`, `number`, `is_coma`, `is_body` FROM `role_things` WHERE `role_id` = $role_id;
SQL;

        $role_things = Helpers ::queryFetchAll($sql);

        $weight = 0;

        if(is_array($role_things)){
            foreach($role_things as $role_thing){
                if($role_thing -> thing_id == 0){
                    if($role_thing -> is_coma == 1 or $role_thing -> is_body == 1){
                        $weight += 30000000;
                    }else{
                        $weight += 1000000;
                    }
                }else{
                    $thing = Helpers ::getThingRowByThingId($role_thing -> thing_id);
                    if(!empty($thing -> weight)){
                        $weight += $thing -> weight * $role_thing -> number;
                    }
                }
            }
        }

        /**
         * 获取角色属性
         *
         */
        $role_attrs = Helpers ::getRoleAttrsByRoleId($role_id);

        $role_attrs -> weight = $weight;

        /**
         * 更新角色属性
         *
         */
        self ::Update($role_attrs, $role_id);
    }
}
