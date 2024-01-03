<?php

namespace App\Http\Controllers\Map;

use App\Libs\Helpers;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 地图主控制器
 */
class IndexController
{
    /**
     * 当前地图 Làm mới
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        if ($request->roleRow->map_id === 757) {
            if ($request->roleRow->release_time > time()) {
                $surplus = $request->roleRow->release_time - time();
                cache()->rPush('role_messages_' . $request->roleId, 'Ngươi bất đắc dĩ mà nhìn hắc hắc vách tường, xem ra còn muốn ở chỗ này ngây ngốc một đoạn thời gian. (' . max($surplus, 0) . ' Giây )');
            } else {
                return $this->delivery($connection, $request, 6);
            }
        }

        $map = Helpers::getMapRowByMapId($request->roleRow->map_id);

        if (in_array($request->roleRow->map_id, [82, 743])) {
            if ($request->roleRow->map_id === 82) {
                $push_stone_state = cache()->get('push_stone_state');
                if ($push_stone_state) {
                    $map->east_map_id = 743;
                }
            } elseif ($request->roleRow->map_id === 743) {
                $push_stone_state = cache()->get('push_stone_state');
                if ($push_stone_state) {
                    $map->west_map_id = 82;
                }
            }
        }

        $map_npcs_id = Helpers::getMapNpcs($request->roleRow->map_id);
        $pipeline = cache()->pipeline();
        $pipeline->lRange('role_messages_' . $request->roleId, 0, -1);
        $pipeline->lRange('role_broadcast_' . $request->roleId, 0, -1);
        $pipeline->lRange('role_map_messages_' . $request->roleId, -4, -1);
        $pipeline->lRange('map_footprints_for_come_' . $request->roleRow->map_id, -5, -1);
        $pipeline->lRange('map_footprints_for_leave_' . $request->roleRow->map_id, -5, -1);
        $pipeline->sMembers('map_roles_' . $request->roleRow->map_id);
        $pipeline->mget($map_npcs_id);
        $pipeline->lTrim('role_messages_' . $request->roleId, 1, 0);
        $pipeline->lTrim('role_broadcast_' . $request->roleId, 1, 0);
        $pipeline->lTrim('role_map_messages_' . $request->roleId, 1, 0);
        [$messages, $broadcasts, $map_messages, $footprints_come, $footprints_leave, $roles_id, $npcs] = $pipeline->exec();


        if (!is_array($roles_id)) $roles_id = [];
        if (!in_array($request->roleId, $roles_id)) {
            cache()->sAdd('map_roles_' . $request->roleRow->map_id, $request->roleId);
        }

        $footprints_come = Helpers::clearMyselfFootprint($footprints_come, $request);
        $footprints_leave = Helpers::clearMyselfFootprint($footprints_leave, $request);

        $roles_row_key = array_map(function ($role) {
            return 'role_row_' . $role;
        }, $roles_id);
        $roles_attrs_key = array_map(function ($role) {
            return 'role_attrs_' . $role;
        }, $roles_id);
        $pipeline = cache()->pipeline();
        $pipeline->mget($roles_row_key);
        $pipeline->mget($roles_attrs_key);
        $pipeline->hMGet('map_things_' . $request->roleRow->map_id, ['money', 'boxes', 'things', 'xinfas', 'bodies']);
        $_ = $pipeline->exec();
        if (is_array($_) and count($_) === 3) {
            [$roles, $roles_attrs, $map_things] = $_;
        }

        /**
         * 处理足迹
         */
        $chars = [];
        $chars_num = 0;
        /**
         * 获取 NPC
         */
        if (is_array($npcs)) {
            foreach ($npcs as $npc) {
                if ($npc) {
                    $chars[] = ['name' => $npc->name,];
                    $chars_num++;
                    if ($chars_num > 15) {
                        break;
                    }
                }
            }
        }

        /**
         * 获取玩家
         */
        if (!empty($roles) and is_array($roles) and !empty($roles_attrs) and is_array($roles_attrs)) {
            foreach ($roles as $key => $role) {
                if ($role) {
                    if ($role->map_id == $request->roleRow->map_id) {
                        if ($role->id == $request->roleId) continue;
                        if (!empty($roles_attrs[$key])) {
                            if ($roles_attrs[$key]->reviveTimestamp > time()) {
                                $chars[] = ['name' => $role->name . 'Quỷ hồn',];
                            } else {
                                $chars[] = ['name' => $role->name,];
                            }
                            $chars_num++;
                            if ($chars_num > 15) {
                                break;
                            }
                        }
                    }
                }
            }
        }

