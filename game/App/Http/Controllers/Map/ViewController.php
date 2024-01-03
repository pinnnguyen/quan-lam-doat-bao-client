<?php

namespace App\Http\Controllers\Map;

use App\Core\Configs\GameConfig;
use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 观察地图人物、物品
 */
class ViewController
{
    /**
     * Xem xét人物首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        $chars = [];
        /**
         * 获取 NPC
         */
        $npcs = cache()->mget(Helpers::getMapNpcs($request->roleRow->map_id));
        if ($npcs) {
            foreach ($npcs as $npc) {
                if ($npc) {
                    $chars[] = [
                        'name'    => $npc->name,
                        'viewUrl' => 'Map/View/npc/map_npc_attrs_' . $npc->mapId . '_' . $npc->npcId . '_' . $npc->number,
                    ];
                }
            }
        }


        /**
         * 获取玩家
         *
         */
        $roles_id = cache()->sMembers('map_roles_' . $request->roleRow->map_id);
        $roles = cache()->mget(array_map(function ($role) {
            return 'role_row_' . $role;
        }, $roles_id));
        $roles_attrs = cache()->mget(array_map(function ($role) {
            return 'role_attrs_' . $role;
        }, $roles_id));
        if ($roles) {
            foreach ($roles as $key => $role) {
                if ($role) {
                    if ($role->id == $request->roleId) continue;
                    if (!empty($roles_attrs[$key])) {
                        if ($roles_attrs[$key]->reviveTimestamp > time()) {
                            $chars[] = [
                                'name'    => (($role->sect_id > 0) ? Helpers::getSect($role->sect_id) . '第' . Helpers::getHansNumber($role->seniority) . '代弟子' : '') . $role->name . '的鬼魂',
                                'viewUrl' => 'Map/View/role/' . $role->id,
                            ];
                        } else {
                            $chars[] = [
                                'name'    => (($role->sect_id > 0) ? Helpers::getSect($role->sect_id) . '第' . Helpers::getHansNumber($role->seniority) . '代弟子' : '') . (!empty($role->nickname)?'「'.$role->nickname.'」':'').$role->name,
                                'viewUrl' => 'Map/View/role/' . $role->id,
                            ];
                        }
                    }
                }
            }
        }

