<?php

namespace App\Libs\Objects;

/**
 * NPC 原生数据
 *
 */
class NpcRow
{
    public int $id;
    public string $name;
    public string $description;
    public string $appearance;
    public int $region_id;
    public int $rank_id;
    public string $gender;
    public int $age;
    public int $sect_id;
    public int $seniority;
    public int $experience;
    public int $base_hp;
    public int $drop_money;
    public int $weapon;
    public int $clothes;
    public int $armor;
    public int $shoes;

    public int $base_jianfa_lv;
    public int $base_daofa_lv;
    public int $base_quanjiao_lv;
    public int $base_neigong_lv;
    public int $base_qinggong_lv;
    public int $base_zhaojia_lv;
    public int $sect_qinggong_lv;
    public int $sect_skill;
    public int $sect_skill_lv;

    public int $search_money;
    public int $search_thing;

    public ?string $master_skills;
    public ?string $actions;
    public ?string $dialogues;
}
