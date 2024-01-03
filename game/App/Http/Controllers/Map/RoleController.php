<?php

namespace App\Http\Controllers\Map;

use App\Core\Configs\GameConfig;
use App\Libs\Helpers;
use App\Libs\Objects\RoleRow;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 地图玩家操作
 *
 */
class RoleController
{
    /**
     * 加好友
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_id
     *
     * @return bool|null
     */
    public function follow(TcpConnection $connection, Request $request, int $role_id)
    {
        $role_row = Helpers::getRoleRowByRoleId($role_id);
        if (empty($role_row)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người chơi đang offline',
            ])));
        }
        if (empty($request->roleRow->follows)) {
            $follows = [];
        } else {
            $follows = json_decode($request->roleRow->follows, true);
        }
        $follows[] = $role_row->id;
        $follows = array_unique($follows);
        $request->roleRow->follows = json_encode($follows);
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        $sql = <<<SQL
UPDATE `roles` SET `follows` = '{$request->roleRow->follows}' WHERE `id` = $request->roleId;
SQL;


        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Map/Role/follow.twig', [
            'request'  => $request,
            'role_row' => $role_row,
        ])));
    }


    /**
     * 移除好友
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_id
     *
     * @return bool|null
     */
    public function unfollow(TcpConnection $connection, Request $request, int $role_id)
    {
        if (empty($request->roleRow->follows)) {
            $follows = [];
        } else {
            $follows = json_decode($request->roleRow->follows, true);
        }
        $role_row = Helpers::getRoleRowByRoleId($role_id);
        if (empty($role_row)) {
            $sql = <<<SQL
SELECT * FROM `roles` WHERE `id` = $role_id;
SQL;

            $role_row_st = db()->query($sql);
            $role_row = $role_row_st->fetchObject(RoleRow::class);
            $role_row_st->closeCursor();
        }
        $follows = array_unique($follows);
        if (in_array($role_id, $follows)) {
            $key = array_search($role_id, $follows);
            unset($follows[$key]);
            $follows = array_unique($follows);
        }
        $request->roleRow->follows = json_encode($follows);
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        $sql = <<<SQL
UPDATE `roles` SET `follows` = '{$request->roleRow->follows}' WHERE `id` = $request->roleId;
SQL;


        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Map/Role/unfollow.twig', [
            'request'  => $request,
            'role_row' => $role_row,
        ])));
    }


    /**
     * Chặn
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_id
     *
     * @return bool|null
     */
    public function block(TcpConnection $connection, Request $request, int $role_id)
    {
        /**
         * 判断是否已经在Danh sách bạn bè里面
         *
         */
        if (empty($request->roleRow->follows)) {
            $follows = [];
        } else {
            $follows = json_decode($request->roleRow->follows, true);
        }
        $follows = array_unique($follows);
        if (in_array($role_id, $follows)) {
            $key = array_search($role_id, $follows);
            unset($follows[$key]);
            $follows = array_unique($follows);
            $request->roleRow->follows = json_encode($follows);
            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
            $sql = <<<SQL
UPDATE `roles` SET `follows` = '{$request->roleRow->follows}' WHERE `id` = $request->roleId;
SQL;


            Helpers::execSql($sql);

        }

        /**
         * 加入黑名单
         *
         */
        if (empty($request->roleRow->blocks)) {
            $blocks = [];
        } else {
            $blocks = json_decode($request->roleRow->blocks, true);
        }
        $role_row = Helpers::getRoleRowByRoleId($role_id);
        if (empty($role_row)) {
            $sql = <<<SQL
SELECT * FROM `roles` WHERE `id` = $role_id;
SQL;

            $role_row_st = db()->query($sql);
            $role_row = $role_row_st->fetchObject(RoleRow::class);
            $role_row_st->closeCursor();
        }
        $blocks[] = $role_row->id;
        $blocks = array_unique($blocks);
        $request->roleRow->blocks = json_encode($blocks);
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        $sql = <<<SQL
UPDATE `roles` SET `blocks` = '{$request->roleRow->blocks}' WHERE `id` = $request->roleId;
SQL;


        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Map/Role/block.twig', [
            'request'  => $request,
            'role_row' => $role_row,
        ])));
    }


    /**
     * 取消Chặn
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_id
     *
     * @return bool|null
     */
    public function unblock(TcpConnection $connection, Request $request, int $role_id)
    {
        if (empty($request->roleRow->blocks)) {
            $blocks = [];
        } else {
            $blocks = json_decode($request->roleRow->blocks, true);
        }
        $role_row = Helpers::getRoleRowByRoleId($role_id);
        if (empty($role_row)) {
            $sql = <<<SQL
SELECT * FROM `roles` WHERE `id` = $role_id;
SQL;

            $role_row_st = db()->query($sql);
            $role_row = $role_row_st->fetchObject(RoleRow::class);
            $role_row_st->closeCursor();
        }
        $blocks = array_unique($blocks);
        if (in_array($role_id, $blocks)) {
            $key = array_search($role_id, $blocks);
            unset($blocks[$key]);
            $blocks = array_unique($blocks);
        }
        $request->roleRow->blocks = json_encode($blocks);
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        $sql = <<<SQL
UPDATE `roles` SET `blocks` = '{$request->roleRow->blocks}' WHERE `id` = $request->roleId;
SQL;


        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Map/Role/unblock.twig', [
            'request'  => $request,
            'role_row' => $role_row,
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
    public function view(TcpConnection $connection, Request $request, int $role_id)
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
        $role_title = '【' . Helpers::getTitle($role_row->sect_id, $role_attrs->experience) . '】';
        $role_title .= ($role_row->sect_id > 0) ? Helpers::getSect($role_row->sect_id) . 'Đệ' .
            Helpers::getHansNumber($role_row->seniority) . 'đệ tử' : '';

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
        if (is_array($blocks)) {
            if (!in_array($role_id, $blocks)) {
                $follows = json_decode($request->roleRow->follows, true);
                if (in_array($role_id, $follows)) {
                    $unfollow_url = 'Map/Role/unfollow/' . $role_id;
                } else {
                    $follow_uel = 'Map/Role/follow/' . $role_id;
                }
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
        return $connection->send(\cache_response($request, \view('Map/Role/view.twig', [
            'request'            => $request,
            'role_attrs'         => $role_attrs,
            'role_row'           => $role_row,
            'equipments'         => $equipments,
            'role_age'           => intdiv($role_row->age / 70 / 3600, 10) * 10,
            'role_title'         => $role_title,
            'follow_url'         => $follow_uel ?? null,
            'unfollow_url'       => $unfollow_url ?? null,
            'ta'                 => $role_row->gender == '男' ? ' hắn ': ' nàng ',
            'send_url'           => 'Map/Role/message/' . $role_row->id,
            'kill_url'           => 'Map/Battlefield/playerStartKill/' . $role_row->id,
            'duel_url'           => 'Map/Battlefield/playerStartDuel/' . $role_row->id,
            'trade_url'          => 'Func/Transaction/start/' . $role_row->id,
            'give_url'           => 'Func/Give/start/' . $role_row->id,
            'wugong_description' => Helpers::getWugongDescription($role_attrs->comprehensiveSkillLv),
            'attack_description' => Helpers::getAttackDescription($role_attrs->attack),
            'status_description' => Helpers::getStatusDescription($role_attrs->hp, $role_attrs->maxHp),
            // 'vip'                => $vip,
        ])));
    }


    /**
     * 发信息
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_id
     *
     * @return bool|null
     */
    public function message(TcpConnection $connection, Request $request, int $role_id)
    {
        /**
         * 获取 玩家
         */
        $role_row = Helpers::getRoleRowByRoleId($role_id);
        if (empty($role_row)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người chơi đang offline，Không thể nhận tin nhắn',
            ])));
        }
        return $connection->send(\cache_response($request, \view('Map/Role/message.twig', [
            'request'  => $request,
            'role_row' => $role_row,
            'send_url' => 'Map/Role/messagePost/' . $role_id,
        ])));
    }


    /**
     * 发送私信 提交
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_id
     *
     * @return bool|null
     */
    public function messagePost(TcpConnection $connection, Request $request, int $role_id)
    {
        if (strtoupper($request->method() != 'POST')) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $content = $request->post('content');
        if (empty($content)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->experience / 1000 < 100) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Bạn không thể gửi tin nhắn riêng nếu trình độ tu luyện của bạn dưới 100 năm!',
            ])));
        }
        if (!preg_match('#^[\x{4e00}-\x{9fa5}，！。、；《》【】‘’：“”（）？+…—\da-zA-Z]{1,64}$#u', $content)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Nội dung trò chuyện không được chứa các ký tự đặc biệt! Chỉ cho phép ký tự tiếng Trung, dấu câu tiếng Trung, chữ cái và số và độ dài là 1~64。',
            ])));
        }
        /**
         * 获取 玩家
         */
        $role_row = Helpers::getRoleRowByRoleId($role_id);
        if (empty($role_row)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Người chơi đang offline，Không thể nhận tin nhắn',
            ])));
        }

        /**
         * 查询是否在黑名单
         *
         */
        if (empty($role_row->blocks)) {
            $blocks = [];
        } else {
            $blocks = json_decode($role_row->blocks, true);
        }
        if (in_array($request->roleId, $blocks)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => $role_row->name . 'Đã từ chối tin nhắn riêng tư của bạn.',
            ])));
        }

        /**
         * 查询是否拒绝陌生人
         *
         */
        if ($role_row->switch_stranger == 0) {
            if (empty($role_row->follows)) {
                $follows = [];
            } else {
                $follows = json_decode($role_row->follows, true);
            }
            if (!in_array($request->roleId, $follows)) {
                return $connection->send(\cache_response($request, \view('Base/message.twig', [
                    'request' => $request,
                    'message' => $role_row->name . 'Đã từ chối tin nhắn riêng tư của bạn.',
                ])));
            }
        }


        $sql = <<<SQL
