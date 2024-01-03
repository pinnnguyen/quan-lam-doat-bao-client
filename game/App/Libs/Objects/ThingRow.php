<?php

namespace App\Libs\Objects;

/**
 * 物品原生数据
 *
 */
class ThingRow
{
    public int $id;
    public string $name;
    public string $description;
    public string $kind;
    public int $equipment_kind;
    public int $money;
    public int $weight;
    public string $unit;
    public int $attack;
    public int $defence;
    public int $dodge;
    public bool $is_no_drop;
    public int $max_durability;
    public int $hp;
    public int $mp;
    public int $jingshen;
}