        /**
         * 获取物品
         */
        $things = [];
        $things_num = 0;
        if (!empty($map_things['money'])) {
            $map_things_money = unserialize($map_things['money']);
            if ($map_things_money['number'] > 0 or ($map_things_money['is_no_expire'] or $map_things_money['expire'] > time())) {
                if ($map_things_money['number'] < 100) {
                    $things[] = 'Đồng tiền';
                } elseif ($map_things_money['number'] < 10000) {
                    $things[] = 'Bạc trắng';
                } else {
                    $things[] = 'Hoàng kim';
                }
            }
        }
        if (!empty($map_things['boxes'])) {
            $map_things_boxes = unserialize($map_things['boxes']);
            foreach ($map_things_boxes as $map_things_box) {
                if ($map_things_box['expire'] > time() and !empty($map_things_box['thing_id'])) {
                    $thing = Helpers::getThingRowByThingId($map_things_box['thing_id']);
                    $things[] = $thing->name;
                    $things_num++;
                    if ($things_num > 15) {
                        break;
                    }
                }
            }
        }
        if (!empty($map_things['bodies'])) {
            $map_things_bodies = unserialize($map_things['bodies']);
            foreach ($map_things_bodies as $map_things_body) {
                if ($map_things_body['expire'] > time()) {
                    if ($map_things_body['expire'] - time() > 180) {
                        $things[] = $map_things_body['name'] . 'Thi thể';
                    } elseif ($map_things_body['expire'] - time() > 120) {
                        $things[] = 'Hư thối thi thể';
                    } elseif ($map_things_body['expire'] - time() > 60) {
                        $things[] = 'Khô cạn thi thể';
                    } else {
                        $things[] = 'Hài cốt';
                    }
                    $things_num++;
                    if ($things_num > 15) {
                        break;
                    }
                }
            }
        }
        if (!empty($map_things['things'])) {
            $map_things_things = unserialize($map_things['things']);
            foreach ($map_things_things as $map_things_thing) {
                if ($map_things_thing['expire'] > time() and !empty($map_things_thing['thing_id'])) {
                    $thing = Helpers::getThingRowByThingId($map_things_thing['thing_id']);
                    if ($thing->is_no_drop) {
                        $things[] = '「' . $thing->name . '」';
                    } else {
                        $things[] = $thing->name;
                    }
                    $things_num++;
                    if ($things_num > 15) {
                        break;
                    }
                }
            }
        }
        if (!empty($map_things['xinfas'])) {
            $map_things_xinfas = unserialize($map_things['xinfas']);
            foreach ($map_things_xinfas as $map_things_xinfa) {
                if ($map_things_xinfa['expire'] > time() and !empty($map_things_xinfa['xinfa_id'])) {
                    $xinfa = Helpers::getXinfaRowByXinfaId($map_things_xinfa['xinfa_id']);
                    $things[] = $xinfa->name;
                    $things_num++;
                    if ($things_num > 15) {
                        break;
                    }
                }
            }
        }

        /**
         * 地图事件
         */
        if (!empty($map->actions)){
            $actions = json_decode($map->actions, true);
        }else{
            $actions = [];
        }


        /**
         * 地图描述
         */
        if (!empty($map->description)){
            $descriptions = json_decode($map->description, true);
        }else{
            $descriptions = [];
        }


        /**
         * 获取四个方向
         */
        [$north, $west, $east, $south] = [
            !empty($map->north_map_id) ? Helpers::getMapRowByMapId($map->north_map_id) : false,
            !empty($map->west_map_id) ? Helpers::getMapRowByMapId($map->west_map_id) : false,
            !empty($map->east_map_id) ? Helpers::getMapRowByMapId($map->east_map_id) : false,
            !empty($map->south_map_id) ? Helpers::getMapRowByMapId($map->south_map_id) : false,
        ];
        if ($north) $north->moveUrl = 'Map/Index/move/' . $north->id;
        if ($west) $west->moveUrl = 'Map/Index/move/' . $west->id;
        if ($east) $east->moveUrl = 'Map/Index/move/' . $east->id;
        if ($south) $south->moveUrl = 'Map/Index/move/' . $south->id;

