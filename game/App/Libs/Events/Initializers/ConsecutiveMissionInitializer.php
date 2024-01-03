<?php

namespace App\Libs\Events\Initializers;

use App\Libs\Helpers;
use PDO;

/**
 * 初始化连续任务
 */
class ConsecutiveMissionInitializer
{
    public static function hook()
    {
        /**
         * 获取所有可击杀NPC
         */
        $sql = <<<SQL
SELECT `npc_id`, `map_id`, `base_daofa_lv`, `base_jianfa_lv`, `base_neigong_lv`, `base_qinggong_lv`, `base_quanjiao_lv`, `base_zhaojia_lv`, `sect_qinggong_lv`, `sect_skill_lv` FROM `map_npcs` 
    INNER JOIN `maps` ON `map_npcs`.`map_id` = `maps`.`id` AND `maps`.`is_allow_fight` = 1 
    INNER JOIN `npcs` ON `map_npcs`.`npc_id` = `npcs`.`id`;
SQL;

        $npcs_st = db()->query($sql);
        $npcs = $npcs_st->fetchAll(PDO::FETCH_ASSOC);
        $npcs_st->closeCursor();
        $npcs = array_column($npcs, null, 'npc_id');
        foreach ([51, 52, 57, 58, 114, 118, 128, 129, 187, 190, 193, 197, 581,] as $npc_id) {
            if (!empty($npcs[$npc_id])) unset($npcs[$npc_id]);
        }
        $npcs = array_map(function ($npc) {
            $max_lv = max($npc['base_daofa_lv'], $npc['base_jianfa_lv'], $npc['base_neigong_lv'],
                $npc['base_qinggong_lv'], $npc['base_quanjiao_lv'], $npc['base_zhaojia_lv'],
                $npc['sect_qinggong_lv'], $npc['sect_skill_lv']);
            return ['npc_id' => $npc['npc_id'], 'map_id' => $npc['map_id'], 'max_lv' => $max_lv];
        }, $npcs);
        $consecutive_mission_kill_npcs = [];
        foreach ($npcs as $npc) {
            $consecutive_mission_kill_npcs[$npc['max_lv']][] = ['npc_id' => $npc['npc_id'], 'map_id' => $npc['map_id'],];
        }

        cache()->set('consecutive_mission_kill_npcs', $consecutive_mission_kill_npcs);

        /**
         * 获取所有地图
         */
        $sql = <<<SQL
SELECT `map_id` FROM `map_npcs` WHERE `map_id` != 0;
SQL;

        $maps_st = db()->query($sql);
        $maps = $maps_st->fetchAll(PDO::FETCH_ASSOC);
        $maps_st->closeCursor();
        $maps = array_column($maps, 'map_id', 'map_id');
        $consecutive_mission_maps = array_values($maps);

        cache()->set('consecutive_mission_maps', $consecutive_mission_maps);

        /**
         * 获取所有心法
         */
        $sql = <<<SQL
SELECT * FROM `xinfas`;
SQL;

        $xinfas = Helpers::queryFetchAll($sql);
        $consecutive_mission_xinfas = [];
        foreach ($xinfas as $xinfa) {
            $consecutive_mission_xinfas[$xinfa->experience][] = $xinfa->id;
        }
        cache()->set('consecutive_mission_xinfas', $consecutive_mission_xinfas);


        /**
         * 获取所有NPC
         */
        $sql = <<<SQL
SELECT `npc_id`, `map_id` FROM `map_npcs` WHERE `npc_id` != 0;
SQL;

        $npcs_st = db()->query($sql);
        $npcs = $npcs_st->fetchAll(PDO::FETCH_ASSOC);
        $npcs_st->closeCursor();
        $npcs = array_column($npcs, null, 'npc_id');
        $consecutive_mission_npcs = array_values($npcs);

        cache()->set('consecutive_mission_npcs', $consecutive_mission_npcs);

        /**
         * 获取所有装备库
         */
        $consecutive_mission_equipments = [
            db()->query("SELECT `thing_id` FROM `npc_rank_things` INNER JOIN `things` ON `npc_rank_things`.`thing_id` = `things`.`id` AND `things`.`kind` = '装备' WHERE `npc_rank_id` = 4;")->fetchAll(PDO::FETCH_ASSOC),
            db()->query("SELECT `thing_id` FROM `npc_rank_things` INNER JOIN `things` ON `npc_rank_things`.`thing_id` = `things`.`id` AND `things`.`kind` = '装备' WHERE `npc_rank_id` = 7;")->fetchAll(PDO::FETCH_ASSOC),
            db()->query("SELECT `thing_id` FROM `npc_rank_things` INNER JOIN `things` ON `npc_rank_things`.`thing_id` = `things`.`id` AND `things`.`kind` = '装备' WHERE `npc_rank_id` = 3;")->fetchAll(PDO::FETCH_ASSOC),
            db()->query("SELECT `thing_id` FROM `npc_rank_things` INNER JOIN `things` ON `npc_rank_things`.`thing_id` = `things`.`id` AND `things`.`kind` = '装备' WHERE `npc_rank_id` = 6;")->fetchAll(PDO::FETCH_ASSOC),
            db()->query("SELECT `thing_id` FROM `npc_rank_things` INNER JOIN `things` ON `npc_rank_things`.`thing_id` = `things`.`id` AND `things`.`kind` = '装备' WHERE `npc_rank_id` = 2;")->fetchAll(PDO::FETCH_ASSOC),
            db()->query("SELECT `thing_id` FROM `npc_rank_things` INNER JOIN `things` ON `npc_rank_things`.`thing_id` = `things`.`id` AND `things`.`kind` = '装备' WHERE `npc_rank_id` = 8;")->fetchAll(PDO::FETCH_ASSOC),
            db()->query("SELECT `thing_id` FROM `npc_rank_things` INNER JOIN `things` ON `npc_rank_things`.`thing_id` = `things`.`id` AND `things`.`kind` = '装备' WHERE `npc_rank_id` = 5;")->fetchAll(PDO::FETCH_ASSOC),
            db()->query("SELECT `thing_id` FROM `npc_rank_things` INNER JOIN `things` ON `npc_rank_things`.`thing_id` = `things`.`id` AND `things`.`kind` = '装备' WHERE `npc_rank_id` = 1;")->fetchAll(PDO::FETCH_ASSOC),
            db()->query("SELECT `thing_id` FROM `npc_rank_things` INNER JOIN `things` ON `npc_rank_things`.`thing_id` = `things`.`id` AND `things`.`kind` = '装备' WHERE `npc_rank_id` = 9;")->fetchAll(PDO::FETCH_ASSOC),
        ];

        cache()->set('consecutive_mission_equipments', $consecutive_mission_equipments);
    }
}