        return $connection->send(\cache_response($request, \view('Map/View/index.twig', [
            'request' => $request,
            'chars'   => $chars,
        ])));
    }


    /**
     * Xem xét玩家
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_id
     *
     * @return bool|null
     */
    public function role(TcpConnection $connection, Request $request, int $role_id)
    {
        /**
         * 获取 玩家
         */
        $role_row = Helpers::getRoleRowByRoleId($role_id);
        $role_attrs = Helpers::getRoleAttrsByRoleId($role_id);

        if (empty($role_row) or empty($role_attrs)) {
            return $connection->send(\cache_response($request, \view('Map/View/message.twig', [
                'request' => $request,
                'message' => 'Nó đang ngoại tuyến!',
            ])));
        }
        if ($role_row->map_id != $request->roleRow->map_id) {
            cache()->sRem('map_roles_' . $request->roleRow->map_id, $role_row->id);
            return $connection->send(\cache_response($request, \view('Map/View/message.twig', [
                'request' => $request,
                'message' => ($role_row->gender == '男' ? 'Anh ta' : 'Cô ta') . 'Rời khỏi!',
            ])));
        }
        $role_title = '【' . Helpers::getTitle($role_row->sect_id, $role_attrs->experience) . '】';
        $role_title .= ($role_row->sect_id > 0) ? Helpers::getSect($role_row->sect_id) . 'đệ' .
            Helpers::getHansNumber($role_row->seniority) . 'đệ tử' : '';

        if (!empty($role_row->nickname)){
            $role_title .= '「'.$role_row->nickname.'」';
        }

        if ($role_attrs->reviveTimestamp > time()) {
            $role_row->name = $role_row->name . 'Quỷ hồn';
        }

        /**
         * 装备
         */
        $equipments = [];
        if ($role_attrs->weaponThingId > 0) {
            $equipments[] = Helpers::getThingRowByThingId($role_attrs->weaponThingId);
        }
        if ($role_attrs->clothesThingId > 0) {
            $equipments[] = Helpers::getThingRowByThingId($role_attrs->clothesThingId);
        }
        if ($role_attrs->armorThingId > 0) {
            $equipments[] = Helpers::getThingRowByThingId($role_attrs->armorThingId);
        }
        if ($role_attrs->shoesThingId > 0) {
            $equipments[] = Helpers::getThingRowByThingId($role_attrs->shoesThingId);
        }

        /**
         * 是否在Danh sách bạn bè
         *
         */
        $blocks = json_decode($request->roleRow->blocks, true);
        if (empty($blocks)) $blocks = [];
        if (!in_array($role_id, $blocks)) {
            $follows = json_decode($request->roleRow->follows, true);
            if (empty($follows)) $follows = [];
            if (in_array($role_id, $follows)) {
                $unfollow_url = 'Map/Role/unfollow/' . $role_id;
            } else {
                $follow_uel = 'Map/Role/follow/' . $role_id;
            }
        }
        $role_row->appearance = mb_substr($role_row->appearance, 1);

        // $vip = match (true) {
        //     $role_row->vip_score >= GameConfig::VIP10_SCORE => 10,
        //     $role_row->vip_score >= GameConfig::VIP9_SCORE  => 9,
        //     $role_row->vip_score >= GameConfig::VIP8_SCORE  => 8,
        //     $role_row->vip_score >= GameConfig::VIP7_SCORE  => 7,
        //     $role_row->vip_score >= GameConfig::VIP6_SCORE  => 6,
        //     $role_row->vip_score >= GameConfig::VIP5_SCORE  => 5,
        //     $role_row->vip_score >= GameConfig::VIP4_SCORE  => 4,
        //     $role_row->vip_score >= GameConfig::VIP3_SCORE  => 3,
        //     $role_row->vip_score >= GameConfig::VIP2_SCORE  => 2,
        //     $role_row->vip_score >= GameConfig::VIP1_SCORE  => 1,
        //     default                                         => 0,
        // };

        return $connection->send(\cache_response($request, \view('Map/View/role.twig', [
            'request'            => $request,
            'role_attrs'         => $role_attrs,
            'role_row'           => $role_row,
            'equipments'         => $equipments,
            'role_age'           => intdiv($role_row->age / 70 / 3600, 10) * 10,
            'role_title'         => $role_title,
            'follow_url'         => $follow_uel ?? null,
            'unfollow_url'       => $unfollow_url ?? null,
            'send_url'           => 'Map/Role/message/' . $role_row->id,
            'kill_url'           => 'Map/Battlefield/playerStartKill/' . $role_id,
            'duel_url'           => 'Map/Battlefield/playerStartDuel/' . $role_id,
            'trade_url'          => 'Func/Transaction/start/' . $role_id,
            'give_url'           => 'Func/Give/start/' . $role_id,
            'ta'                 => $role_row->gender == '男' ? ' hắn ': ' nàng ',
            'wugong_description' => Helpers::getWugongDescription($role_attrs->comprehensiveSkillLv),
            'attack_description' => Helpers::getAttackDescription($role_attrs->attack),
            'status_description' => Helpers::getStatusDescription($role_attrs->hp, $role_attrs->maxHp),
            // 'vip'                => $vip,
        ])));
    }


    /**
     * Xem xét NPC
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param string        $map_npc_id
     *
     * @return bool|null
     */
    public function npc(TcpConnection $connection, Request $request, string $map_npc_id)
    {
        $actions = [];
        /**
         * 获取 NPC
         */
        $npc_attrs = Helpers::getMapNpcAttrsByMapNpcId($map_npc_id);
        if (empty($npc_attrs)) {
            return $connection->send(\cache_response($request, \view('Map/View/message.twig', [
                'request' => $request,
                'message' => 'Nhân vật đã biến mất',
            ])));
        }
        $npc_row = Helpers::getNpcRowByNpcId($npc_attrs->npcId);

        /**
         * 装备
         */
        $equipments = [];
        if ($npc_row->weapon > 0) {
            $equipments[] = Helpers::getThingRowByThingId($npc_row->weapon);
        }
        if ($npc_row->clothes > 0) {
            $equipments[] = Helpers::getThingRowByThingId($npc_row->clothes);
        }
        if ($npc_row->armor > 0) {
            $equipments[] = Helpers::getThingRowByThingId($npc_row->armor);
        }
        if ($npc_row->shoes > 0) {
            $equipments[] = Helpers::getThingRowByThingId($npc_row->shoes);
        }

        /**
         * 传授技能
         *
         */
        if (!empty($npc_row->master_skills)) {
            if ($npc_row->id == $request->roleRow->master) {
                $actions[] = ['name' => 'Học nghệ', 'url' => 'Map/Master/learn/' . $npc_row->id];
                $actions[] = ['name' => 'Phản bội sư', 'url' => 'Map/Master/leaveQuestion/' . $npc_row->id];
            } else {
                $actions[] = ['name' => 'Bái sư', 'url' => 'Map/Master/joinQuestion/' . $npc_row->id];
            }
        }
        $npc_row_actions = json_decode($npc_row->actions, true);
        if (is_array($npc_row_actions)) {
            $actions = array_merge($npc_row_actions, $actions);
        }

        /**
         * 群芳楼任务
         */
        if (in_array($npc_row->id, [746, 743, 744, 745, 751, 752])) {
            /**
             * 查询我的任务
             */
            $sql = <<<SQL
SELECT * FROM `role_qunfanglou_missions` WHERE `role_id` = $request->roleId;
SQL;

            $role_qunfanglou_mission = Helpers::queryFetchObject($sql);
            if (empty($role_qunfanglou_mission)) {
                /**
                 * 任务不存在则添加任务、并重新查询
                 */
                $sql = <<<SQL
INSERT INTO `role_qunfanglou_missions` (`role_id`) VALUES ($request->roleId);
SQL;


                Helpers::execSql($sql);

                $sql = <<<SQL
SELECT * FROM `role_qunfanglou_missions` WHERE `role_id` = $request->roleId;
SQL;

                $role_qunfanglou_mission = Helpers::queryFetchObject($sql);
            }
            if ($npc_row->id == 746) {
                /**
                 * 刀白凤、刀任务
                 */

                if ($role_qunfanglou_mission->dao_number < 45) {
                    /**
                     * 任务未完成
                     */
                    /**
                     * 获取物品
                     */
                    $thing = Helpers::getThingRowByThingId(QunFangLouController::$qfls[$npc_row->id][$role_qunfanglou_mission->dao_number]);
                    if ($role_qunfanglou_mission->dao_status == 1) {
                        /**
                         * 任务已经领取、检查完成状态
                         */
                        $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = $thing->id AND `equipped` = 0; 
SQL;

                        $role_thing = Helpers::queryFetchObject($sql);
                        if ($role_thing) {
                            /**
                             * 任务已完成、显示提交任务链接
                             */
                            $qfl = ['name' => 'Đây là bằng hữu muốn ' . $thing->name . '。', 'url' => 'Map/QunFangLou/daoSubmit/' . $role_qunfanglou_mission->dao_number . '/' . $role_thing->id];
                        } else {
                            /**
                             * 任务未完成、显示Hủy bỏ nhiệm vụ询问链接
                             */
                            $qfl = ['name' => 'Đối thoại', 'url' => 'Map/QunFangLou/daoCancelQuestion/' . $role_qunfanglou_mission->dao_number];
                        }
                    } else {
                        /**
                         * 任务未领取、Cho领取任务询问链接
                         */
                        $qfl = ['name' => 'Đối thoại', 'url' => 'Map/QunFangLou/daoReceiveQuestion/' . $role_qunfanglou_mission->dao_number];
                    }

                }
            } elseif ($npc_row->id == 743) {
                /**
                 * 康敏、剑任务
                 */
                if ($role_qunfanglou_mission->jian_number < 48) {
                    $thing = Helpers::getThingRowByThingId(QunFangLouController::$qfls[$npc_row->id][$role_qunfanglou_mission->jian_number]);
                    if ($role_qunfanglou_mission->jian_status == 1) {
                        $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = $thing->id AND `equipped` = 0; 
SQL;

                        $role_thing = Helpers::queryFetchObject($sql);
                        if ($role_thing) {

                            $qfl = ['name' => 'Đây là bằng hữu muốn ' . $thing->name . '。', 'url' => 'Map/QunFangLou/jianSubmit/' . $role_qunfanglou_mission->jian_number . '/' . $role_thing->id];
                        } else {

                            $qfl = ['name' => 'Đối thoại ', 'url' => 'Map/QunFangLou/jianCancelQuestion/' . $role_qunfanglou_mission->jian_number];
                        }
                    } else {
                        $qfl = ['name' => 'Đối thoại ', 'url' => 'Map/QunFangLou/jianReceiveQuestion/' . $role_qunfanglou_mission->jian_number];
                    }
                }
            } elseif ($npc_row->id == 744) {
                /**
                 * 梦姑、爪任务
                 */
                if ($role_qunfanglou_mission->zhua_number < 15) {
                    $thing = Helpers::getThingRowByThingId(QunFangLouController::$qfls[$npc_row->id][$role_qunfanglou_mission->zhua_number]);
                    if ($role_qunfanglou_mission->zhua_status == 1) {
                        $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = $thing->id AND `equipped` = 0; 
SQL;

                        $role_thing = Helpers::queryFetchObject($sql);
                        if ($role_thing) {

                            $qfl = ['name' => 'Đây là bằng hữu muốn ' . $thing->name . '。', 'url' => 'Map/QunFangLou/zhuaSubmit/' . $role_qunfanglou_mission->zhua_number . '/' . $role_thing->id];
                        } else {

                            $qfl = ['name' => 'Đối thoại ', 'url' => 'Map/QunFangLou/zhuaCancelQuestion/' . $role_qunfanglou_mission->zhua_number];
                        }
                    } else {
                        $qfl = ['name' => 'Đối thoại ', 'url' => 'Map/QunFangLou/zhuaReceiveQuestion/' . $role_qunfanglou_mission->zhua_number];
                    }
                }
            } elseif ($npc_row->id == 745) {
                /**
                 * 阿碧、鞋任务
                 */
                if ($role_qunfanglou_mission->shoes_number < 20) {
                    $thing = Helpers::getThingRowByThingId(QunFangLouController::$qfls[$npc_row->id][$role_qunfanglou_mission->shoes_number]);
                    if ($role_qunfanglou_mission->shoes_status == 1) {
                        $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = $thing->id AND `equipped` = 0; 
SQL;

                        $role_thing = Helpers::queryFetchObject($sql);
                        if ($role_thing) {

                            $qfl = ['name' => 'Đây là bằng hữu muốn ' . $thing->name . '。', 'url' => 'Map/QunFangLou/shoesSubmit/' . $role_qunfanglou_mission->shoes_number . '/' . $role_thing->id];
                        } else {

                            $qfl = ['name' => 'Đối thoại ', 'url' => 'Map/QunFangLou/shoesCancelQuestion/' . $role_qunfanglou_mission->shoes_number];
                        }
                    } else {
                        $qfl = ['name' => 'Đối thoại ', 'url' => 'Map/QunFangLou/shoesReceiveQuestion/' . $role_qunfanglou_mission->shoes_number];
                    }
                }
            } elseif ($npc_row->id == 751) {
                /**
                 * 阿紫、衣任务
                 */
                if ($role_qunfanglou_mission->clothes_number < 19) {
                    $thing = Helpers::getThingRowByThingId(QunFangLouController::$qfls[$npc_row->id][$role_qunfanglou_mission->clothes_number]);
                    if ($role_qunfanglou_mission->clothes_status == 1) {
                        $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = $thing->id AND `equipped` = 0; 
SQL;

                        $role_thing = Helpers::queryFetchObject($sql);
                        if ($role_thing) {

                            $qfl = ['name' => 'Đây là bằng hữu muốn ' . $thing->name . '。', 'url' => 'Map/QunFangLou/clothesSubmit/' . $role_qunfanglou_mission->clothes_number . '/' . $role_thing->id];
                        } else {

                            $qfl = ['name' => 'Đối thoại ', 'url' => 'Map/QunFangLou/clothesCancelQuestion/' . $role_qunfanglou_mission->clothes_number];
                        }
                    } else {
                        $qfl = ['name' => 'Đối thoại ', 'url' => 'Map/QunFangLou/clothesReceiveQuestion/' . $role_qunfanglou_mission->clothes_number];
                    }
                }
            } elseif ($npc_row->id == 752) {
                /**
                 * 阿朱、甲任务
                 */
                if ($role_qunfanglou_mission->armor_number < 24) {
                    $thing = Helpers::getThingRowByThingId(QunFangLouController::$qfls[$npc_row->id][$role_qunfanglou_mission->armor_number]);
                    if ($role_qunfanglou_mission->armor_status == 1) {
                        $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = $thing->id AND `equipped` = 0; 
SQL;

                        $role_thing = Helpers::queryFetchObject($sql);
                        if ($role_thing) {

                            $qfl = ['name' => 'Đây là bằng hữu muốn ' . $thing->name . '。', 'url' => 'Map/QunFangLou/armorSubmit/' . $role_qunfanglou_mission->armor_number . '/' . $role_thing->id];
                        } else {

                            $qfl = ['name' => 'Đối thoại ', 'url' => 'Map/QunFangLou/armorCancelQuestion/' . $role_qunfanglou_mission->armor_number];
                        }
                    } else {
                        $qfl = ['name' => 'Đối thoại ', 'url' => 'Map/QunFangLou/armorReceiveQuestion/' . $role_qunfanglou_mission->armor_number];
                    }
                }
            }
        }


        return $connection->send(\cache_response($request, \view('Map/View/npc.twig', [
            'request'            => $request,
            'actions'            => $actions,
            'wugong_description' => Helpers::getWugongDescription($npc_attrs->comprehensiveSkillLv),
            'attack_description' => Helpers::getAttackDescription($npc_attrs->attack),
            'status_description' => Helpers::getStatusDescription($npc_attrs->hp, $npc_attrs->maxHp),
            'npc_attrs'          => $npc_attrs,
            'npc_row'            => $npc_row,
            'npc_title'          => Helpers::getTitle($npc_row->sect_id, $npc_row->experience),
            'npc_age'            => intdiv($npc_row->age, 10) * 10,
            'equipments'         => $equipments,
            'ta'                 => $npc_row->gender == '男' ? ' hắn ': ' nàng ',
            'kill_url'           => 'Map/Battlefield/npcStartKill/' . $map_npc_id,
            'duel_url'           => 'Map/Battlefield/npcStartDuel/' . $map_npc_id,
            'dialogue_url'       => 'Map/Dialogue/npc/' . $npc_attrs->npcId,
            'give_url'           => 'Map/Give/npc/' . $npc_attrs->npcId,
            'qfl'                => $qfl ?? null,
        ])));
    }
}