        return $connection->send(\cache_response($request, \view('Map/Index/index.twig', [
            'request'          => $request,
            'map'              => $map,
            'north'            => $north,
            'west'             => $west,
            'east'             => $east,
            'south'            => $south,
            'actions'          => $actions,
            'descriptions'     => $descriptions,
            'chars'            => $chars,
            'messages'         => $messages,
            'map_messages'     => $map_messages,
            'things'           => $things,
            'come_footprints'  => $footprints_come,
            'leave_footprints' => $footprints_leave,
            'broadcasts'       => $broadcasts,
        ])));
    }


    /**
     * 行走
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $map_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function move(TcpConnection $connection, Request $request, int $map_id)
    {
        $map = Helpers::getMapRowByMapId($request->roleRow->map_id);

        /**
         * 名字检测
         */
        if ($map->id == 8) {
            if ($request->roleRow->name == '无名氏') {
                return $connection->send(\cache_response($request, \view('Base/message.twig', [
                    'request' => $request,
                    'message' => 'Trên giang hồ nhưng không lưu vô danh hạng người, đại hiệp vẫn là đi trước lấy cái tên đi.',
                ])));
            }
        }

        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);

        /**
         * 检测负重
         */
        if ($role_attrs->weight >= 100000000) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ba lô phụ trọng đã đạt tới hạn mức cao nhất, vô pháp di động.',
            ])));
        }

        /**
         * 获取所有怪物
         */
        if ($map->is_allow_fight) {
            if ($role_attrs->reviveTimestamp < time()) {
                $npcs = cache()->mget(Helpers::getMapNpcs($request->roleRow->map_id));
                if ($npcs) {
                    $npcs = array_column(array_filter($npcs, function ($npc) {
                        if (empty($npc)) return false; else return true;
                    }), null, 'npcId');
                    foreach ($npcs as $npc) {
                        if (($npc->guardNorth and $map->north_map_id == $map_id) or
                            ($npc->guardWest and $map->west_map_id == $map_id) or
                            ($npc->guardEast and $map->east_map_id == $map_id) or
                            ($npc->guardSouth and $map->south_map_id == $map_id)) {
                            $npc_row = Helpers::getNpcRowByNpcId($npc->npcId);
                            $dialogues = json_decode($npc_row->dialogues, true);
                            if (is_array($dialogues) and count($dialogues) > 0) {
                                $message = $npc->name . 'Ngăn cản ngươi đường đi:「' . $dialogues[array_rand($dialogues)] . '」';
                            } else {
                                $message = $npc->name . 'Ngăn cản ngươi đường đi.';
                            }
                            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                                'request' => $request,
                                'message' => $message,
                            ])));
                        }
                    }
                }
            }
        }

        /**
         * 连续任务
         */
        if (!empty($request->roleRow->mission)) {
            $mission = json_decode($request->roleRow->mission);
            if ($mission->circle == 6) {
                /**
                 * 打探消息任务、判断是否完成
                 */
                if (!$mission->status and $mission->mapId == $map_id) {
                    /**
                     * 完成任务
                     */
                    $mission->status = true;
                    $request->roleRow->mission = json_encode($mission, JSON_UNESCAPED_UNICODE);
                    $messages = [];
                    $messages[] = 'Ngươi thật cẩn thận bắt đầu tra xét phụ cận tình huống, quả nhiên như Bách Hiểu Sinh lời nói, có không ít hành tung quỷ dị giang hồ nhân sĩ……';
                    $messages[] = 'Ngươi hoàn thành liên tục nhiệm vụ, mau trở về tìm Bách Hiểu Sinh hội báo tình huống, lãnh thưởng đi!';
                    Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                    cache()->rPush('role_messages_' . $request->roleId, ...$messages);
                }
            }
        }

        /**
         * 地图足迹
         */
        if ($role_attrs->reviveTimestamp > time()) {
            $role_name = $request->roleRow->name . 'Quỷ hồn';
        } else {
            $role_name = $request->roleRow->name;
        }
        if ($map_id == $map->north_map_id) {
            $message = $role_name . 'Hướng Bắc rời đi.';
        } elseif ($map_id == $map->west_map_id) {
            $message = $role_name . 'Hướng Tây rời đi.';
        } elseif ($map_id == $map->east_map_id) {
            $message = $role_name . 'Hướng Đông rời đi.';
        } else {
            $message = $role_name . 'Hướng Nam rời đi.';
        }

        $pipeline = cache()->pipeline();
        $pipeline->rPush('map_footprints_for_leave_' . $request->roleRow->map_id, $message);
        $pipeline->rPush('map_footprints_for_come_' . $map_id, $role_name . '走了过来。');
        $pipeline->exec();

        return $this->delivery($connection, $request, $map_id);
    }


    /**
     * 传送
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $map_id
     *
     * @return bool|null
     */
    public function delivery(TcpConnection $connection, Request $request, int $map_id)
    {
        /**
         * 判断是否在监狱
         */
        if ($request->roleRow->map_id === 757) {
            if ($request->roleRow->release_time > time()) {
                cache()->rPush('role_messages_' . $request->roleId, 'Ngục tốt: “Trở về cho ta hảo hảo đợi!”');
                return $this->index($connection, $request);
            }
            $messages = [
                'Ngươi bị hai cái ngục tốt giá đi ra đại lao……',
                'Ngục tốt đối với ngươi nói: Hảo, ngươi có thể đi rồi. Nếu là còn dám phạm án, hừ……',
            ];
            $request->roleRow->red = 0;
            $request->roleRow->release_time = 0;
            cache()->rPush('role_messages_' . $request->roleId, ...$messages);

            cache()->sRem('map_roles_' . $request->roleRow->map_id, $request->roleId);
            $request->roleRow->map_id = 6;
            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
            cache()->sAdd('map_roles_' . $request->roleRow->map_id, $request->roleId);
            return $this->index($connection, $request);
        }
        /**
         * 删除地图玩家自身记录、添加自己到新地图玩家记录
         */
        cache()->sRem('map_roles_' . $request->roleRow->map_id, $request->roleId);
        $request->roleRow->map_id = $map_id;
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        cache()->sAdd('map_roles_' . $request->roleRow->map_id, $request->roleId);

        return $this->index($connection, $request);
    }
}
