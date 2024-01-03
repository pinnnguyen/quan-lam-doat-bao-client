<?php

namespace App\Libs\Events\Timers\Battlefield;

use App\Core\Configs\FlushConfig;
use App\Libs\Attrs\FlushRoleAttrs;
use App\Libs\Events\Timers\ReviveTimer;
use App\Libs\Helpers;
use Workerman\Lib\Timer;

/**
 * 玩家杀戮 NPC
 *
 */
class PlayerKillNPC
{
    public static function hook(array &$battlefield, int $i)
    {
        //Helpers::log_message("PlayerKillNPC");
        $role_id = $battlefield['role_id'];

        /**
         * 获取 NPC 玩家原生数据 属性 出招
         *
         */
        [$npc_attrs, $role_row, $role_attrs] = cache()->mget([
            $battlefield['b' . $i . '_id'],
            'role_row_' . $role_id, 'role_attrs_' . $role_id,
        ]);
        if (empty($npc_attrs) or empty($role_row) or empty($role_attrs)) {
            $battlefield['b' . $i . '_state'] = false;
            cache()->hSet($battlefield['id'], 'b' . $i . '_state', false);
            if (!empty($role_attrs)) {
                if (Attr::isFree($battlefield)) {
                    Attr::recover($role_attrs);
                    Helpers::setRoleAttrsByRoleId($role_id, $role_attrs);
                }
            }
            return;
        }

        $role_action = $battlefield['b' . $i . '_action'];
        if ($role_action !== false) {
            $role_action = json_decode($role_action, true);
            cache()->hSet($battlefield['id'], 'b' . $i . '_action', false);
        }


        //Helpers::log_message('角色属性：'.var_export($role_attrs,true));
        //即你的气血低于escape_ratio% 时进入战斗，系统就会自动下达逃跑指令
        $ratio = ($role_attrs->hp / $role_attrs->maxHp) * 100;
        //Helpers::log_message("气血值：".$ratio);
        if (($ratio <= $role_row->escape_ratio)){
            $role_action['kind'] = 4;
        }
        //Helpers::log_message("role_action:".var_export($role_action,true));


        $messages = [];
        $map_messages = [];



        /**
         * 玩家先出手
         *
         */

        /**
         * 计算 NPC 闪避
         *
         */
        $npc_dodge = false;
        // $limit = max(($role_attrs->attackXinfaLv - $role_attrs->maxSectSkillLv) / 20, 0);
        // $dodge = 75 + ($role_attrs->sectSkillLv - $npc_attrs->comprehensiveQinggongLv - $npc_attrs->equipmentDodge / 10) / 2 + $limit;
        // $dodge = 100 - max(min($dodge, 100), 0);
        if (Helpers::getProbability($npc_attrs->dodgeProbability, 1000)) {
            // if (Helpers::getProbability($dodge, 100)) {
            $npc_dodge = true;
            $npc_dodge_desc = Attr::getDodgeDesc($npc_attrs->sect_id);
        }

        /**
         * 计算 NPC 格挡
         *
         */
        $npc_block = false;
        if (!$npc_dodge) {
            if (Helpers::getProbability($npc_attrs->blockProbability, 100000)) {
                $npc_block = true;
                $npc_block_desc = Attr::getBlockDesc($npc_attrs->weaponKind);
            }
        }

        if (!$npc_dodge) {
            if ($role_attrs->weaponRoleThingId > 0 and $role_attrs->weaponDurability > 0) {
                $role_attrs->weaponDurability--;
                if ($role_attrs->weaponDurability === 0) {
                    $role_attrs->weaponDurability = 0;
                    $sql = <<<SQL
UPDATE `role_things` SET `durability` = 0 WHERE `id` = $role_attrs->weaponRoleThingId;
SQL;

                    Helpers::execSql($sql);
                    FlushRoleAttrs::fromRoleEquipmentByRoleId($role_id);
                    $role_attrs = Helpers::getRoleAttrsByRoleId($role_id);
                    if (empty($role_attrs)) {
                        cache()->hSet($battlefield['id'], 'b' . $i . '_state', false);
                        return;
                    }
                }
            }
        }

        /**
         * 普通攻击
         *
         */
        if (!$role_action) {
            ORD_ATTACK:

            /**
             * 计算伤害
             *
             */
            if (!$npc_dodge) {
                $damage_to_npc = $role_attrs->attack - $npc_attrs->defence;
                if ($npc_block) {
                    $damage_to_npc -= $npc_attrs->block;
                }
                $damage_to_npc = $damage_to_npc < 1 ? 1 : $damage_to_npc;
                $npc_attrs->hp -= $damage_to_npc;
            }
            //Helpers::log_message("role_action false hp: ".$role_attrs->hp);
            /**
             * 生成描述
             *
             */
            $npc_status_desc = '$O' . Helpers::getStatusDescription($npc_attrs->hp, $npc_attrs->maxHp) . '！';
            if ($role_attrs->weaponKind === 0 or $role_attrs->weaponKind === 3) {
                $role_attack_desc = Attr::$ordinaryAttackActionDesc[mt_rand(0, 1)];
            } else {
                $role_attack_desc = Attr::$ordinaryAttackActionDesc[mt_rand(2, 3)];
            }
            if ($npc_dodge) {
                $message = $role_attack_desc . $npc_dodge_desc . $npc_status_desc;
            } else {
                if ($npc_block) {
                    $role_attack_desc .= $npc_block_desc;
                } else {
                    if ($role_attrs->weaponKind === 0 or $role_attrs->weaponKind === 3) {
                        $role_attack_desc .= Attr::$ordinaryAttackResultDesc[mt_rand(0, 2)];
                    } else {
                        $role_attack_desc .= Attr::$ordinaryAttackResultDesc[mt_rand(0, 7)];
                    }
                }
                //$message = $role_attack_desc . Attr::$damageDesc . $npc_status_desc;
                $message = $role_attack_desc . $npc_status_desc;
            }
            $message = str_replace(['$O', '$W', '$P', '$D'], [
                $npc_attrs->name, $role_attrs->weaponName,
                Attr::getPosition(), $damage_to_npc ?? 0,
            ], $message);
            $messages[] = str_replace('$M', '你', $message);
            $map_messages[] = str_replace('$M', $role_row->name, $message);

            /**
             * 技能攻击
             *
             */
        } elseif ($role_action['kind'] == 1) {
            /**
             * 获取技能招式属性
             *
             */
            $skill_trick = Attr::getSkillTrick($role_action['skill_id'], Attr::$skillTrickNumbers[$role_action['skill_level']]);

            /**
             * 判断 MP 是否足够
             *
             */
            if ($role_attrs->mp < $skill_trick->mp) {
                $message = str_replace('$N', $skill_trick->name, Attr::$neiLi);
                $messages[] = str_replace('$M', '你', $message);
                $map_messages[] = str_replace('$M', $role_row->name, $message);
                goto ORD_ATTACK;
            }
            $role_attrs->mp -= $skill_trick->mp;

            /**
             * 计算伤害
             *
             */
            if (!$npc_dodge) {
                $damage_to_npc = $role_attrs->attack + $skill_trick->damage - $npc_attrs->defence;
                if ($npc_block) {
                    $damage_to_npc -= $npc_attrs->block;
                }
                $damage_to_npc = $damage_to_npc < 1 ? 1 : $damage_to_npc;
                $npc_attrs->hp -= $damage_to_npc;
            }
            //Helpers::log_message("role_action[kind] = 1  hp: ".$role_attrs->hp);
            /**
             * 生成描述、删除玩家出招
             *
             */
            $npc_status_desc = '$O' . Helpers::getStatusDescription($npc_attrs->hp, $npc_attrs->maxHp) . '！';
            $role_attack_desc = $skill_trick->action_description;
            if ($npc_dodge) {
                $message = $role_attack_desc . $npc_dodge_desc . $npc_status_desc;
            } else {
                if ($npc_block) {
                    $role_attack_desc .= $npc_block_desc;
                } else {
                    $role_attack_desc .= $skill_trick->result_description;
                }
                //$message = $role_attack_desc . Attr::$damageDesc . $npc_status_desc;
                $message = $role_attack_desc . $npc_status_desc;
            }
            $message = str_replace(['$O', '$W', '$P', '$D'], [
                $npc_attrs->name, $role_attrs->weaponName,
                Attr::getPosition(), $damage_to_npc ?? 0,
            ], $message);
            $messages[] = str_replace('$M', '你', $message);
            $map_messages[] = str_replace('$M', $role_row->name, $message);

            /**
             * 心法攻击
             *
             */
        } elseif ($role_action['kind'] == 2) {
            /**
             * 获取心法招式属性
             *
             */
            $xinfa_trick = Attr::getXinfaTrick($role_action['xinfa_id'], Attr::$xinfaTrickNumbers[$role_action['xinfa_level']]);

            /**
             * 判断 MP 是否足够
             *
             */
            if ($role_attrs->mp < $xinfa_trick->mp) {
                $message = str_replace('$N', $xinfa_trick->name, Attr::$neiLi);
                $messages[] = str_replace('$M', '你', $message);
                $map_messages[] = str_replace('$M', $role_row->name, $message);
                goto ORD_ATTACK;
            }
            $role_attrs->mp -= $xinfa_trick->mp;

            /**
             * 计算伤害
             *
             */
            if (!$npc_dodge) {
                $damage_to_npc = $role_attrs->xinfaExtraDamage + $role_attrs->attackXinfaBaseDamage * $role_attrs->attackXinfaLv +
                    $xinfa_trick->damage + $role_attrs->equipmentAttack - $npc_attrs->defence;
                $damage_to_npc = intval($damage_to_npc);
                if ($npc_block) {
                    $damage_to_npc -= $npc_attrs->block;
                }
                $damage_to_npc = $damage_to_npc < 1 ? 1 : $damage_to_npc;
                $npc_attrs->hp -= $damage_to_npc;
            }

            /**
             * 生成描述
             *
             */
            $npc_status_desc = '$O' . Helpers::getStatusDescription($npc_attrs->hp, $npc_attrs->maxHp) . '！';
            $role_attack_desc = Attr::$xinfaDesc;
            if ($npc_dodge) {
                $message = $role_attack_desc . $npc_dodge_desc . $npc_status_desc;
            } else {
                if ($npc_block) {
                    $role_attack_desc .= $npc_block_desc;
                } else {
                    $role_attack_desc .= Attr::$ordinaryAttackResultDesc[mt_rand(0, 2)];
                }
                //$message = $role_attack_desc . Attr::$damageDesc . $npc_status_desc;
                $message = $role_attack_desc . $npc_status_desc;
            }
            $message = str_replace(['$O', '$W', '$P', '$D', '$N'], [
                $npc_attrs->name, $role_attrs->weaponName,
                Attr::getPosition(), $damage_to_npc ?? 0, $xinfa_trick->name,
            ], $message);
            $messages[] = str_replace('$M', '你', $message);
            $map_messages[] = str_replace('$M', $role_row->name, $message);

            /**
             * 投降
             *
             */
        } elseif ($role_action['kind'] == 3) {
            /**
             * 生成描述
             *
             */
            $message = str_replace('$O', $npc_attrs->name, Attr::$surrenderDesc);
            $messages[] = str_replace('$M', '你', $message);
            $map_messages[] = str_replace('$M', $role_row->name, $message);

            /**
             * 逃跑
             *
             */
        } elseif ($role_action['kind'] == 4) {
            /**
             * 计算概率
             *
             */
            /*if ($role_attrs->weight >= 100000000) {
                $escape = 20;
            } else {
                $escape = 50 + ($role_attrs->comprehensiveQinggongLv - $npc_attrs->comprehensiveQinggongLv) / 5;
                if ($escape < 20) {
                    $escape = 20;
                } elseif ($escape > 80) {
                    $escape = 80;
                }
            }*/
            //if (Helpers::getProbability($escape, 100)) {
                /**
                 * 改变玩家所在地图
                 *
                 */
                $messages[] = '你逃跑成功！';
                if ($role_attrs->weight < 100000000) {
                    $map_row = Helpers::getMapRowByMapId($role_row->map_id);
                    $maps = [$map_row->north_map_id, $map_row->west_map_id, $map_row->east_map_id, $map_row->south_map_id];
                    foreach ($maps as $k => $map) if (empty($map)) unset($maps[$k]);
                    $role_row->map_id = $maps[array_rand($maps)];
                    $forwards = [$map_row->north_map_id => '北', $map_row->west_map_id => '西', $map_row->east_map_id => '东', $map_row->south_map_id => '南'];
                    $map_messages[] = $role_row->name . '向' . $forwards[$role_row->map_id] . '逃跑了！';
                    cache()->sRem('map_roles_' . $npc_attrs->mapId, $role_id);
                    Helpers::setRoleRowByRoleId($role_id, $role_row);
                    cache()->sAdd('map_roles_' . $role_row->map_id, $role_id);
                    cache()->rPush('map_footprints_for_come_' . $role_row->map_id, $role_row->name . '走了过来。');
                }
                $role_attrs->isFighting = false;
                $npc_attrs->isFought = true;
                $npc_attrs->isFighting = false;

                /**
                 * 销毁战场
                 *
                 */
                $battlefield['b1_state'] = false;
                $battlefield['b2_state'] = false;
                $battlefield['b3_state'] = false;
                cache()->hMSet($battlefield['id'], ['b1_state' => false, 'b2_state' => false, 'b3_state' => false]);
                cache()->set($battlefield['b' . $i . '_id'], $npc_attrs);
                Attr::recover($role_attrs);
                Helpers::setRoleAttrsByRoleId($role_id, $role_attrs);

                /**
                 * 推送战斗信息
                 *
                 */
                cache()->rPush('role_messages_' . $role_id, ...$messages);
                Event::pushMapMessages($npc_attrs->mapId, $role_row->id, $map_messages);
                return;
            /*} else {
                $message = str_replace('$O', $npc_attrs->name, Attr::$escapeFailedDesc);
                $messages[] = str_replace('$M', '你', $message);
                $map_messages[] = str_replace('$M', $role_row->name, $message);
            }*/
        }

        /**
         * NPC 死亡事件
         *
         */
        if ($npc_attrs->hp <= 0) {
            /**
             * 击杀信息
             *
             */
            $message = str_replace('$O', $npc_attrs->name, Attr::$killDesc);
            $messages[] = str_replace('$M', '你', $message);
            $map_messages[] = str_replace('$M', $role_row->name, $message);

            /**
             * 判断是否可获得修为
             *
             */
            $virtual_lv = 10 * pow($role_attrs->experience / 1000, 1 / 3);
            $role_gains_experience = intval(sqrt($npc_attrs->experience / 1000000) * 1000 / 2);
            if ($role_gains_experience > 0) {
                $times = Helpers::getExperienceRatio();
                if ($role_row->vip_double_time > time()) {
                    $times += 1;
                }
                if ($role_row->triple_experience > time()) {
                    $times += 2;
                } elseif ($role_row->double_experience > time()) {
                    $times += 1;
                }
                $role_gains_experience *= $times;
                if ($npc_attrs->experience / 1000 > pow(($virtual_lv - 40) / 10, 3) and
                    $npc_attrs->experience / 1000 < pow(($virtual_lv + 40) / 10, 3)) {
                    if ($role_attrs->maxSkillLv >= $npc_attrs->maxSkillLv - 50 && $role_attrs->maxSkillLv <= $npc_attrs->maxSkillLv + 50) {
                        $role_attrs->experience += $role_gains_experience;
                        $messages[] = '获得了' . Helpers::getHansExperience($role_gains_experience) . '修为';
                    }
                }
            }

            /**
             * 判断是否可获得潜能
             *
             */
            $role_gains_qianneng = intval($npc_attrs->maxSkillLv / 5);
            if ($role_gains_qianneng > 0) {
                $times = Helpers::getQiannengRatio();
                if ($role_row->vip_double_time > time()) {
                    $times += 1;
                }
                if ($role_row->triple_qianneng > time()) {
                    $times += 2;
                } elseif ($role_row->double_qianneng > time()) {
                    $times += 1;
                }
                $role_gains_qianneng *= $times;
                if ($npc_attrs->experience / 1000 > pow(($virtual_lv - 55) / 10, 3) and
                    $npc_attrs->experience / 1000 < pow(($virtual_lv + 55) / 10, 3)) {
                    if ($role_attrs->maxSkillLv > $npc_attrs->maxSkillLv - 60 && $role_attrs->maxSkillLv < $npc_attrs->maxSkillLv + 60) {
                        $messages[] = '获得了' . Helpers::getHansNumber($role_gains_qianneng) . '点潜能';
                        if ($role_attrs->qianneng < $role_attrs->maxQianneng) {
                            $role_attrs->qianneng += $role_gains_qianneng;
                        } else {
                            $messages[] = '你的潜能已经满了！';
                        }
                    }
                }
            }

            /**
             * 判断是否还有其他战场
             *
             */
            $battlefield['b' . $i . '_state'] = false;
            if (Attr::isFree($battlefield)) {
                Attr::recover($role_attrs);
                $role_attrs->isFighting = false;
            }

            Event::consecutiveMission($role_row, $role_attrs, $npc_attrs, $messages);

            /**
             * 保存玩家信息、销毁 NPC 销毁战场
             *
             */
            cache()->del($battlefield['b' . $i . '_id']);
            cache()->hSet($battlefield['id'], 'b' . $i . '_state', false);
            Helpers::setRoleAttrsByRoleId($role_id, $role_attrs);

            /**
             * 处理掉落
             *
             */
            Event::dropMoney($npc_attrs->mapId, $role_row->id, $npc_attrs->maxSkillLv);
            Event::dropBox($npc_attrs->mapId, $role_row->id);
            Event::dropThing($npc_attrs->mapId, $role_row->id, $npc_attrs->npcId);
            Event::dropXinfa($npc_attrs->mapId, $role_row->id);
            Event::dropBody($npc_attrs->mapId, $npc_attrs->name);

            /**
             * 推送战斗信息
             *
             */
            cache()->rPush('role_messages_' . $role_id, ...$messages);
            Event::pushMapMessages($npc_attrs->mapId, $role_row->id, $map_messages);
            return;
        }

        /**
         * NPC 出手
         *
         */

        /**
         * 计算玩家闪避
         *
         */
        $role_dodge = false;
        // $dodge = 75 + ($npc_attrs->comprehensiveSkillLv - $role_attrs->comprehensiveQinggongLv - $role_attrs->equipmentDodge / 10) / 2;
        // $dodge = 100 - max(min($dodge, 100), 0);
        if (Helpers::getProbability($role_attrs->dodgeProbability, 1000)) {
            // if (Helpers::getProbability($dodge, 100)) {
            $role_dodge = true;
            $role_dodge_desc = Attr::getDodgeDesc($role_row->sect_id);
            if ($role_attrs->shoesRoleThingId > 0 and $role_attrs->shoesDurability > 0) {
                $role_attrs->shoesDurability--;
                if ($role_attrs->shoesDurability === 0) {
                    $role_attrs->shoesDurability = 0;
                    $sql = <<<SQL
UPDATE `role_things` SET `durability` = 0 WHERE `id` = $role_attrs->shoesRoleThingId;
SQL;
                    Helpers::execSql($sql);
                    Helpers::setRoleAttrsByRoleId($role_id, $role_attrs);
                    FlushRoleAttrs::fromRoleEquipmentByRoleId($role_id);
                    $role_attrs = Helpers::getRoleAttrsByRoleId($role_id);
                    if (empty($role_attrs)) {
                        cache()->hSet($battlefield['id'], 'b' . $i . '_state', false);
                        return;
                    }
                }
            }
        }

        /**
         * 计算玩家格挡
         *
         */
        $role_block = false;
        if (!$role_dodge) {
            if (Helpers::getProbability($role_attrs->blockProbability, 100000)) {
                $role_block = true;
                $role_block_desc = Attr::getBlockDesc($role_attrs->weaponKind);

                /**
                 * 增加修为 潜能
                 *
                 */
                $role_attrs->experience += 12;
                $role_attrs->qianneng += 1;
            }
        }

        if ($role_attrs->clothesRoleThingId > 0 and $role_attrs->clothesDurability > 0) {
            $role_attrs->clothesDurability--;
            if ($role_attrs->clothesDurability === 0) {
                $sql = <<<SQL
UPDATE `role_things` SET `durability` = 0 WHERE `id` = $role_attrs->clothesRoleThingId;
SQL;
                Helpers::execSql($sql);
                Helpers::setRoleAttrsByRoleId($role_id, $role_attrs);
                FlushRoleAttrs::fromRoleEquipmentByRoleId($role_id);
                $role_attrs = Helpers::getRoleAttrsByRoleId($role_id);
                if (empty($role_attrs)) {
                    cache()->hSet($battlefield['id'], 'b' . $i . '_state', false);
                    return;
                }
            }
        }
        if ($role_attrs->armorRoleThingId > 0 and $role_attrs->armorDurability > 0) {
            $role_attrs->armorDurability--;
            if ($role_attrs->armorDurability === 0) {
                $sql = <<<SQL
UPDATE `role_things` SET `durability` = 0 WHERE `id` = $role_attrs->armorRoleThingId;
SQL;
                Helpers::execSql($sql);
                Helpers::setRoleAttrsByRoleId($role_id, $role_attrs);
                FlushRoleAttrs::fromRoleEquipmentByRoleId($role_id);
                $role_attrs = Helpers::getRoleAttrsByRoleId($role_id);
                if (empty($role_attrs)) {
                    cache()->hSet($battlefield['id'], 'b' . $i . '_state', false);
                    return;
                }
            }
        }

        /**
         * NPC 使用技能
         */
        if ($npc_attrs->skillTrickNumber > 0) {
            /**
             * 获得随机技能招式
             *
             */
            $skill_trick = Attr::getRandomSkillTrick($npc_attrs->skillId, $npc_attrs->skillTrickNumber);

            /**
             * 计算伤害
             *
             */
            if (!$role_dodge) {
                $damage_to_role = $npc_attrs->attack + $skill_trick->damage - $role_attrs->defence;
                if ($role_block) {
                    $damage_to_role -= $role_attrs->block;
                }
                $damage_to_role = $damage_to_role < 1 ? 1 : $damage_to_role;
                $role_attrs->hp -= $damage_to_role;
            }

            /**
             * 生成描述
             *
             */
            $role_status_desc = '$O' . Helpers::getStatusDescription($role_attrs->hp, $role_attrs->maxHp) . '！';
            $npc_attack_desc = $skill_trick->action_description;
            if ($role_dodge) {
                $message = $npc_attack_desc . $role_dodge_desc . $role_status_desc;
            } else {
                if ($role_block) {
                    $npc_attack_desc .= $role_block_desc;
                } else {
                    $npc_attack_desc .= $skill_trick->result_description;
                }
                //$message = $npc_attack_desc . Attr::$damageDesc . $role_status_desc;
                $message = $npc_attack_desc . $role_status_desc;
            }
            $message = str_replace(['$M', '$W', '$P', '$D'], [
                $npc_attrs->name, $npc_attrs->weaponName,
                Attr::getPosition(), $damage_to_role ?? 0,
            ], $message);
            $messages[] = str_replace('$O', '你', $message);
            $map_messages[] = str_replace('$O', $role_row->name, $message);

            /**
             * NPC 使用普通攻击
             */
        } else {
            /**
             * 计算伤害
             */
            if (!$role_dodge) {
                $damage_to_role = $npc_attrs->attack - $role_attrs->defence;
                if ($role_block) {
                    $damage_to_role -= $role_attrs->block;
                }
                $damage_to_role = $damage_to_role < 1 ? 1 : $damage_to_role;
                $role_attrs->hp -= $damage_to_role;
            }

            /**
             * 生成描述
             */
            $role_status_desc = '$O' . Helpers::getStatusDescription($role_attrs->hp, $role_attrs->maxHp) . '！';
            if ($npc_attrs->weaponKind === 0 or $npc_attrs->weaponKind === 3) {
                $npc_attack_desc = Attr::$ordinaryAttackActionDesc[mt_rand(0, 1)];
            } else {
                $npc_attack_desc = Attr::$ordinaryAttackActionDesc[mt_rand(2, 3)];
            }
            if ($role_dodge) {
                $message = $npc_attack_desc . $role_dodge_desc . $role_status_desc;
            } else {
                if ($role_block) {
                    $npc_attack_desc .= $role_block_desc;
                } else {
                    if ($npc_attrs->weaponKind === 0 or $npc_attrs->weaponKind === 3) {
                        $npc_attack_desc .= Attr::$ordinaryAttackResultDesc[mt_rand(0, 2)];
                    } else {
                        $npc_attack_desc .= Attr::$ordinaryAttackResultDesc[mt_rand(0, 7)];
                    }
                }
                //$message = $npc_attack_desc . Attr::$damageDesc . $role_status_desc;
                $message = $npc_attack_desc . $role_status_desc;
            }
            $message = str_replace(['$M', '$W', '$P', '$D'], [
                $npc_attrs->name, $npc_attrs->weaponName,
                Attr::getPosition(), $damage_to_role ?? 0,
            ], $message);
            $messages[] = str_replace('$O', '你', $message);
            $map_messages[] = str_replace('$O', $role_row->name, $message);
        }
        if ($role_block) {
            $messages[] = '你的修为上升了。';
            $messages[] = '你的潜能上升了。';
        }
        $role_attrs->hp = $role_attrs->hp < 0 ? 0 : $role_attrs->hp;

        /**
         * 玩家死亡事件
         */
        if ($role_attrs->hp <= 0) {
            /**
             * 物品掉一地
             *
             */
            Event::dropAll($role_id, $npc_attrs->mapId, $role_attrs);

            /**
             * 掉落修为
             *
             */
            $role_attrs->hp = 0;
//            $role_attrs->mp = 0;
            Timer::add(FlushConfig::REVIVE, [ReviveTimer::class, 'hook'], [$role_id], false);
            $role_lost_experience = ceil(sqrt($role_attrs->experience / 1000000) * 1000);
            $role_attrs->experience -= $role_lost_experience;
            $role_attrs->experience = $role_attrs->experience < 0 ? 0 : $role_attrs->experience;

            /**
             * 回到长安客栈、销毁战场
             *
             */
            if ($role_row->red >= 20) {
                $role_row->map_id = 757;
                $role_row->release_time = time() + $role_row->red * 300;
            } else {
                $role_row->map_id = 6;
            }
            $role_attrs->isFighting = false;
            $role_attrs->reviveTimestamp = time() + 40;
            $npc_attrs->isFought = true;
            $npc_attrs->isFighting = false;
            $battlefield['b' . $i . '_state'] = false;
            Helpers::setRoleAttrsByRoleId($role_id, $role_attrs);

            FlushRoleAttrs::fromRoleEquipmentByRoleId($role_id);
            FlushRoleAttrs::fromRoleThingByRoleId($role_id);
            FlushRoleAttrs::fromRoleSkillByRoleId($role_id);

            $battlefield['b1_state'] = false;
            $battlefield['b2_state'] = false;
            $battlefield['b3_state'] = false;
            cache()->hMSet($battlefield['id'], ['b1_state' => false, 'b2_state' => false, 'b3_state' => false]);
            cache()->set($battlefield['b' . $i . '_id'], $npc_attrs);
            cache()->sRem('map_roles_' . $npc_attrs->mapId, $role_id);
            Helpers::setRoleRowByRoleId($role_id, $role_row);
            cache()->sAdd('map_roles_' . $role_row->map_id, $role_id);

            /**
             * 推送战斗信息
             *
             */
            $message = str_replace('$M', $npc_attrs->name, Attr::$killDesc);
            $messages[] = str_replace('$O', '你', $message);
            $map_messages[] = str_replace('$O', $role_row->name, $message);
            $messages[] = '损失了' . Helpers::getHansExperience($role_lost_experience) . '修为';
            cache()->rPush('role_messages_' . $role_id, ...$messages);
            Event::pushMapMessages($npc_attrs->mapId, $role_id, $map_messages);
            return;
        }

        /**
         * 储存 玩家 NPC 信息
         */
        $npc_attrs->isFighting = true;
        $npc_attrs->isFought = true;
        $role_attrs->isFighting = true;
        cache()->set($battlefield['b' . $i . '_id'], $npc_attrs);
        Helpers::setRoleAttrsByRoleId($role_id, $role_attrs);
        cache()->rPush('role_messages_' . $role_id, ...$messages);
        Event::pushMapMessages($npc_attrs->mapId, $role_id, $map_messages);
    }
}
