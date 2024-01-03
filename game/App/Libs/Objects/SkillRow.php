<?php

namespace App\Libs\Objects;

/**
 * 技能原生数据
 *
 */
class SkillRow
{
    public int $id;
    public string $name;
    public string $description;
    public int $sect_id;
    public string $kind;
    public bool $is_base;

    public ?string $lv5_name = null;
    public ?string $lv10_name = null;
    public ?string $lv20_name = null;
    public ?string $lv40_name = null;
    public ?string $lv80_name = null;
    public ?string $lv120_name = null;
    public ?string $lv160_name = null;
    public ?string $lv180_name = null;
    public ?string $lv240_name = null;
    public ?string $lv300_name = null;
    public ?string $lv360_name = null;
    public ?string $lv420_name = null;
    public ?string $lv480_name = null;
    public ?string $lv700_name = null;
    public ?string $lv1000_name = null;

    public int $lv5_damage;
    public int $lv10_damage;
    public int $lv20_damage;
    public int $lv40_damage;
    public int $lv80_damage;
    public int $lv120_damage;
    public int $lv160_damage;
    public int $lv180_damage;
    public int $lv240_damage;
    public int $lv300_damage;
    public int $lv360_damage;
    public int $lv420_damage;
    public int $lv480_damage;
    public int $lv700_damage;
    public int $lv1000_damage;

    public ?string $lv5_action_description = null;
    public ?string $lv10_action_description = null;
    public ?string $lv20_action_description = null;
    public ?string $lv40_action_description = null;
    public ?string $lv80_action_description = null;
    public ?string $lv120_action_description = null;
    public ?string $lv160_action_description = null;
    public ?string $lv180_action_description = null;
    public ?string $lv240_action_description = null;
    public ?string $lv300_action_description = null;
    public ?string $lv360_action_description = null;
    public ?string $lv420_action_description = null;
    public ?string $lv480_action_description = null;
    public ?string $lv700_action_description = null;
    public ?string $lv1000_action_description = null;

    public ?string $lv5_result_description = null;
    public ?string $lv10_result_description = null;
    public ?string $lv20_result_description = null;
    public ?string $lv40_result_description = null;
    public ?string $lv80_result_description = null;
    public ?string $lv120_result_description = null;
    public ?string $lv160_result_description = null;
    public ?string $lv180_result_description = null;
    public ?string $lv240_result_description = null;
    public ?string $lv300_result_description = null;
    public ?string $lv360_result_description = null;
    public ?string $lv420_result_description = null;
    public ?string $lv480_result_description = null;
    public ?string $lv700_result_description = null;
    public ?string $lv1000_result_description = null;
}