SELECT * FROM `chat_logs` WHERE `kind` = 6 AND `sender_id` = $request->roleId ORDER BY `id` DESC LIMIT 1;
SQL;

        $chat_log = Helpers::queryFetchObject($sql);

        if ($chat_log and $chat_log->timestamp + 30 > time()) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Bạn không thể gửi lại tin nhắn riêng tư trong vòng ba mươi giây!',
            ])));
        }

        /**
         * 发送私信、推送消息给对方、增加对方魅力
         *
         */
        $timestamp = time();

        $sql = <<<SQL
UPDATE `roles` SET `charm` = `charm` + 1 WHERE `id` = $role_row->id;
INSERT INTO `chat_logs` (`sender_id`, `receiver_id`, `content`, `timestamp`, `kind`) VALUES ($request->roleId, $role_row->id, '$content', $timestamp, 6);
SQL;


        Helpers::execSql($sql);

        cache()->rPush('role_broadcast_' . $role_row->id, ['kind' => 6, 'content' => $request->roleRow->name . 'Đối với ngươi nói:' . $content,]);
        $role_row = Helpers::getRoleRowByRoleId($role_row->id);
        Helpers::setRoleRowByRoleId($role_id, $role_row);
        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => 'Ngươi đối ' . $role_row->name . ' nói: ' . $content,
        ])));
    }
}
