<?php

namespace App\Libs\Events\Timers\Battlefield;

use App\Libs\Attrs\NpcAttrs;
use App\Libs\Attrs\RoleAttrs;
use App\Libs\Helpers;
use App\Libs\Objects\RoleRow;

/**
 * 战场事件
 *
 */
class Event
{
    /**
     * 掉落箱子
     *
     * @param int $map_id
     * @param int $role_id
     */
    public static function dropBox(int $map_id, int $role_id)
    {
        if (Helpers::getProbability(Helpers::getSetting('box_drop_probability'), 1000000)) {
            $map_boxes = cache()->hGet('map_things_' . $map_id, 'boxes');
            if ($map_boxes) {
                $boxes = unserialize($map_boxes);
            } else {
                $boxes = [];
            }
            $boxes[md5(microtime(true))] = [
                'expire'          => time() + 300,
                'thing_id'        => mt_rand(215, 222),
                'protect_role_id' => $role_id,
            ];
            cache()->hSet('map_things_' . $map_id, 'boxes', serialize($boxes));
        }
    }


    /**
     * 掉落金钱
     *
     * @param int $map_id
     * @param int $role_id
     * @param int $npc_max_skill_lv
     */
    public static function dropMoney(int $map_id, int $role_id, int $npc_max_skill_lv)
    {
        $map_money = cache()->hGet('map_things_' . $map_id, 'money');
        if ($map_money) {
            $money = unserialize($map_money);
            $money['number'] += $npc_max_skill_lv;
        } else {
            $money = [];
            $money['is_no_expire'] = false;
            $money['number'] = $npc_max_skill_lv;
        }
        $money['expire'] = time() + 300;
        $money['protect_role_id'] = $role_id;
        cache()->hSet('map_things_' . $map_id, 'money', serialize($money));

    }


    /**
     * 掉落普通物品（装备、药品）
     *
     * @param int $map_id
     * @param int $role_id
     * @param int $npc_id
     */
    public static function dropThing(int $map_id, int $role_id, int $npc_id)
    {
        if (Helpers::getProbability(Helpers::getSetting('thing_drop_probability'), 1000000)) {
            $npc_row = Helpers::getNpcRowByNpcId($npc_id);
            if ($npc_row->rank_id > 0) {
                $map_things = cache()->hGet('map_things_' . $map_id, 'things');
                if ($map_things) {
                    $things = unserialize($map_things);
                } else {
                    $things = [];
                }
                $npc_rank_things = Helpers::getNpcRankThing($npc_row->rank_id);
                $thing = Helpers::getThingRowByThingId($npc_rank_things[array_rand($npc_rank_things)]);
                $things[md5(microtime(true))] = [
                    'expire'          => time() + 300,
                    'thing_id'        => $thing->id,
                    'protect_role_id' => $role_id,
                    'status'          => $thing->kind == '装备' ? mt_rand(1, 4) : 0,
                    'durability'      => $thing->kind == '装备' ? $thing->max_durability : 0,
                ];
                cache()->hSet('map_things_' . $map_id, 'things', serialize($things));
            }
        }
    }


    /**
     * 掉落心法列表
     *
     * @var array|int[]
     */
    public static array $dropXinfas = [
        2, 3, 4, 5, 6, 8, 9, 10, 11, 12, 13, 15, 17, 19, 20, 21, 22, 23, 25, 26,
        28, 29, 30, 31, 32, 34, 35, 36, 38, 39, 40, 43, 44, 45, 46, 47, 48, 49, 50, 51,
        52, 53, 54, 55, 56, 57, 58, 59, 60, 62, 63, 64, 65, 67, 69, 71, 72, 73, 74, 75,
        76, 78, 79, 80, 86, 87, 89, 90, 91, 95, 96, 100, 102, 104, 105, 108, 109, 110, 113, 115,
        116, 117, 120, 121, 122, 124, 126, 127, 128, 129, 131, 132, 134, 138, 142, 146, 148, 149, 150, 151,
        152, 153, 154, 155, 156, 157, 160, 161, 162, 163, 175, 176, 177, 178, 179, 180, 181, 182, 183, 184,
        185, 186, 187, 188, 189, 190, 191, 192, 193, 194, 195, 196, 197,
    ];


