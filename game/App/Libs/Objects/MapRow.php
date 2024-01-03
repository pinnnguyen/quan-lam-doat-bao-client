<?php

namespace App\Libs\Objects;

/**
 * 地图原生数据
 *
 */
class MapRow
{
    public int $id;
    public string $name;
    public string $description;
    public int $region_id;
    public int $north_map_id;
    public int $west_map_id;
    public int $east_map_id;
    public int $south_map_id;
    public bool $is_allow_fight;
    public ?string $actions;
}
