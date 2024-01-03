<?php

namespace App\Http\Controllers\Map;

use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 战场
 *
 */
class BattlefieldController
{
    public function npcStartDuel(TcpConnection $connection, Request $request, string $map_npc_id)
    {
        /**
         * 判断当前地图是否允许战斗
         *
         */
        $map = Helpers::getMapRowByMapId($request->roleRow->map_id);
        if (!$map->is_allow_fight) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Không được phép đánh nhau ở đây!',
            ])));
        }

        /**
         * 检测是否存在
         */
        $npc_attrs = Helpers::getMapNpcAttrsByMapNpcId($map_npc_id);
        if (empty($npc_attrs)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Nhân vật đã biến mất!',
            ])));
        }
        if (Helpers::getPercent($npc_attrs->hp, $npc_attrs->maxHp) < 50) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => $npc_attrs->name . 'Tôi không muốn chấp nhận cuộc đấu của bạn!',
            ])));
        }

        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->reviveTimestamp > time()) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Nếu bạn vẫn còn trong trạng thái ma, đừng gây rắc rối.',
            ])));
        }
        if (Helpers::getPercent($role_attrs->hp, $role_attrs->maxHp) < 50) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => $npc_attrs->name . 'Tôi không muốn chấp nhận cuộc đấu của bạn!',
            ])));
        }

        /**
         * 获取战场信息
         *
         */
        $battlefield = cache()->hMGet('role_battlefield_' . $request->roleId, ['b1_state', 'b2_state', 'b3_state',]);

        /**
         * 建立战场
         *
         */
        if (!$battlefield['b1_state']) {
            $i = 1;
        } elseif (!$battlefield['b2_state']) {
            $i = 2;
        } elseif (!$battlefield['b3_state']) {
            $i = 3;
        } else {
            $i = 0;
        }
        if ($i > 0) {
            cache()->hMSet('role_battlefield_' . $request->roleId, [
                'id'                 => 'role_battlefield_' . $request->roleId,
                'role_id'            => $request->roleId,
                'b' . $i . '_state'  => true,
                'b' . $i . '_object' => 2,
                'b' . $i . '_id'     => $map_npc_id,
                'b' . $i . '_kind'   => 2,
                'b' . $i . '_form'   => 1,
                'b' . $i . '_action' => false,
            ]);
        }
        $footprints = cache()->lRange('map_footprints_for_come_' . $request->roleRow->map_id, -5, -1);
        $footprints = Helpers::clearMyselfFootprint($footprints, $request);
        return $connection->send(\cache_response($request, \view('Map/Battlefield/npcStartDuel.twig', [
            'request'    => $request,
            'npc_attrs'  => $npc_attrs,
            'footprints' => $footprints,
        ])));
    }


    /**
     * 发起Giết chóc NPC
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param string        $map_npc_id
     *
     * @return bool|null
     */
    public function npcStartKill(TcpConnection $connection, Request $request, string $map_npc_id)
    {
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->reviveTimestamp > time()) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Nếu bạn vẫn còn trong trạng thái ma, đừng gây rắc rối.',
            ])));
        }

        /**
         * 检测是否允许战斗
         *
         */
        $map = Helpers::getMapRowByMapId($request->roleRow->map_id);
        if (!$map->is_allow_fight) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Không được phép đánh nhau ở đây!',
            ])));
        }

        /**
         * 检测是否存在
         */
        $npc_attrs = Helpers::getMapNpcAttrsByMapNpcId($map_npc_id);
        if (empty($npc_attrs)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Nhân vật đã biến mất!',
            ])));
        }



        /**
         * 获取战场信息
         *
         */
        $battlefield = cache()->hMGet('role_battlefield_' . $request->roleId, ['b1_state', 'b2_state', 'b3_state',]);

        /**
         * 建立战场
         *
         */
        if (!$battlefield['b1_state']) {
            $i = 1;
        } elseif (!$battlefield['b2_state']) {
            $i = 2;
        } elseif (!$battlefield['b3_state']) {
            $i = 3;
        } else {
            $i = 0;
        }
        if ($i > 0) {
            cache()->hMSet('role_battlefield_' . $request->roleId, [
                'id'                 => 'role_battlefield_' . $request->roleId,
                'role_id'            => $request->roleId,
                'b' . $i . '_state'  => true,
                'b' . $i . '_object' => 2,
                'b' . $i . '_id'     => $map_npc_id,
                'b' . $i . '_kind'   => 1,
                'b' . $i . '_form'   => 1,
                'b' . $i . '_action' => false,
            ]);
        }

        $footprints = cache()->lRange('map_footprints_for_come_' . $request->roleRow->map_id, -5, -1);
        $footprints = Helpers::clearMyselfFootprint($footprints, $request);
        return $connection->send(\cache_response($request, \view('Map/Battlefield/npcStartKill.twig', [
            'request'    => $request,
            'npc_attrs'  => $npc_attrs,
            'footprints' => $footprints,
        ])));
    }


    /**
     * 战斗状态
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function state(TcpConnection $connection, Request $request)
    {
        /**
         * 获取战斗消息
         *
         */
        $messages = cache()->lRange('role_messages_' . $request->roleId, 0, -1);
        //Helpers::log_message(var_export($messages,true));
        cache()->lTrim('role_messages_' . $request->roleId, 1, 0);

        /**
         * 判断战场是否存在
         *
         */
        $battlefield = cache()->hMGet('role_battlefield_' . $request->roleId, [
            'id', 'role_id',
            'b1_state', 'b1_object', 'b1_id', 'b1_kind', 'b1_form',
            'b2_state', 'b2_object', 'b2_id', 'b2_kind', 'b2_form',
            'b3_state', 'b3_object', 'b3_id', 'b3_kind', 'b3_form',
        ]);

        if ($battlefield['b1_state']) {
            $i = 1;
        } elseif ($battlefield['b2_state']) {
            $i = 2;
        } elseif ($battlefield['b3_state']) {
            $i = 3;
        } else {
            $i = 0;
        }
        if ($i === 0) {
            if (!empty($messages)) {
                cache()->rPush('role_messages_' . $request->roleId, ...$messages);
            }
            return (new IndexController())->index($connection, $request);
        }

        /**
         * 判断 NPC 或者 Role 是否存在
         *
         */
        if ($battlefield['b' . $i . '_object'] == 1) {
            $object = Helpers::getRoleRowByRoleId($battlefield['b' . $i . '_id']);
        } else {
            $object = Helpers::getMapNpcAttrsByMapNpcId($battlefield['b' . $i . '_id']);
        }
        if (empty($object)) {
            cache()->hSet('role_battlefield_' . $request->roleId, 'b' . $i . '_state', false);
            return $this->state($connection, $request);
        }

        /**
         * 获取足迹
         *
         */
        $footprints = cache()->lRange('map_footprints_for_come_' . $request->roleRow->map_id, -5, -1);
        $footprints = Helpers::clearMyselfFootprint($footprints, $request);

        $broadcasts = cache()->lRange('role_broadcast_' . $request->roleId, 0, -1);
        if (!empty($broadcasts)) {
            cache()->lTrim('role_broadcast_' . $request->roleId, 1, 0);
        }
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        //Helpers::log_message("角色属性:".var_export($role_attrs,true));
        return $connection->send(\cache_response($request, \view('Map/Battlefield/state.twig', [
            'request'    => $request,
            'messages'   => $messages,
            'status'     => Helpers::getStatusDescription($role_attrs->hp, $role_attrs->maxHp),
            'object'     => $object,
            'footprints' => $footprints,
            'broadcasts' => $broadcasts,
        ])));
    }


    /**
     * Xem xét技能招式
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function skill(TcpConnection $connection, Request $request)
    {
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->sectSkillId === 0) {
            return $connection->send(\cache_response($request, \view('Map/Battlefield/skill.twig', [
                'request' => $request,
                'message' => 'Bạn chưa cấu hình bất kỳ kỹ năng nào!',
            ])));
        }
        if ($role_attrs->sectSkillLv < 5) {
            return $connection->send(\cache_response($request, \view('Map/Battlefield/skill.twig', [
                'request' => $request,
                'message' => 'Bạn không có chiêu nào để sử dụng!',
            ])));
        }
        $skill = Helpers::getSkillRowBySkillId($role_attrs->sectSkillId);
        $tricks = [];
        $levels = [5, 10, 20, 40, 80, 120, 160, 180, 240, 300, 360, 420, 480, 700, 1000];
        foreach ($levels as $level) {
            $name = 'lv' . $level . '_name';
            if ($skill->$name && $level <= $role_attrs->sectSkillLv) {
                $tricks[] = [
                    'lv'        => $level,
                    'name'      => $skill->$name,
                    'selectUrl' => 'Map/Battlefield/skillSelect/' . $skill->id . '/' . $level,
                ];
            } else {
                break;
            }
        }
        $tricks = array_reverse($tricks);
        return $connection->send(\cache_response($request, \view('Map/Battlefield/skill.twig', [
            'request' => $request,
            'tricks'  => $tricks,
        ])));
    }


    /**
     * 选择技能招式
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $skill_id
     * @param int           $skill_level
     *
     * @return bool|null
     */
    public function skillSelect(TcpConnection $connection, Request $request, int $skill_id, int $skill_level)
    {
        /**
         * 获取战斗状态
         *
         */
        $battlefield = cache()->hMGet('role_battlefield_' . $request->roleId, ['b1_state', 'b2_state', 'b3_state']);
        //Helpers::log_message(var_export($battlefield,true));
        $states = [];
        if ($battlefield['b1_state']) $states[] = 1;
        if ($battlefield['b2_state']) $states[] = 2;
        if ($battlefield['b3_state']) $states[] = 3;
        $count = count($states);
        //Helpers::log_message("battlefield count ".$count);
        if ($count > 0) {
            $index = time() % $count;
            cache()->hSet('role_battlefield_' . $request->roleId, 'b' . $states[$index] . '_action', json_encode([
                'kind'        => 1,
                'skill_id'    => $skill_id,
                'skill_level' => $skill_level,
            ]));
        }
        return $this->state($connection, $request);
    }


    /**
     * Tâm Pháp
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function xinfa(TcpConnection $connection, Request $request)
    {
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->attackXinfaId === 0) {
            return $connection->send(\cache_response($request, \view('Map/Battlefield/xinfa.twig', [
                'request' => $request,
                'message' => 'Bạn không có kỹ năng tấn công',
            ])));
        }
        $xinfa_tricks = Helpers::getXinfaAttackTrick($role_attrs->attackXinfaId);
        $tricks = [];
        $levels = [0, 40, 80, 160, 240, 400, 560, 720, 880, 1000,];
        foreach ($levels as $level) {
            $name = 'lv' . $level . '_name';
            if ($xinfa_tricks->$name && $xinfa_tricks->$name !== '无' && $level <= $role_attrs->attackXinfaLv) {
                $tricks[] = [
                    'lv'        => $level,
                    'name'      => $xinfa_tricks->$name,
                    'selectUrl' => 'Map/Battlefield/xinfaSelect/' . $xinfa_tricks->xinfa_id . '/' . $level,
                ];
            } else {
                break;
            }
        }
        $tricks = array_reverse($tricks);
        return $connection->send(\cache_response($request, \view('Map/Battlefield/xinfa.twig', [
            'request' => $request,
            'tricks'  => $tricks,
        ])));
    }


    /**
     * 选择心法招式
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $xinfa_id
     * @param int           $xinfa_level
     *
     * @return bool|null
     */
    public function xinfaSelect(TcpConnection $connection, Request $request, int $xinfa_id, int $xinfa_level)
    {
        /**
         * 获取战斗状态
         *
         */
        $battlefield = cache()->hMGet('role_battlefield_' . $request->roleId, ['b1_state', 'b2_state', 'b3_state']);
        $states = [];
        if ($battlefield['b1_state']) $states[] = 1;
        if ($battlefield['b2_state']) $states[] = 2;
        if ($battlefield['b3_state']) $states[] = 3;
        $count = count($states);
        if ($count > 0) {
            $index = time() % $count;
            cache()->hSet('role_battlefield_' . $request->roleId, 'b' . $states[$index] . '_action', json_encode([
                'kind'        => 2,
                'xinfa_id'    => $xinfa_id,
                'xinfa_level' => $xinfa_level,
            ]));
        }
        return $this->state($connection, $request);
    }


    /**
     * Đầu hàng
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function surrender(TcpConnection $connection, Request $request)
    {
        $battlefield = cache()->hMGet('role_battlefield_' . $request->roleId, ['b1_state', 'b2_state', 'b3_state']);
        $states = [];
        if ($battlefield['b1_state']) $states[] = 1;
        if ($battlefield['b2_state']) $states[] = 2;
        if ($battlefield['b3_state']) $states[] = 3;
        $count = count($states);
        if ($count > 0) {
            $index = time() % $count;
            cache()->hSet('role_battlefield_' . $request->roleId, 'b' . $states[$index] . '_action', json_encode([
                'kind' => 3,
            ]));
        }
        return $this->state($connection, $request);
    }


    /**
     * Chạy trốn
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function escape(TcpConnection $connection, Request $request)
    {
        $battlefield = cache()->hMGet('role_battlefield_' . $request->roleId, ['b1_state', 'b2_state', 'b3_state']);
        $states = [];
        if ($battlefield['b1_state']) $states[] = 1;
        if ($battlefield['b2_state']) $states[] = 2;
        if ($battlefield['b3_state']) $states[] = 3;
        $count = count($states);
        if ($count > 0) {
            $index = time() % $count;
            cache()->hSet('role_battlefield_' . $request->roleId, 'b' . $states[$index] . '_action', json_encode([
                'kind' => 4,
            ]));
        }
        return $this->state($connection, $request);
    }


    /**
     * Giết chóc玩家
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_id
     *
     * @return bool|null
     */
    public function playerStartKill(TcpConnection $connection, Request $request, int $role_id) : ?bool{
        /**
         * 检测是否允许战斗
         *
         */
        $map = Helpers::getMapRowByMapId($request->roleRow->map_id);
        if (!$map->is_allow_fight) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Không được phép đánh nhau ở đây!',
            ])));
        }

        /**
         * 检测人物是否在线
         *
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
            return $connection->send(\cache_response($request, \view('Map/View/message.twig', [
                'request' => $request,
                'message' => ($role_row->gender == '男' ? ' hắn ': ' nàng ') . 'Rời khỏi!',
            ])));
        }
        if ($role_attrs->reviveTimestamp > time()) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Đối phương đã là quỷ rồi, sao còn có tâm tư công kích?',
            ])));
        }

        $m_role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($m_role_attrs->reviveTimestamp > time()) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Nếu bạn vẫn còn trong trạng thái ma, đừng gây rắc rối.',
            ])));
        }
        if ($m_role_attrs->experience / 1000 < 50) {
            return $connection->send(\cache_response($request, \view('Map/View/message.twig', [
                'request' => $request,
                'message' => 'Bạn muốn hành động dù chưa có kinh nghiệm trên thế giới? Chúng ta hãy chờ thêm một thời gian nữa.',
            ])));
        }

        if ($role_attrs->experience / 1000 < 50) {
            return $connection->send(\cache_response($request, \view('Map/View/message.twig', [
                'request' => $request,
                'message' => 'Đối phương mới đến thế giới, sao có thể nỡ giết hắn?',
            ])));
        }
        if ($role_row->no_kill > time()) {
            return $connection->send(\cache_response($request, \view('Map/View/message.twig', [
                'request' => $request,
                'message' => 'Đối thủ có huy chương vàng không chết và không thể giết.',
            ])));
        }
        if ($request->roleRow->no_kill > time()) {
            return $connection->send(\cache_response($request, \view('Map/View/message.twig', [
                'request' => $request,
                'message' => 'Bạn có huy chương không chết và không thể tham gia giết chóc.',
            ])));
        }


        /**
         * 获取我的战场信息
         *
         */
        $battlefield = cache()->hMGet('role_battlefield_' . $request->roleId, ['b1_state', 'b2_state', 'b3_state',]);

        /**
         * 获取 Ta 的战场信息
         */
        $o_battlefield = cache()->hMGet('role_battlefield_' . $role_id, ['b1_state', 'b2_state', 'b3_state',]);

        /**
         * 建立战场
         *
         */
        if (!$battlefield['b1_state']) {
            $i = 1;
        } elseif (!$battlefield['b2_state']) {
            $i = 2;
        } elseif (!$battlefield['b3_state']) {
            $i = 3;
        } else {
            $i = 0;
        }
        if (!$o_battlefield['b1_state']) {
            $o_i = 1;
        } elseif (!$o_battlefield['b2_state']) {
            $o_i = 2;
        } elseif (!$o_battlefield['b3_state']) {
            $o_i = 3;
        } else {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => $role_row->name . 'Anh ta đang bị ba người giết chết, vì vậy tốt hơn hết là đừng lợi dụng sự nguy hiểm của người khác.',
            ])));
        }
        if ($i > 0) {
            cache()->hMSet('role_battlefield_' . $request->roleId, [
                'id'                 => 'role_battlefield_' . $request->roleId,
                'role_id'            => $request->roleId,
                'b' . $i . '_state'  => true,
                'b' . $i . '_object' => 1,
                'b' . $i . '_id'     => $role_id,
                'b' . $i . '_kind'   => 1,
                'b' . $i . '_form'   => 1,
                'b' . $i . '_action' => false,
            ]);
            cache()->hMSet('role_battlefield_' . $role_id, [
                'id'                   => 'role_battlefield_' . $role_id,
                'role_id'              => $role_id,
                'b' . $o_i . '_state'  => true,
                'b' . $o_i . '_object' => 1,
                'b' . $o_i . '_id'     => $request->roleId,
                'b' . $o_i . '_kind'   => 1,
                'b' . $o_i . '_form'   => 2,
                'b' . $o_i . '_action' => false,
            ]);
            $message = $request->roleRow->name . 'Đối với ngươi uống nói：「' . ($role_row->gender == '男' ? 'Xú tặc' : 'Tiểu tiện nhân') . '!Hôm nay không phải ngươi chết chính là ta sống！」';
            cache()->rPush('role_messages_' . $role_id, $message);
        }

        $footprints = cache()->lRange('map_footprints_for_come_' . $request->roleRow->map_id, -5, -1);
        $footprints = Helpers::clearMyselfFootprint($footprints, $request);
        return $connection->send(\cache_response($request, \view('Map/Battlefield/playerStartKill.twig', [
            'request'    => $request,
            'role_row'   => $role_row,
            'footprints' => $footprints,
        ])));
    }


    /**
     * Luận bàn玩家
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_id
     *
     * @return bool|null
     */
    public function playerStartDuel(TcpConnection $connection, Request $request, int $role_id)
    {
        /**
         * 判断当前地图是否允许战斗
         *
         */
        $map = Helpers::getMapRowByMapId($request->roleRow->map_id);
        if (!$map->is_allow_fight) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Không được phép đánh nhau ở đây!',
            ])));
        }

        /**
         * 检测人物是否在线
         *
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
            return $connection->send(\cache_response($request, \view('Map/View/message.twig', [
                'request' => $request,
                'message' => ($role_row->gender == '男' ? 'Hắn' : 'Nàng') . 'Rời khỏi!',
            ])));
        }

        if ($role_attrs->reviveTimestamp > time()) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Đối phương đã là quỷ rồi, sao còn có tâm tư công kích?',
            ])));
        }

        $m_role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($m_role_attrs->reviveTimestamp > time()) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Nếu bạn vẫn còn trong trạng thái ma, đừng gây rắc rối.',
            ])));
        }
        if ($m_role_attrs->experience / 1000 < 50) {
            return $connection->send(\cache_response($request, \view('Map/View/message.twig', [
                'request' => $request,
                'message' => 'Bạn muốn hành động dù chưa có kinh nghiệm trên thế giới? Chúng ta hãy chờ thêm một thời gian nữa.',
            ])));
        }

        if ($role_attrs->experience / 1000 < 50) {
            return $connection->send(\cache_response($request, \view('Map/View/message.twig', [
                'request' => $request,
                'message' => 'Đối phương mới đến thế giới, sao có thể nỡ giết hắn?',
            ])));
        }

        if (Helpers::getPercent($role_attrs->hp, $role_attrs->maxHp) < 50) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => $role_row->name . 'Tôi không muốn chấp nhận cuộc đấu của bạn!',
            ])));
        }

        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if (Helpers::getPercent($role_attrs->hp, $role_attrs->maxHp) < 50) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => $role_row->name . 'Tôi không muốn chấp nhận cuộc đấu của bạn!',
            ])));
        }


        /**
         * 获取我的战场信息
         *
         */
        $battlefield = cache()->hMGet('role_battlefield_' . $request->roleId, ['b1_state', 'b2_state', 'b3_state',]);

        /**
         * 获取 Ta 的战场信息
         */
        $o_battlefield = cache()->hMGet('role_battlefield_' . $role_id, ['b1_state', 'b2_state', 'b3_state',]);

        /**
         * 建立战场
         *
         */
        if (!$battlefield['b1_state']) {
            $i = 1;
        } elseif (!$battlefield['b2_state']) {
            $i = 2;
        } elseif (!$battlefield['b3_state']) {
            $i = 3;
        } else {
            $i = 0;
        }
        if (!$o_battlefield['b1_state']) {
            $o_i = 1;
        } elseif (!$o_battlefield['b2_state']) {
            $o_i = 2;
        } elseif (!$o_battlefield['b3_state']) {
            $o_i = 3;
        } else {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => $role_row->name . 'Đang ở bị ba người giết chóc, đại hiệp vẫn là không cần nhân lúc cháy nhà mà đi hôi của.',
            ])));
        }

        if ($i > 0) {
            cache()->hMSet('role_battlefield_' . $request->roleId, [
                'id'                 => 'role_battlefield_' . $request->roleId,
                'role_id'            => $request->roleId,
                'b' . $i . '_state'  => true,
                'b' . $i . '_object' => 1,
                'b' . $i . '_id'     => $role_id,
                'b' . $i . '_kind'   => 2,
                'b' . $i . '_form'   => 1,
                'b' . $i . '_action' => false,
            ]);
            cache()->hMSet('role_battlefield_' . $role_id, [
                'id'                   => 'role_battlefield_' . $role_id,
                'role_id'              => $role_id,
                'b' . $o_i . '_state'  => true,
                'b' . $o_i . '_object' => 1,
                'b' . $o_i . '_id'     => $request->roleId,
                'b' . $o_i . '_kind'   => 2,
                'b' . $o_i . '_form'   => 2,
                'b' . $o_i . '_action' => false,
            ]);
        }

        $footprints = cache()->lRange('map_footprints_for_come_' . $request->roleRow->map_id, -5, -1);
        $footprints = Helpers::clearMyselfFootprint($footprints, $request);
        return $connection->send(\cache_response($request, \view('Map/Battlefield/playerStartDuel.twig', [
            'request'    => $request,
            'role_row'   => $role_row,
            'footprints' => $footprints,
        ])));
    }
}