    /**
     * 掉落心法
     *
     * @param int $map_id
     * @param int $role_id
     */
    public static function dropXinfa(int $map_id, int $role_id)
    {
        if (Helpers::getProbability(Helpers::getSetting('xinfa_drop_probability'), 1000000)) {
            $map_xinfas = cache()->hGet('map_things_' . $map_id, 'xinfas');
            if ($map_xinfas) {
                $xinfas = unserialize($map_xinfas);
            } else {
                $xinfas = [];
            }
            $xinfa = Helpers::getXinfaRowByXinfaId(self::$dropXinfas[array_rand(self::$dropXinfas)]);
            $xinfas[md5(microtime(true))] = [
                'expire'          => time() + 300,
                'protect_role_id' => $role_id,
                'xinfa_id'        => $xinfa->id,
                'base_experience' => Helpers::getXinfaBaseExperience($xinfa->experience),
                'experience'      => 0,
                'lv'              => 1,
                'max_lv'          => mt_rand(40, 80),
                'private_name'    => '',
            ];
            cache()->hSet('map_things_' . $map_id, 'xinfas', serialize($xinfas));
        }
    }


    /**
     * 掉落尸体
     *
     * @param int    $map_id
     * @param string $npc_name
     */
    public static function dropBody(int $map_id, string $npc_name)
    {
        $map_bodies = cache()->hGet('map_things_' . $map_id, 'bodies');
        if ($map_bodies) {
            $bodies = unserialize($map_bodies);
        } else {
            $bodies = [];
        }
        $bodies[md5(microtime(true))] = [
            'expire' => time() + 240,
            'name'   => $npc_name,
        ];
        cache()->hSet('map_things_' . $map_id, 'bodies', serialize($bodies));
    }


    /**
     * 连续任务
     *
     * @param RoleRow   $role_row
     * @param RoleAttrs $role_attrs
     * @param NpcAttrs  $npc_attrs
     * @param array     $messages
     */
    public static function consecutiveMission(RoleRow &$role_row, RoleAttrs &$role_attrs, NpcAttrs &$npc_attrs, array &$messages)
    {
        /**
         * 判断是否完成任务
         *
         */
        if (!empty($role_row->mission)) {
            $mission = json_decode($role_row->mission);
            if ($mission->circle == 1 or $mission->circle == 4) {
                /**
                 * 打怪任务、判断是否完成
                 *
                 */
                if (!$mission->status and $mission->npcId == $npc_attrs->npcId) {
                    /**
                     * 完成任务
                     *
                     */
                    $mission->status = true;
                    $role_row->mission = json_encode($mission, JSON_UNESCAPED_UNICODE);
                    $messages[] = '你完成了此次连续任务，快去领奖吧！';
                    Helpers::setRoleRowByRoleId($role_row->id, $role_row);
                }
            } elseif ($mission->circle == 2 or $mission->circle == 5) {
                /**
                 * 打宝石任务、判断是否完成
                 *
                 */
                if ($mission->gemGainNumber < $mission->gemNumber) {
                    if (Helpers::getGemProbability($role_attrs->maxSkillLv, $npc_attrs->maxSkillLv)) {
                        $mission->gemGainNumber += 1;
                        $messages[] = '你在' . $npc_attrs->name . '身上搜取到了1个' . [1 => '玛瑙', 2 => '翡翠', 3 => '人参', 4 => '玉佩'][$mission->gemKind] . '，赶紧揣在怀里。';
                        if ($mission->gemGainNumber >= $mission->gemNumber) {
                            $messages[] = '你完成了此次连续任务，快去领奖吧！';
                        }
                        $role_row->mission = json_encode($mission, JSON_UNESCAPED_UNICODE);
                        Helpers::setRoleRowByRoleId($role_row->id, $role_row);
                    }
                }
            }
        }
    }


    /**
     * 推送地图消息
     *
     * @param int   $map_id
     * @param int   $role_id
     * @param array $map_messages
     * @param int   $o_role_id
     */
    public static function pushMapMessages(int $map_id, int $role_id, array &$map_messages, int $o_role_id = 0)
    {
        $map_roles = cache()->sMembers('map_roles_' . $map_id);
        $map_roles = array_filter(array_unique($map_roles), function ($map_role) use (&$role_id, &$o_role_id) {
            if ($map_role == $role_id or $map_role == $o_role_id) {
                return false;
            } else {
                return true;
            }
        });
        if ($map_roles) {
//            $count = cache()->incr('map_message_push_count_' . $map_id);
//            if ($count)
            $pipeline = cache()->pipeline();
            foreach ($map_roles as $map_role) {
                $pipeline->rPush('role_map_messages_' . $map_role, ...$map_messages);
            }
            $pipeline->exec();
        }
    }


