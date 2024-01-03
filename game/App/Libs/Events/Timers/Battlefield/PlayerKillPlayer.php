<?php

namespace App\Libs\Events\Timers\Battlefield;

use App\Core\Configs\FlushConfig;
use App\Libs\Attrs\FlushRoleAttrs;
use App\Libs\Events\Timers\ReviveTimer;
use App\Libs\Helpers;
use Workerman\Lib\Timer;

/**
 * 玩家杀戮玩家
 *
 */
class PlayerKillPlayer
{
    public static function hook(array &$battlefield, int $i)
    {
        //Helpers::log_message("PlayerKillPlayer");
        $role_id = $battlefield['role_id'];
        $o_role_id = $battlefield['b' . $i . '_id'];

        /**
         * 获取双方玩家原生数据 属性
         *
         */
        [$role_row, $role_attrs, $o_role_row, $o_role_attrs] = cache()->mget([
            'role_row_' . $role_id,
            'role_attrs_' . $role_id, 'role_row_' . $o_role_id, 'role_attrs_' . $o_role_id,
        ]);
        if (empty($role_row) or empty($role_attrs) or empty($o_role_row) or empty($o_role_attrs)) {
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

        /**
         * 获取对方战场
         *
         */
        $o_battlefield = cache()->hMGet('role_battlefield_' . $battlefield['b' . $i . '_id'], [
            'id', 'role_id',
            'b1_state', 'b1_object', 'b1_id', 'b1_kind', 'b1_form',
            'b2_state', 'b2_object', 'b2_id', 'b2_kind', 'b2_form',
            'b3_state', 'b3_object', 'b3_id', 'b3_kind', 'b3_form',
        ]);

        if ($o_battlefield['b1_state'] and $o_battlefield['b1_id'] == $role_id) {
            $o_i = 1;
        } elseif ($o_battlefield['b2_state'] and $o_battlefield['b2_id'] == $role_id) {
            $o_i = 2;
        } elseif ($o_battlefield['b3_state'] and $o_battlefield['b3_id'] == $role_id) {
            $o_i = 3;
        } else {
            $battlefield['b' . $i . '_state'] = false;
            cache()->hSet($battlefield['id'], 'b' . $i . '_state', false);
            if (Attr::isFree($battlefield)) {
                Attr::recover($role_attrs);
                Helpers::setRoleAttrsByRoleId($role_id, $role_attrs);
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
        //Helpers::log_message($ratio);
        if (($ratio <= $role_row->escape_ratio)){
            //Helpers::log_message(var_export($role_action,true));
            $role_action['kind'] = 4;
        }

        $messages = [];
        $o_messages = [];
        $map_messages = [];



        /**
         * 玩家先出手
         *
         */

        /**
         * 计算 对方玩家 闪避
         *
         */
        $o_role_dodge = false;
        // $limit = max(($role_attrs->attackXinfaLv - $role_attrs->maxSectSkillLv) / 20, 0);
        // $dodge = 75 + ($role_attrs->sectSkillLv - $o_role_attrs->comprehensiveQinggongLv - $o_role_attrs->equipmentDodge / 10) / 2 + $limit;
        // $dodge = 100 - max(min($dodge, 100), 0);
        if (Helpers::getProbability($o_role_attrs->dodgeProbability, 1000)) {
            // if (Helpers::getProbability($dodge, 100)) {
            $o_role_dodge = true;
            $o_role_dodge_desc = Attr::getDodgeDesc($o_role_row->sect_id);
        }

        /**
         * 计算 对方玩家 格挡
         *
         */
        $o_role_block = false;
        if (!$o_role_dodge) {
            if (Helpers::getProbability($o_role_attrs->blockProbability, 100000)) {
                $o_role_block = true;
                $o_role_block_desc = Attr::getBlockDesc($o_role_attrs->weaponKind);
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
            if (!$o_role_dodge) {
                $damage_to_o_role = $role_attrs->attack - $o_role_attrs->defence;
                if ($o_role_block) {
                    $damage_to_o_role -= $o_role_attrs->block;
                }
                $damage_to_o_role = $damage_to_o_role < 1 ? 1 : $damage_to_o_role;
                $o_role_attrs->hp -= $damage_to_o_role;
            }

            /**
             * 生成描述
             *
             */
            $o_role_status_desc = '$O' . Helpers::getStatusDescription($o_role_attrs->hp, $o_role_attrs->maxHp) . '！';
            if ($role_attrs->weaponKind == 0 or $role_attrs->weaponKind == 3) {
                $role_attack_desc = Attr::$ordinaryAttackActionDesc[mt_rand(0, 1)];
            } else {
                $role_attack_desc = Attr::$ordinaryAttackActionDesc[mt_rand(2, 3)];
            }
            if ($o_role_dodge) {
                $message = $role_attack_desc . $o_role_dodge_desc . $o_role_status_desc;
            } else {
                if ($o_role_block) {
                    $role_attack_desc .= $o_role_block_desc;
                } else {
                    if ($role_attrs->weaponKind == 0 or $role_attrs->weaponKind == 3) {
                        $role_attack_desc .= Attr::$ordinaryAttackResultDesc[mt_rand(0, 2)];
                    } else {
                        $role_attack_desc .= Attr::$ordinaryAttackResultDesc[mt_rand(0, 7)];
                    }
                }
                //$message = $role_attack_desc . Attr::$damageDesc . $o_role_status_desc;
                $message = $role_attack_desc . $o_role_status_desc;
            }
            $message = str_replace(['$W', '$P', '$D'], [
                $role_attrs->weaponName, Attr::getPosition(),
                $damage_to_o_role ?? 0,
            ], $message);
            $messages[] = str_replace(['$M', '$O'], ['你', $o_role_row->name], $message);
            $o_messages[] = str_replace(['$M', '$O'], [$role_row->name, '你'], $message);
            $map_messages[] = str_replace(['$M', '$O'], [$role_row->name, $o_role_row->name], $message);


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
                $o_messages[] = str_replace('$M', $role_row->name, $message);
                $map_messages[] = str_replace('$M', $role_row->name, $message);
                goto ORD_ATTACK;
            }
            $role_attrs->mp -= $skill_trick->mp;

            /**
             * 计算伤害
             *
             */
            if (!$o_role_dodge) {
                $damage_to_o_role = $role_attrs->attack + $skill_trick->damage - $o_role_attrs->defence;
                if ($o_role_block) {
                    $damage_to_o_role -= $o_role_attrs->block;
                }
                $damage_to_o_role = $damage_to_o_role < 1 ? 1 : $damage_to_o_role;
                $o_role_attrs->hp -= $damage_to_o_role;
            }

            /**
             * 生成描述、删除玩家出招
             *
             */
            $o_role_status_desc = '$O' . Helpers::getStatusDescription($o_role_attrs->hp, $o_role_attrs->maxHp) . '！';
            $role_attack_desc = $skill_trick->action_description;
            if ($o_role_dodge) {
                $message = $role_attack_desc . $o_role_dodge_desc . $o_role_status_desc;
            } else {
                if ($o_role_block) {
                    $role_attack_desc .= $o_role_block_desc;
                } else {
                    $role_attack_desc .= $skill_trick->result_description;
                }
                //$message = $role_attack_desc . Attr::$damageDesc . $o_role_status_desc;
                $message = $role_attack_desc . $o_role_status_desc;
            }
            $message = str_replace(['$W', '$P', '$D'], [
                $role_attrs->weaponName, Attr::getPosition(),
                $damage_to_o_role ?? 0,
            ], $message);
            $messages[] = str_replace(['$M', '$O'], ['你', $o_role_row->name], $message);
            $o_messages[] = str_replace(['$M', '$O'], [$role_row->name, '你'], $message);
            $map_messages[] = str_replace(['$M', '$O'], [$role_row->name, $o_role_row->name], $message);

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
            if (!$o_role_dodge) {
                $damage_to_o_role = $role_attrs->xinfaExtraDamage + $role_attrs->attackXinfaBaseDamage * $role_attrs->attackXinfaLv +
                    $xinfa_trick->damage + $role_attrs->equipmentAttack - $o_role_attrs->defence;
                $damage_to_o_role = intval($damage_to_o_role);
                if ($o_role_block) {
                    $damage_to_o_role -= $o_role_attrs->block;
                }
                $damage_to_o_role = $damage_to_o_role < 1 ? 1 : $damage_to_o_role;
                $o_role_attrs->hp -= $damage_to_o_role;
            }

            /**
             * 生成描述
             *
             */
            $o_role_status_desc = '$O' . Helpers::getStatusDescription($o_role_attrs->hp, $o_role_attrs->maxHp) . '！';
            $role_attack_desc = Attr::$xinfaDesc;
            if ($o_role_dodge) {
                $message = $role_attack_desc . $o_role_dodge_desc . $o_role_status_desc;
            } else {
                if ($o_role_block) {
                    $role_attack_desc .= $o_role_block_desc;
                } else {
                    $role_attack_desc .= Attr::$ordinaryAttackResultDesc[mt_rand(0, 2)];
                }
                //$message = $role_attack_desc . Attr::$damageDesc . $o_role_status_desc;
                $message = $role_attack_desc . $o_role_status_desc;
            }
            $message = str_replace(['$W', '$P', '$D', '$N'], [
                $role_attrs->weaponName, Attr::getPosition(),
                $damage_to_o_role ?? 0, $xinfa_trick->name,
            ], $message);
            $messages[] = str_replace(['$M', '$O'], ['你', $o_role_row->name], $message);
            $o_messages[] = str_replace(['$M', '$O'], [$role_row->name, '你'], $message);
            $map_messages[] = str_replace(['$M', '$O'], [$role_row->name, $o_role_row->name], $message);

            /**
             * 投降
             *
             */
        } elseif ($role_action['kind'] == 3) {
            /**
             * 生成描述
             *
             */
            $messages[] = str_replace(['$M', '$O'], ['你', $o_role_row->name], Attr::$surrenderDesc);
            $o_messages[] = str_replace(['$M', '$O'], [$role_row->name, '你'], Attr::$surrenderDesc);
            $map_messages[] = str_replace(['$M', '$O'], [$role_row->name, $o_role_row->name], Attr::$surrenderDesc);

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
                $escape = 50 + ($role_attrs->comprehensiveQinggongLv - $o_role_attrs->comprehensiveQinggongLv) / 5;
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
                    $o_messages[] = $role_row->name . '向' . $forwards[$role_row->map_id] . '逃跑了！';
                    $map_messages[] = $role_row->name . '向' . $forwards[$role_row->map_id] . '逃跑了！';
                    cache()->sRem('map_roles_' . $o_role_row->map_id, $role_id);
                    Helpers::setRoleRowByRoleId($role_id, $role_row);
                    cache()->sAdd('map_roles_' . $role_row->map_id, $role_id);
                    cache()->rPush('map_footprints_for_come_' . $role_row->map_id, $role_row->name . '走了过来。');
                }
                $role_attrs->isFighting = false;

                /**
                 * 销毁战场
                 *
                 */
                $battlefield['b1_state'] = false;
                $battlefield['b2_state'] = false;
                $battlefield['b3_state'] = false;
                cache()->hMSet($battlefield['id'], ['b1_state' => false, 'b2_state' => false, 'b3_state' => false]);
                cache()->hSet($o_battlefield['id'], 'b' . $o_i . '_state', false);
                Attr::recover($role_attrs);
                Helpers::setRoleAttrsByRoleId($role_id, $role_attrs);
                Helpers::setRoleAttrsByRoleId($o_role_id, $o_role_attrs);

                /**
                 * 推送战斗消息
                 *
                 */
                cache()->rPush('role_messages_' . $role_id, ...$messages);
                cache()->rPush('role_messages_' . $o_role_id, ...$o_messages);
                Event::pushMapMessages($o_role_row->map_id, $role_row->id, $map_messages, $o_role_id);
                return;
            /*} else {
                $messages[] = str_replace(['$M', '$O'], ['你', $o_role_row->name], Attr::$escapeFailedDesc);
                $o_messages[] = str_replace(['$M', '$O'], [$role_row->name, '你'], Attr::$escapeFailedDesc);
                $map_messages[] = str_replace(['$M', '$O'], [$role_row->name, $o_role_row->name], Attr::$escapeFailedDesc);
            }*/
        }

        $o_role_attrs->hp = $o_role_attrs->hp < 0 ? 0 : $o_role_attrs->hp;

        /**
         * 对方玩家 死亡事件
         *
         */
        if ($o_role_attrs->hp <= 0) {
            /**
             * 掉落物品
             */
            Event::dropAll($o_role_id, $role_row->map_id, $o_role_attrs);

            /**
             * 掉落尸体
             *
             */
            Event::dropBody($role_row->map_id, $o_role_row->name);

            $messages[] = str_replace(['$M', '$O'], ['你', $o_role_row->name], Attr::$killDesc);
            $o_messages[] = str_replace(['$M', '$O'], [$role_row->name, '你'], Attr::$killDesc);
            $map_messages[] = str_replace(['$M', '$O'], [$role_row->name, $o_role_row->name], Attr::$killDesc);

            /**
             * 判断是否可获得修为
             *
             */
            $virtual_lv = 10 * pow($role_attrs->experience / 1000, 1 / 3);
            $role_gains_experience = intval(sqrt($o_role_attrs->experience / 1000000) * 1000 * Helpers::getExperienceRatio());
            if ($o_role_attrs->experience / 1000 > pow(($virtual_lv - 40) / 10, 3) and
                $o_role_attrs->experience / 1000 < pow(($virtual_lv + 40) / 10, 3)) {
                if ($role_attrs->maxSkillLv >= $o_role_attrs->maxSkillLv - 50 && $role_attrs->maxSkillLv <= $o_role_attrs->maxSkillLv + 50) {
                    $role_attrs->experience += $role_gains_experience;
                    $messages[] = '获得了' . Helpers::getHansExperience($role_gains_experience) . '修为';
                }
            }

            /**
             * 判断是否可获得潜能
             *
             */
            $role_gains_qianneng = intval($o_role_attrs->maxSkillLv / 5 * Helpers::getQiannengRatio());
            if ($o_role_attrs->experience / 1000 > pow(($virtual_lv - 55) / 10, 3) and
                $o_role_attrs->experience / 1000 < pow(($virtual_lv + 55) / 10, 3)) {
                if ($role_attrs->maxSkillLv > $o_role_attrs->maxSkillLv - 60 && $role_attrs->maxSkillLv < $o_role_attrs->maxSkillLv + 60) {
                    $messages[] = '获得了' . Helpers::getHansNumber($role_gains_qianneng) . '点潜能';
                    if ($role_attrs->qianneng < $role_attrs->maxQianneng) {
                        $role_attrs->qianneng += $role_gains_qianneng;
                    } else {
                        $messages[] = '你的潜能已经满了！';
                    }
                }
            }

            $battlefield['b' . $i . '_state'] = false;
            if (Attr::isFree($battlefield)) {
                Attr::recover($role_attrs);
                $role_attrs->isFighting = false;
            }
            $o_role_attrs->hp = 0;
//            $o_role_attrs->mp = 0;
            $o_role_attrs->isFighting = false;
            $o_role_attrs->reviveTimestamp = time() + 40;
            Timer::add(FlushConfig::REVIVE, [ReviveTimer::class, 'hook'], [$o_role_id], false);

            /**
             * 掉落 修为
             */
            $o_role_lost_experience = $role_gains_experience;
            $o_role_attrs->experience -= $o_role_lost_experience;
            $o_role_attrs->experience = $o_role_attrs->experience < 0 ? 0 : $o_role_attrs->experience;
            $o_messages[] = '损失了' . Helpers::getHansExperience($o_role_lost_experience) . '修为';


            /**
             * 保存玩家信息 销毁战场
             *
             */
            cache()->hSet($battlefield['id'], 'b' . $i . '_state', false);
            cache()->hMSet($o_battlefield['id'], ['b1_state' => false, 'b2_state' => false, 'b3_state' => false]);
            Helpers::setRoleAttrsByRoleId($role_id, $role_attrs);
            Helpers::setRoleAttrsByRoleId($o_role_id, $o_role_attrs);
            FlushRoleAttrs::fromRoleEquipmentByRoleId($o_role_id);
            FlushRoleAttrs::fromRoleThingByRoleId($o_role_id);
            FlushRoleAttrs::fromRoleSkillByRoleId($role_id);

            /**
             * 回到 长安客栈、推送死亡信息、销毁战场
             */
            $o_old_map_id = $o_role_row->map_id;
            $old_map = Helpers::getMapRowByMapId($o_old_map_id);
            if ($o_role_row->red >= 20) {
                $o_role_row->map_id = 757;
                $o_role_row->release_time = time() + $o_role_row->red * 300;
            } else {
                $o_role_row->map_id = 6;
            }
            if ($battlefield['b' . $i . '_form'] == 1) {
                $role_row->kills += 1;
                $role_row->red += 1;
                $o_role_row->killed += 1;
                $sql = <<<SQL
INSERT INTO `role_kill_logs` (`role_id`, `o_role_id`) VALUES ($o_role_id, $role_id);
SQL;
                Helpers::execSql($sql);

            } elseif ($battlefield['b' . $i . '_form'] == 2) {
                $o_role_row->killed++;
            }

            cache()->sRem('map_roles_' . $o_old_map_id, $o_role_id);
            Helpers::setRoleRowByRoleId($o_role_id, $o_role_row);
            cache()->sAdd('map_roles_' . $o_role_row->map_id, $o_role_id);
            Helpers::setRoleRowByRoleId($role_id, $role_row);

            /**
             * 推送战斗信息
             *
             */
            cache()->rPush('role_messages_' . $role_id, ...$messages);
            cache()->rPush('role_messages_' . $o_role_id, ...$o_messages);
            Event::pushMapMessages($o_old_map_id, $role_row->id, $map_messages, $o_role_id);

            /**
             * 判断是否在榜
             */
            $tops = cache()->get('top_gaoshou_roles_id');
            if (in_array($role_id, $tops)) {
                $broadcast = [
                    'kind'    => 6,
                    'content' => '【传言】听说' . $role_row->name . '在' . Helpers::getRegion($old_map->region_id) . '杀死了' . $o_role_row->name . '！',
                ];
            } elseif (in_array($o_role_id, $tops)) {
                $broadcast = [
                    'kind'    => 6,
                    'content' => '【传言】听说' . $o_role_row->name . '在' . Helpers::getRegion($old_map->region_id) . '被' . $role_row->name . '杀死了！',
                ];
            }
            if (isset($broadcast)) {
                $role_keys = cache()->keys('role_row_*');
                if (is_array($role_keys)) {
                    $pipeline = cache()->pipeline();
                    foreach ($role_keys as $role_key) {
                        $pipeline->rPush(str_replace('row', 'broadcast', $role_key), $broadcast);
                    }
                    $pipeline->exec();
                }
            }
            return;
        }

        /**
         * 储存 玩家 信息
         */
        $o_role_attrs->isFighting = true;
        $role_attrs->isFighting = true;
        Helpers::setRoleAttrsByRoleId($role_id, $role_attrs);
        Helpers::setRoleAttrsByRoleId($o_role_id, $o_role_attrs);
        cache()->rPush('role_messages_' . $role_id, ...$messages);
        cache()->rPush('role_messages_' . $o_role_id, ...$o_messages);
        Event::pushMapMessages($o_role_row->map_id, $role_row->id, $map_messages, $o_role_id);
    }
}