    /**
     * 掉落所有
     *
     * @param int       $role_id
     * @param int       $map_id
     * @param RoleAttrs $role_attrs
     */
    public static function dropAll(int $role_id, int $map_id, RoleAttrs &$role_attrs)
    {
        /**
         * 获取所有物品
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `role_id` = $role_id;
SQL;

        $role_things = Helpers::queryFetchAll($sql);
        if (is_array($role_things)) {
            $sql = '';
            foreach ($role_things as $role_thing) {
                if ($role_thing->thing_id == 0) {
                    $sql .= <<<SQL
DELETE FROM `role_things` WHERE `id` = $role_thing->id;
SQL;

                    if ($role_thing->is_body) {
                        $map_bodies = cache()->hGet('map_things_' . $map_id, 'bodies');
                        if ($map_bodies) {
                            $map_bodies = unserialize($map_bodies);
                        } else {
                            $map_bodies = [];
                        }
                        $map_bodies[md5(microtime(true))] = [
                            'expire' => $role_thing->body_expire,
                            'name'   => $role_thing->body_name,
                        ];
                        cache()->hSet('map_things_' . $map_id, 'bodies', serialize($map_bodies));
                    }
                } else {
                    $thing = Helpers::getThingRowByThingId($role_thing->thing_id);
                    if (!$thing->is_no_drop) {
                        $sql .= <<<SQL
DELETE FROM `role_things` WHERE `id` = $role_thing->id;
SQL;

                        if ($thing->kind == '装备') {
                            $map_things = cache()->hGet('map_things_' . $map_id, 'things');
                            if ($map_things) {
                                $map_things = unserialize($map_things);
                            } else {
                                $map_things = [];
                            }
                            if ($role_thing->equipped) {
                                if ($role_attrs->weaponRoleThingId == $role_thing->id) {
                                    $durability = $role_attrs->weaponDurability;
                                } elseif ($role_attrs->armorRoleThingId == $role_thing->id) {
                                    $durability = $role_attrs->armorDurability;
                                } elseif ($role_attrs->clothesRoleThingId == $role_thing->id) {
                                    $durability = $role_attrs->clothesDurability;
                                } elseif ($role_attrs->shoesRoleThingId == $role_thing->id) {
                                    $durability = $role_attrs->shoesDurability;
                                } else {
                                    $durability = $role_thing->durability;
                                }
                            } else {
                                $durability = $role_thing->durability;
                            }
                            $map_things[md5(microtime(true))] = [
                                'expire'          => time() + 300,
                                'thing_id'        => $role_thing->thing_id,
                                'protect_role_id' => 0,
                                'status'          => $role_thing->status,
                                'durability'      => $durability,
                            ];
                            cache()->hSet('map_things_' . $map_id, 'things', serialize($map_things));
                        } elseif ($thing->id === 213) {
                            $map_money = cache()->hGet('map_things_' . $map_id, 'money');

                            if ($map_money) {
                                $map_money = unserialize($map_money);
                                $map_money['expire'] = time() + 300;
                                $map_money['number'] += $role_thing->number;
                            } else {
                                $map_money = [];
                                $map_money['expire'] = time() + 300;
                                $map_money['protect_role_id'] = 0;
                                $map_money['number'] = $role_thing->number;
                                $map_money['is_no_expire'] = false;
                            }

                            cache()->hSet('map_things_' . $map_id, 'money', serialize($map_money));
                        } elseif (in_array($thing->id, [220, 245, 219, 218, 217, 216, 215, 221, 222])) {
                            $map_boxes = cache()->hGet('map_things_' . $map_id, 'boxes');

                            if ($map_boxes) {
                                $map_boxes = unserialize($map_boxes);
                            } else {
                                $map_boxes = [];
                            }

                            for ($i = 0; $i < $role_thing->number; $i++) {
                                $map_boxes[md5(mt_rand(1, 88888888))] = [
                                    'expire'          => time() + 300,
                                    'thing_id'        => $role_thing->thing_id,
                                    'protect_role_id' => 0,
                                ];
                            }

                            cache()->hSet('map_things_' . $map_id, 'boxes', serialize($map_boxes));
                        } elseif ($thing->kind == '书籍') {
                            $map_things = cache()->hGet('map_things_' . $map_id, 'things');

                            if ($map_things) {
                                $map_things = unserialize($map_things);
                            } else {
                                $map_things = [];
                            }

                            $map_things[md5(microtime(true))] = [
                                'expire'          => time() + 300,
                                'thing_id'        => $role_thing->thing_id,
                                'protect_role_id' => 0,
                                'status'          => 0,
                                'durability'      => 0,
                            ];

                            cache()->hSet('map_things_' . $map_id, 'things', serialize($map_things));
                        }
                    }
                }
            }
            if ($sql !== '') {
                Helpers::execSql($sql);
            }
        }
    }
}
