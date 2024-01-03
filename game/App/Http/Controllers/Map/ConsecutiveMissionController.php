<?php

namespace App\Http\Controllers\Map;

use App\Libs\Attrs\ConsecutiveMission;
use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 连续任务
 */
class ConsecutiveMissionController
{
    /**
     * 连续任务入口 接任务 提交任务
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        /**
         * 判断是否有任务
         */
        $mission = json_decode($request->roleRow->mission);
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);

        if (empty($mission)) {
            $mission = new ConsecutiveMission();
            /**
             * 初始 Cho任务
             */
            $mission->times += 1;
            $mission->circle = 1;
            $_ = Helpers::getConsecutiveMissionKillNpc($mission->times, $role_attrs->maxSkillLv);
            $mission->npcId = $_['npc_id'];
            $mission->mapId = $_['map_id'];
            $mission->regionName = Helpers::getRegion(Helpers::getMapRowByMapId($mission->mapId)->region_id);
            $mission->expireTimestamp = time() + 15 * 60;

            /**
             * 保存任务
             */
            $request->roleRow->mission = json_encode($mission, JSON_UNESCAPED_UNICODE);
            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
            $sql = <<<SQL
UPDATE `roles` SET `mission` = '{$request->roleRow->mission}' WHERE `id` = $request->roleId;
SQL;

            Helpers::execSql($sql);

            /**
             * 第一次接任务的页面
             */
            return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/receive.twig', [
                'request' => $request,
                'mission' => $mission,
                'npc'     => Helpers::getNpcRowByNpcId($mission->npcId),
                'map'     => Helpers::getMapRowByMapId($mission->mapId),
            ])));
        }

        /**
         * 判断是否需要验证
         */
        if ($mission->times > 0 and $mission->times % 10 == 0 and $mission->verified == false) {
            return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/verifyTip.twig', [
                'request' => $request,
            ])));
        }

        $role_gain_qianneng = intval($mission->times / 2 + 55);
        $role_gain_experience = intval(20 * $mission->times + 200);
        $role_gain_money = intval((2.5 * $mission->times + 50) * 100);

        if ($role_gain_qianneng > 500) {
            $role_gain_qianneng = mt_rand(250, 499);
        }
        if ($role_gain_experience > 20000) {
            $role_gain_experience = mt_rand(10000, 19999);
        }
        if ($role_gain_money > 200000) {
            $role_gain_money = mt_rand(100000, 199999);
        }

        /**
         * 判断任务是否完成
         */
        if ($mission->circle == 1) {
            /**
             * 击杀怪物任务
             */
            if ($mission->status) {
                /**
                 * Cho奖励
                 */
                $role_attrs->experience += $role_gain_experience;
                $role_attrs->qianneng += $role_gain_qianneng;
                $role_attrs->qianneng = $role_attrs->qianneng > $role_attrs->maxQianneng ? $role_attrs->maxQianneng : $role_attrs->qianneng;
                $greet = 'Thật là ngượng ngùng phiền toái ngươi nhiều như vậy, đa tạ ngươi giúp ta ra khẩu khí này, đây là cho ngươi khen thưởng.';
                $gain = 'Ngươi được đến ' . Helpers::getHansExperience($role_gain_experience) . ' Tu vi,' . Helpers::getHansNumber($role_gain_qianneng) . 'Điểm tiềm năng!';
                Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);

                /**
                 * 判断任务是否超时
                 */
                if ($mission->expireTimestamp < time()) {
                    /**
                     * 保存任务状态
                     */
                    $request->roleRow->mission = null;
                    Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                    $sql = <<<SQL
UPDATE `roles` SET `mission` = null WHERE `id` = $request->roleId;
SQL;

                    Helpers::execSql($sql);
                    return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/index.twig', [
                        'request'   => $request,
                        'mission'   => $mission,
                        'completed' => true,
                        'greet'     => $greet,
                        'gain'      => $gain,
                        'stop'      => true,
                    ])));
                }

                /**
                 * 判断是否出现验证
                 */
                if ($mission->times > 0 and $mission->times % 10 == 0) {
                    $mission->verified = false;
                    /**
                     * 保存任务状态
                     */
                    $request->roleRow->mission = json_encode($mission, JSON_UNESCAPED_UNICODE);
                    Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                    $sql = <<<SQL
UPDATE `roles` SET `mission` = '{$request->roleRow->mission}' WHERE `id` = $request->roleId;
SQL;

                    Helpers::execSql($sql);
                    return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/index.twig', [
                        'request'   => $request,
                        'mission'   => $mission,
                        'completed' => true,
                        'greet'     => $greet,
                        'gain'      => $gain,
                        'verify'    => true,
                    ])));
                }

                /**
                 * Cho下一个任务
                 */
                $mission->expireTimestamp = time() + 15 * 60;
                $mission->times += 1;
                $mission->circle += 1;
                $mission->gemKind = mt_rand(1, 4);
                $mission->gemNumber = Helpers::getConsecutiveMissionGem($mission->times);
                $mission->gemGainNumber = 0;

                /**
                 * 保存任务状态
                 */
                $request->roleRow->mission = json_encode($mission, JSON_UNESCAPED_UNICODE);
                Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                $sql = <<<SQL
UPDATE `roles` SET `mission` = '{$request->roleRow->mission}' WHERE `id` = $request->roleId;
SQL;

                Helpers::execSql($sql);
                return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/index.twig', [
                    'request'   => $request,
                    'mission'   => $mission,
                    'completed' => true,
                    'greet'     => $greet,
                    'gain'      => $gain,
                    'receive'   => true,
                    'gem_name'  => [1 => 'Mã não', 2 => 'Phỉ thúy', 3 => 'Nhân sâm', 4 => 'Ngọc bội'][$mission->gemKind],
                ])));
            } else {
                /**
                 * 显示当前任务状态
                 */
                return $this->view($connection, $request);
            }
        } elseif ($mission->circle == 2) {
            /**
             * 收集宝石任务
             */
            if ($mission->gemNumber <= $mission->gemGainNumber) {
                /**
                 * Cho奖励
                 */
                $role_attrs->experience += $role_gain_experience;
                $role_attrs->qianneng += $role_gain_qianneng;
                $role_attrs->qianneng = $role_attrs->qianneng > $role_attrs->maxQianneng ? $role_attrs->maxQianneng : $role_attrs->qianneng;
                $greet = 'Thật là ngượng ngùng phiền toái ngươi nhiều như vậy, đa tạ ngươi giúp ta ra khẩu khí này, đây là cho ngươi khen thưởng.';
                $gain = 'Ngươi được đến ' . Helpers::getHansExperience($role_gain_experience) . ' Tu vi,' . Helpers::getHansNumber($role_gain_qianneng) . 'điểm nội lực，Bạch ngân' . Helpers::getHansNumber(intdiv($role_gain_money, 100)) . '两！';
                Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);

                $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

                $role_money = Helpers::queryFetchObject($sql);

                if ($role_money) {
                    $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` + $role_gain_money WHERE `id` = $role_money->id;
SQL;

                } else {
                    $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, 213, $role_gain_money);
SQL;

                }

                Helpers::execSql($sql);


                /**
                 * 判断任务是否超时
                 */
                if ($mission->expireTimestamp < time()) {
                    /**
                     * 保存任务状态
                     */
                    $request->roleRow->mission = null;
                    Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                    $sql = <<<SQL
UPDATE `roles` SET `mission` = null WHERE `id` = $request->roleId;
SQL;

                    Helpers::execSql($sql);
                    return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/index.twig', [
                        'request'   => $request,
                        'mission'   => $mission,
                        'completed' => true,
                        'greet'     => $greet,
                        'gain'      => $gain,
                        'stop'      => true,
                    ])));
                }

                /**
                 * 判断是否出现验证
                 */
                if ($mission->times > 0 and $mission->times % 10 == 0) {
                    $mission->verified = false;
                    /**
                     * 保存任务状态
                     */
                    $request->roleRow->mission = json_encode($mission, JSON_UNESCAPED_UNICODE);
                    Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                    $sql = <<<SQL
UPDATE `roles` SET `mission` = '{$request->roleRow->mission}' WHERE `id` = $request->roleId;
SQL;

                    Helpers::execSql($sql);
                    return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/index.twig', [
                        'request'   => $request,
                        'mission'   => $mission,
                        'completed' => true,
                        'greet'     => $greet,
                        'gain'      => $gain,
                        'verify'    => true,
                    ])));
                }

                /**
                 * Cho下一个任务
                 */
                $mission->expireTimestamp = time() + 15 * 60;
                $mission->times += 1;
                $mission->circle += 1;
                $_ = Helpers::getConsecutiveMissionNpc();
                $mission->npcId = $_['npc_id'];
                $mission->mapId = $_['map_id'];
                $mission->regionName = Helpers::getRegion(Helpers::getMapRowByMapId($mission->mapId)->region_id);
                $mission->status = false;

                /**
                 * 保存任务状态
                 */
                $request->roleRow->mission = json_encode($mission, JSON_UNESCAPED_UNICODE);
                Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                $sql = <<<SQL
UPDATE `roles` SET `mission` = '{$request->roleRow->mission}' WHERE `id` = $request->roleId;
SQL;

                Helpers::execSql($sql);
                return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/index.twig', [
                    'request'   => $request,
                    'mission'   => $mission,
                    'completed' => true,
                    'greet'     => $greet,
                    'gain'      => $gain,
                    'receive'   => true,
                    'map'       => Helpers::getMapRowByMapId($mission->mapId),
                    'npc'       => Helpers::getNpcRowByNpcId($mission->npcId),
                ])));
            } else {
                /**
                 * 显示当前任务状态
                 */
                return $this->view($connection, $request);
            }
        } elseif ($mission->circle == 3) {
            /**
             * Đối thoại任务
             */
            if ($mission->status) {
                /**
                 * Cho奖励
                 */
                $role_attrs->experience += $role_gain_experience;
                $role_attrs->qianneng += $role_gain_qianneng;
                $role_attrs->qianneng = $role_attrs->qianneng > $role_attrs->maxQianneng ? $role_attrs->maxQianneng : $role_attrs->qianneng;
                $greet = 'Đa tạ vị này đại hiệp, thật là ngượng ngùng phiền toái ngươi nhiều như vậy, đây là cho ngươi khen thưởng.';
                $gain = 'Ngươi được đến ' . Helpers::getHansExperience($role_gain_experience) . ' Tu vi,' . Helpers::getHansNumber($role_gain_qianneng) . 'điểm nội lực，Bạch ngân' . Helpers::getHansNumber(intdiv($role_gain_money, 100)) . '两！';
                Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);

                $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

                $role_money = Helpers::queryFetchObject($sql);

                if ($role_money) {
                    $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` + $role_gain_money WHERE `id` = $role_money->id;
SQL;

                } else {
                    $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, 213, $role_gain_money);
SQL;

                }

                Helpers::execSql($sql);


                /**
                 * 判断任务是否超时
                 */
                if ($mission->expireTimestamp < time()) {
                    /**
                     * 保存任务状态
                     */
                    $request->roleRow->mission = null;
                    Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                    $sql = <<<SQL
UPDATE `roles` SET `mission` = null WHERE `id` = $request->roleId;
SQL;

                    Helpers::execSql($sql);
                    return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/index.twig', [
                        'request'   => $request,
                        'mission'   => $mission,
                        'completed' => true,
                        'greet'     => $greet,
                        'gain'      => $gain,
                        'stop'      => true,
                    ])));
                }

                /**
                 * 判断是否出现验证
                 */
                if ($mission->times > 0 and $mission->times % 10 == 0) {
                    $mission->verified = false;
                    /**
                     * 保存任务状态
                     */
                    $request->roleRow->mission = json_encode($mission, JSON_UNESCAPED_UNICODE);
                    Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                    $sql = <<<SQL
UPDATE `roles` SET `mission` = '{$request->roleRow->mission}' WHERE `id` = $request->roleId;
SQL;

                    Helpers::execSql($sql);
                    return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/index.twig', [
                        'request'   => $request,
                        'mission'   => $mission,
                        'completed' => true,
                        'greet'     => $greet,
                        'gain'      => $gain,
                        'verify'    => true,
                    ])));
                }

                /**
                 * Cho下一个任务
                 */
                $mission->expireTimestamp = time() + 15 * 60;
                $mission->times += 1;
                $mission->circle += 1;
                $_ = Helpers::getConsecutiveMissionKillNpc($mission->times, $role_attrs->maxSkillLv);
                $mission->npcId = $_['npc_id'];
                $mission->mapId = $_['map_id'];
                $mission->regionName = Helpers::getRegion(Helpers::getMapRowByMapId($mission->mapId)->region_id);
                $mission->status = false;

                /**
                 * 保存任务状态
                 */
                $request->roleRow->mission = json_encode($mission, JSON_UNESCAPED_UNICODE);
                Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                $sql = <<<SQL
UPDATE `roles` SET `mission` = '{$request->roleRow->mission}' WHERE `id` = $request->roleId;
SQL;

                Helpers::execSql($sql);
                return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/index.twig', [
                    'request'   => $request,
                    'mission'   => $mission,
                    'completed' => true,
                    'greet'     => $greet,
                    'gain'      => $gain,
                    'receive'   => true,
                    'map'       => Helpers::getMapRowByMapId($mission->mapId),
                    'npc'       => Helpers::getNpcRowByNpcId($mission->npcId),
                ])));
            } else {
                /**
                 * 显示当前任务状态
                 */
                return $this->view($connection, $request);
            }
        } elseif ($mission->circle == 4) {
            /**
             * 击杀怪物任务
             */
            if ($mission->status) {
                /**
                 * Cho奖励
                 */
                $role_attrs->experience += $role_gain_experience;
                $role_attrs->qianneng += $role_gain_qianneng;
                $role_attrs->qianneng = $role_attrs->qianneng > $role_attrs->maxQianneng ? $role_attrs->maxQianneng : $role_attrs->qianneng;
                $greet = 'Thật là ngượng ngùng phiền toái ngươi nhiều như vậy, đa tạ ngươi giúp ta ra khẩu khí này, đây là cho ngươi khen thưởng.';
                $gain = 'Ngươi được đến ' . Helpers::getHansExperience($role_gain_experience) . ' Tu vi,' . Helpers::getHansNumber($role_gain_qianneng) . 'điểm nội lực';

                /**
                 * 判断是否给装备
                 */
                if (Helpers::getProbability(50, 100)) {
                    if (Helpers::getProbability(1, 1000000)) {
                        $equipment_thing_id = Helpers::getConsecutiveMissionRewardNoDropEquipment();
                        $thing = Helpers::getThingRowByThingId($equipment_thing_id);
                        $gain .= '，一' . $thing->unit . '「' . $thing->name . '」';
                        $timestamp = time();
                        $content = 'Chúc mừng người chơi ' . $request->roleRow->name . ' Hoàn thành liên tục nhiệm vụ đạt được một ' . $thing->unit . '「' . $thing->name . '」。';
                        $sql = <<<SQL
INSERT INTO `chat_logs` (`content`, `timestamp`, `kind`) VALUES ('$content', $timestamp, 4);
SQL;

                        Helpers::execSql($sql);
                        /**
                         * 获取在线
                         */
                        $roles = cache()->keys('role_row_*');
                        if (!empty($roles)) {
                            $roles = cache()->mget($roles);
                            if (!empty($roles)) {
                                $cont = $content;
                                $broadcast = cache()->pipeline();
                                foreach ($roles as $role) {
                                    if (!empty($role)) {
                                        if ($role->switch_jianghu) {
                                            $broadcast->rPush('role_broadcast_' . $role->id,
                                                ['kind' => 4, 'content' => $cont,]);
                                        }
                                    }
                                }
                                $broadcast->exec();
                            }
                        }
                    } else {
                        $equipment_thing_id = Helpers::getConsecutiveMissionRewardEquipment($mission->times);
                        $thing = Helpers::getThingRowByThingId($equipment_thing_id);
                        $gain .= '，一' . $thing->unit . $thing->name;
                    }
                    $status = mt_rand(1, 4);
                    $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`, `status`, `durability`) VALUES ($request->roleId, $equipment_thing_id, 1, $status, $thing->max_durability);
SQL;


                    Helpers::execSql($sql);

                }
                $gain .= '！';

                Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);


                /**
                 * 判断任务是否超时
                 */
                if ($mission->expireTimestamp < time()) {
                    /**
                     * 保存任务状态
                     */
                    $request->roleRow->mission = null;
                    Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                    $sql = <<<SQL
UPDATE `roles` SET `mission` = null WHERE `id` = $request->roleId;
SQL;

                    Helpers::execSql($sql);
                    return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/index.twig', [
                        'request'   => $request,
                        'mission'   => $mission,
                        'completed' => true,
                        'greet'     => $greet,
                        'gain'      => $gain,
                        'stop'      => true,
                    ])));
                }

                /**
                 * 判断是否出现验证
                 */
                if ($mission->times > 0 and $mission->times % 10 == 0) {
                    $mission->verified = false;
                    /**
                     * 保存任务状态
                     */
                    $request->roleRow->mission = json_encode($mission, JSON_UNESCAPED_UNICODE);
                    Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                    $sql = <<<SQL
UPDATE `roles` SET `mission` = '{$request->roleRow->mission}' WHERE `id` = $request->roleId;
SQL;

                    Helpers::execSql($sql);
                    return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/index.twig', [
                        'request'   => $request,
                        'mission'   => $mission,
                        'completed' => true,
                        'greet'     => $greet,
                        'gain'      => $gain,
                        'verify'    => true,
                    ])));
                }

                /**
                 * Cho下一个任务
                 */
                $mission->expireTimestamp = time() + 15 * 60;
                $mission->times += 1;
                $mission->circle += 1;
                $mission->gemKind = mt_rand(1, 4);
                $mission->gemNumber = Helpers::getConsecutiveMissionGem($mission->times);
                $mission->gemGainNumber = 0;

                /**
                 * 保存任务状态
                 */
                $request->roleRow->mission = json_encode($mission, JSON_UNESCAPED_UNICODE);
                Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                $sql = <<<SQL
UPDATE `roles` SET `mission` = '{$request->roleRow->mission}' WHERE `id` = $request->roleId;
SQL;

                Helpers::execSql($sql);
                return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/index.twig', [
                    'request'   => $request,
                    'mission'   => $mission,
                    'completed' => true,
                    'greet'     => $greet,
                    'gain'      => $gain,
                    'receive'   => true,
                    'gem_name'  => [1 => 'Mã não', 2 => 'Phỉ thúy', 3 => 'Nhân sâm', 4 => 'Ngọc bội'][$mission->gemKind],
                ])));
            } else {
                /**
                 * 显示当前任务状态
                 */
                return $this->view($connection, $request);
            }
        } elseif ($mission->circle == 5) {
            /**
             * 收集宝石任务
             */
            if ($mission->gemNumber <= $mission->gemGainNumber) {
                /**
                 * Cho奖励
                 */
                $role_attrs->experience += $role_gain_experience;
                $role_attrs->qianneng += $role_gain_qianneng;
                $role_attrs->qianneng = $role_attrs->qianneng > $role_attrs->maxQianneng ? $role_attrs->maxQianneng : $role_attrs->qianneng;
                $greet = 'Thật là ngượng ngùng phiền toái ngươi nhiều như vậy, đa tạ ngươi giúp ta ra khẩu khí này, đây là cho ngươi khen thưởng.';
                $gain = 'Ngươi được đến ' . Helpers::getHansExperience($role_gain_experience) . ' Tu vi,' . Helpers::getHansNumber($role_gain_qianneng) . 'điểm nội lực，Bạch ngân' . Helpers::getHansNumber(intdiv($role_gain_money, 100)) . '两！';
                Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);

                $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

                $role_money = Helpers::queryFetchObject($sql);

                if ($role_money) {
                    $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` + $role_gain_money WHERE `id` = $role_money->id;
SQL;

                } else {
                    $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, 213, $role_gain_money);
SQL;

                }

                Helpers::execSql($sql);


                /**
                 * 判断任务是否超时
                 */
                if ($mission->expireTimestamp < time()) {
                    /**
                     * 保存任务状态
                     */
                    $request->roleRow->mission = null;
                    Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                    $sql = <<<SQL
UPDATE `roles` SET `mission` = null WHERE `id` = $request->roleId;
SQL;

                    Helpers::execSql($sql);
                    return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/index.twig', [
                        'request'   => $request,
                        'mission'   => $mission,
                        'completed' => true,
                        'greet'     => $greet,
                        'gain'      => $gain,
                        'stop'      => true,
                    ])));
                }

                /**
                 * 判断是否出现验证
                 */
                if ($mission->times > 0 and $mission->times % 10 == 0) {
                    $mission->verified = false;
                    /**
                     * 保存任务状态
                     */
                    $request->roleRow->mission = json_encode($mission, JSON_UNESCAPED_UNICODE);
                    Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                    $sql = <<<SQL
UPDATE `roles` SET `mission` = '{$request->roleRow->mission}' WHERE `id` = $request->roleId;
SQL;

                    Helpers::execSql($sql);
                    return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/index.twig', [
                        'request'   => $request,
                        'mission'   => $mission,
                        'completed' => true,
                        'greet'     => $greet,
                        'gain'      => $gain,
                        'verify'    => true,
                    ])));
                }

                /**
                 * Cho下一个任务
                 */
                $mission->expireTimestamp = time() + 15 * 60;
                $mission->times += 1;
                $mission->circle += 1;
                $mission->mapId = Helpers::getConsecutiveMissionMap();
                $mission->regionName = Helpers::getRegion(Helpers::getMapRowByMapId($mission->mapId)->region_id);
                $mission->status = false;

                /**
                 * 保存任务状态
                 */
                $request->roleRow->mission = json_encode($mission, JSON_UNESCAPED_UNICODE);
                Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                $sql = <<<SQL
UPDATE `roles` SET `mission` = '{$request->roleRow->mission}' WHERE `id` = $request->roleId;
SQL;

                Helpers::execSql($sql);
                return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/index.twig', [
                    'request'   => $request,
                    'mission'   => $mission,
                    'completed' => true,
                    'greet'     => $greet,
                    'gain'      => $gain,
                    'receive'   => true,
                    'map'       => Helpers::getMapRowByMapId($mission->mapId),
                ])));
            } else {
                /**
                 * 显示当前任务状态
                 */
                return $this->view($connection, $request);
            }
        } elseif ($mission->circle == 6) {
            /**
             * 打探消息
             */
            if ($mission->status) {
                /**
                 * Cho奖励
                 */
                $role_attrs->experience += $role_gain_experience;
                $role_attrs->qianneng += $role_gain_qianneng;
                $role_attrs->qianneng = $role_attrs->qianneng > $role_attrs->maxQianneng ? $role_attrs->maxQianneng : $role_attrs->qianneng;
                $greet = 'Đa tạ vị này đại hiệp, thật là ngượng ngùng phiền toái ngươi nhiều như vậy, đây là cho ngươi khen thưởng.';
                $gain = 'Ngươi được đến ' . Helpers::getHansExperience($role_gain_experience) . ' Tu vi,' . Helpers::getHansNumber($role_gain_qianneng) . 'Điểm tiềm năng!';
                Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);

                /**
                 * 判断任务是否超时
                 */
                if ($mission->expireTimestamp < time()) {
                    /**
                     * 保存任务状态
                     */
                    $request->roleRow->mission = null;
                    Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                    $sql = <<<SQL
UPDATE `roles` SET `mission` = null WHERE `id` = $request->roleId;
SQL;

                    Helpers::execSql($sql);
                    return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/index.twig', [
                        'request'   => $request,
                        'mission'   => $mission,
                        'completed' => true,
                        'greet'     => $greet,
                        'gain'      => $gain,
                        'stop'      => true,
                    ])));
                }

                /**
                 * 判断是否出现验证
                 */
                if ($mission->times > 0 and $mission->times % 10 == 0) {
                    $mission->verified = false;
                    /**
                     * 保存任务状态
                     */
                    $request->roleRow->mission = json_encode($mission, JSON_UNESCAPED_UNICODE);
                    Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                    $sql = <<<SQL
UPDATE `roles` SET `mission` = '{$request->roleRow->mission}' WHERE `id` = $request->roleId;
SQL;

                    Helpers::execSql($sql);
                    return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/index.twig', [
                        'request'   => $request,
                        'mission'   => $mission,
                        'completed' => true,
                        'greet'     => $greet,
                        'gain'      => $gain,
                        'verify'    => true,
                    ])));
                }

                /**
                 * Cho下一个任务
                 */
                $mission->expireTimestamp = time() + 15 * 60;
                $mission->times += 1;
                $mission->circle += 1;
                $mission->equipmentThingId = Helpers::getConsecutiveMissionEquipment($mission->times);

                /**
                 * 保存任务状态
                 */
                $request->roleRow->mission = json_encode($mission, JSON_UNESCAPED_UNICODE);
                Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                $sql = <<<SQL
UPDATE `roles` SET `mission` = '{$request->roleRow->mission}' WHERE `id` = $request->roleId;
SQL;

                Helpers::execSql($sql);
                return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/index.twig', [
                    'request'   => $request,
                    'mission'   => $mission,
                    'completed' => true,
                    'greet'     => $greet,
                    'gain'      => $gain,
                    'receive'   => true,
                    'thing'     => Helpers::getThingRowByThingId($mission->equipmentThingId),
                ])));
            } else {
                /**
                 * 显示当前任务状态
                 */
                return $this->view($connection, $request);
            }
        } elseif ($mission->circle == 7) {
            /**
             * Xem xét是否已经拥有
             */
            $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = $mission->equipmentThingId AND `equipped` = 0;
SQL;

            $role_thing = Helpers::queryFetchObject($sql);
            if ($role_thing) {
                /**
                 * Cho奖励
                 */
                $role_attrs->experience += $role_gain_experience;
                $role_attrs->qianneng += $role_gain_qianneng;
                $role_attrs->qianneng = $role_attrs->qianneng > $role_attrs->maxQianneng ? $role_attrs->maxQianneng : $role_attrs->qianneng;
                $greet = 'Ngươi đem thần binh giao cho Bách Hiểu Sinh, Bách Hiểu Sinh cười đối với ngươi nói: Không tồi, không tồi, vất vả. Đây là cho ngươi khen thưởng.';
                $gain = 'Ngươi được đến ' . Helpers::getHansExperience($role_gain_experience) . ' Tu vi,' . Helpers::getHansNumber($role_gain_qianneng) . 'điểm nội lực';

                /**
                 * 判断是否给心法
                 */
                if (Helpers::getProbability(25, 100)) {
                    $probability = mt_rand(1, 100000000);
                    if ($probability <= 50000000) {
                        $xinfa_id = Helpers::getConsecutiveMissionXinfa(0);
                    } elseif ($probability <= 97000000) {
                        $xinfa_id = Helpers::getConsecutiveMissionXinfa(8);
                    } elseif ($probability <= 99990000) {
                        $xinfa_id = Helpers::getConsecutiveMissionXinfa(64);
                    } elseif ($probability <= 99999990) {
                        $xinfa_id = Helpers::getConsecutiveMissionXinfa(128);
                    } elseif ($probability <= 99999999) {
                        $xinfa_id = Helpers::getConsecutiveMissionXinfa(216);
                    } else {
                        $xinfa_id = Helpers::getConsecutiveMissionXinfa(512);
                    }

                    $xinfa = Helpers::getXinfaRowByXinfaId($xinfa_id);
                    $gain .= '，一本《' . $xinfa->name . '》';

                    if (in_array($xinfa->experience, [128, 216, 512])) {
                        $timestamp = time();
                        $content = 'Chúc mừng người chơi ' . $request->roleRow->name . ' Hoàn thành liên tục nhiệm vụ đạt được một 本《' . $xinfa->name . '》心法。';
                        $sql = <<<SQL
INSERT INTO `chat_logs` (`content`, `timestamp`, `kind`) VALUES ('$content', $timestamp, 4);
SQL;

                        Helpers::execSql($sql);
                        /**
                         * 获取在线
                         */
                        $roles = cache()->keys('role_row_*');
                        if (!empty($roles)) {
                            $roles = cache()->mget($roles);
                            if (!empty($roles)) {
                                $cont = $content;
                                $broadcast = cache()->pipeline();
                                foreach ($roles as $role) {
                                    if (!empty($role)) {
                                        if ($role->switch_jianghu) {
                                            $broadcast->rPush('role_broadcast_' . $role->id,
                                                ['kind' => 4, 'content' => $cont,]);
                                        }
                                    }
                                }
                                $broadcast->exec();
                            }
                        }
                    }
                    /**
                     * 判断心法是否已满
                     */
                    $sql = <<<SQL
SELECT `id` FROM `role_xinfas` WHERE `role_id` = $request->roleId;
SQL;

                    $role_xinfas = Helpers::queryFetchAll($sql);

                    $sql = '';
                    if (is_array($role_xinfas) and count($role_xinfas) >= 10) {
                        $gain .= ',Ngươi tâm pháp ba lô đã mãn, nhiệm vụ khen thưởng ' . $xinfa->name . 'Vô pháp phát đến ngươi tâm pháp ba lô, đã bị hệ thống thu về';
                    } else {
                        if ($xinfa->experience == 0) {
                            $base_experience = 2;
                        } elseif ($xinfa->experience == 8) {
                            $base_experience = mt_rand(2, 3);
                        } elseif ($xinfa->experience == 64) {
                            $base_experience = mt_rand(3, 4);
                        } elseif ($xinfa->experience == 128) {
                            $base_experience = mt_rand(3, 5);
                        } elseif ($xinfa->experience == 216) {
                            $base_experience = mt_rand(5, 6);
                        } else {
                            $base_experience = mt_rand(6, 8);
                        }
                        $max_lv = mt_rand(40, 80);
                        $time = time();
                        $sql .= <<<SQL
INSERT INTO `role_xinfas` (`role_id`, `xinfa_id`, `base_experience`, `lv`, `max_lv`) VALUES ($request->roleId, $xinfa_id, $base_experience, 1, $max_lv);
INSERT INTO `role_xinfa_logs` (`role_id`, `xinfa_id`, `timestamp`) VALUES ($request->roleId, $xinfa_id, $time);
SQL;

                    }
                    $sql .= <<<SQL
DELETE FROM `role_things` WHERE `id` = $role_thing->id;
SQL;

                    Helpers::execSql($sql);

                } else {
                    $sql .= <<<SQL
DELETE FROM `role_things` WHERE `id` = $role_thing->id;
SQL;


                    Helpers::execSql($sql);

                }
                $gain .= '！';

                Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);


                /**
                 * 判断任务是否超时
                 */
                if ($mission->expireTimestamp < time()) {
                    /**
                     * 保存任务状态
                     */
                    $request->roleRow->mission = null;
                    Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                    $sql = <<<SQL
UPDATE `roles` SET `mission` = null WHERE `id` = $request->roleId;
SQL;

                    Helpers::execSql($sql);
                    return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/index.twig', [
                        'request'   => $request,
                        'mission'   => $mission,
                        'completed' => true,
                        'greet'     => $greet,
                        'gain'      => $gain,
                        'stop'      => true,
                    ])));
                }

                /**
                 * 判断是否出现验证
                 */
                if ($mission->times > 0 and $mission->times % 10 == 0) {
                    $mission->verified = false;
                    /**
                     * 保存任务状态
                     */
                    $request->roleRow->mission = json_encode($mission, JSON_UNESCAPED_UNICODE);
                    Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                    $sql = <<<SQL
UPDATE `roles` SET `mission` = '{$request->roleRow->mission}' WHERE `id` = $request->roleId;
SQL;

                    Helpers::execSql($sql);
                    return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/index.twig', [
                        'request'   => $request,
                        'mission'   => $mission,
                        'completed' => true,
                        'greet'     => $greet,
                        'gain'      => $gain,
                        'verify'    => true,
                    ])));
                }

                /**
                 * Cho下一个任务
                 */
                $mission->expireTimestamp = time() + 15 * 60;
                $mission->times += 1;
                $mission->circle = 1;
                $_ = Helpers::getConsecutiveMissionKillNpc($mission->times, $role_attrs->maxSkillLv);
                $mission->npcId = $_['npc_id'];
                $mission->mapId = $_['map_id'];
                $mission->regionName = Helpers::getRegion(Helpers::getMapRowByMapId($mission->mapId)->region_id);
                $mission->status = false;

                /**
                 * 保存任务状态
                 */
                $request->roleRow->mission = json_encode($mission, JSON_UNESCAPED_UNICODE);
                Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                $sql = <<<SQL
UPDATE `roles` SET `mission` = '{$request->roleRow->mission}' WHERE `id` = $request->roleId;
SQL;

                Helpers::execSql($sql);
                return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/index.twig', [
                    'request'   => $request,
                    'mission'   => $mission,
                    'completed' => true,
                    'greet'     => $greet,
                    'gain'      => $gain,
                    'receive'   => true,
                    'map'       => Helpers::getMapRowByMapId($mission->mapId),
                    'npc'       => Helpers::getNpcRowByNpcId($mission->npcId),
                ])));
            } else {
                /**
                 * 显示当前任务状态
                 */
                return $this->view($connection, $request);
            }
        }

        return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/index.twig', [
            'request' => $request,
        ])));
    }


    /**
     * Làm mới nhiệm vụ
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function change(TcpConnection $connection, Request $request)
    {
        /**
         * Xem xét账户
         */
        if ($request->roleRow->bank_balance < 10000) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi ở tiền trang tiền tiết kiệm không đủ.',
            ])));
        }
        $request->roleRow->bank_balance -= 10000;
        $sql = <<<SQL
UPDATE `roles` SET `bank_balance` = `bank_balance` - 10000 WHERE `id` = $request->roleId;
SQL;


        Helpers::execSql($sql);


        /**
         * 获取当前任务
         */
        $mission = json_decode($request->roleRow->mission);

        /**
         * 判断任务是否过期
         */
        if ($mission->expireTimestamp < time()) {
            /**
             * 过期 档次重置 环数清零
             */
            $mission->times = 1;
            $mission->circle = 1;
        }
        $mission->expireTimestamp = time() + 15 * 60;
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($mission->circle == 1 or $mission->circle == 4) {
            $_ = Helpers::getConsecutiveMissionKillNpc($mission->times, $role_attrs->maxSkillLv);
            $mission->npcId = $_['npc_id'];
            $mission->mapId = $_['map_id'];
            $mission->regionName = Helpers::getRegion(Helpers::getMapRowByMapId($mission->mapId)->region_id);
            $mission->status = false;
        } elseif ($mission->circle == 2 or $mission->circle == 5) {
            $mission->gemNumber = Helpers::getConsecutiveMissionGem($mission->times);
            $mission->gemGainNumber = 0;
            $mission->gemKind = mt_rand(1, 4);
        } elseif ($mission->circle == 3) {
            $_ = Helpers::getConsecutiveMissionNpc();
            $mission->npcId = $_['npc_id'];
            $mission->mapId = $_['map_id'];
            $mission->regionName = Helpers::getRegion(Helpers::getMapRowByMapId($mission->mapId)->region_id);
            $mission->status = false;
        } elseif ($mission->circle == 6) {
            $mission->mapId = Helpers::getConsecutiveMissionMap();
            $mission->regionName = Helpers::getRegion(Helpers::getMapRowByMapId($mission->mapId)->region_id);
            $mission->status = false;
        } elseif ($mission->circle == 7) {
            $mission->equipmentThingId = Helpers::getConsecutiveMissionEquipment($mission->times);
        }

        /**
         * 保存任务
         */
        $request->roleRow->mission = json_encode($mission, JSON_UNESCAPED_UNICODE);
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        $sql = <<<SQL
UPDATE `roles` SET `mission` = '{$request->roleRow->mission}' WHERE `id` = $request->roleId;
SQL;

        Helpers::execSql($sql);
        return $this->view($connection, $request);
    }


    /**
     * Xem xét任务
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function view(TcpConnection $connection, Request $request)
    {
        /**
         * 获取当前任务
         */
        $mission = json_decode($request->roleRow->mission);

        /**
         * 判断任务是否过期
         */
        if ($mission->expireTimestamp < time()) {
            $expired = true;
        } else {
            $expired = false;
        }

        if ($mission->circle == 1 or $mission->circle == 4) {
            $completed = $mission->status;
            $npc = Helpers::getNpcRowByNpcId($mission->npcId);
            $map = Helpers::getMapRowByMapId($mission->mapId);
        } elseif ($mission->circle == 2 or $mission->circle == 5) {
            $gem_name = [1 => 'Mã não', 2 => 'Phỉ thúy', 3 => 'Nhân sâm', 4 => 'Ngọc bội'][$mission->gemKind];
            $gem_number = $mission->gemNumber - $mission->gemGainNumber;
            if ($gem_number < 1) {
                $completed = true;
            } else {
                $completed = false;
            }
        } elseif ($mission->circle == 3) {
            $completed = $mission->status;
            $npc = Helpers::getNpcRowByNpcId($mission->npcId);
            $map = Helpers::getMapRowByMapId($mission->mapId);
        } elseif ($mission->circle == 6) {
            $completed = $mission->status;
            $map = Helpers::getMapRowByMapId($mission->mapId);
        } elseif ($mission->circle == 7) {
            $thing = Helpers::getThingRowByThingId($mission->equipmentThingId);
            /**
             * Xem xét是否已经拥有
             */
            $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = $mission->equipmentThingId AND `equipped` = 0;
SQL;

            $role_thing = Helpers::queryFetchObject($sql);
            if ($role_thing) {
                $completed = true;
            } else {
                $completed = false;
            }
        }

        if ($mission->mapId > 0) {
            $mission->regionName = Helpers::getRegion(Helpers::getMapRowByMapId($mission->mapId)->region_id);
        }

        return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/view.twig', [
            'request'    => $request,
            'mission'    => $mission,
            'map'        => $map ?? null,
            'npc'        => $npc ?? null,
            'thing'      => $thing ?? null,
            'time'       => date('i分s秒', $mission->expireTimestamp - time()),
            'role_thing' => $role_thing ?? null,
            'gem_name'   => $gem_name ?? null,
            'expired'    => $expired,
            'completed'  => $completed ?? null,
            'gem_number' => $gem_number ?? null,
        ])));
    }


    /**
     * Hủy bỏ nhiệm vụ询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function cancelQuestion(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/cancelQuestion.twig', [
            'request' => $request,
        ])));
    }


    /**
     * Hủy bỏ nhiệm vụ
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function cancel(TcpConnection $connection, Request $request)
    {
        $request->roleRow->mission = null;
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        $sql = <<<SQL
UPDATE `roles` SET `mission` = null WHERE `id` = $request->roleId;
SQL;

        Helpers::execSql($sql);
        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => 'Ngươi đã từ bỏ trước mặt liên tục nhiệm vụ.',
        ])));
    }


    /**
     * 验证
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function verify(TcpConnection $connection, Request $request)
    {
        $delay = cache()->get('role:mis:del:' . $request->roleId);
        $delay = $delay === false ? 0 : (int)$delay;
        $now = time();
        if ($delay > $now) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Nghiệm chứng mã đưa vào sai lầm, thỉnh ' . ($delay - $now) . ' Giây sau thử lại.',
            ])));
        }
        $captcha = Helpers::getCaptcha();
        cache()->set('role_consecutive_mission_captcha_' . $request->roleId, $captcha['result']);
        return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/verify.twig', [
            'request'        => $request,
            'captcha_base64' => $captcha['captcha_base64'],
        ])));
    }


    /**
     * Làm mới nhiệm vụ询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function changeQuestion(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/changeQuestion.twig', [
            'request' => $request,
        ])));
    }


    /**
     * 提交验证
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function verifyPost(TcpConnection $connection, Request $request)
    {
        if (strtoupper($request->method()) !== 'POST') {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $result = trim($request->post('result'));
        if (!is_numeric($result)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        if ($result != cache()->get('role_consecutive_mission_captcha_' . $request->roleId)) {
            // $captcha = Helpers::getCaptcha();
            // cache()->set('role_consecutive_mission_captcha_' . $request->roleId, $captcha['result']);
            // return $connection->send(\cache_response($request, \view('Map/ConsecutiveMission/verify.twig', [
            //     'request'        => $request,
            //     'captcha_base64' => $captcha['captcha_base64'],
            //     'message'        => '你输入的结果不正确，请重新输入',
            // ])));
            cache()->set('role:mis:del:' . $request->roleId, time() + 60);
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Nghiệm chứng mã đưa vào sai lầm, thỉnh 60 giây sau thử lại.',
            ])));
        }

        /**
         * Cho新任务
         */
        $mission = json_decode($request->roleRow->mission);

        $mission->expireTimestamp = time() + 15 * 60;
        $mission->times += 1;
        $mission->verified = true;
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);

        if ($mission->circle == 1) {
            $mission->circle += 1;
            $mission->gemNumber = Helpers::getConsecutiveMissionGem($mission->times);
            $mission->gemGainNumber = 0;
            $mission->gemKind = mt_rand(1, 4);
        } elseif ($mission->circle == 2) {
            $mission->circle += 1;
            $_ = Helpers::getConsecutiveMissionNpc();
            $mission->npcId = $_['npc_id'];
            $mission->mapId = $_['map_id'];
            $mission->regionName = Helpers::getRegion(Helpers::getMapRowByMapId($mission->mapId)->region_id);
            $mission->status = false;
        } elseif ($mission->circle == 3) {
            $mission->circle += 1;
            $_ = Helpers::getConsecutiveMissionKillNpc($mission->times, $role_attrs->maxSkillLv);
            $mission->npcId = $_['npc_id'];
            $mission->mapId = $_['map_id'];
            $mission->regionName = Helpers::getRegion(Helpers::getMapRowByMapId($mission->mapId)->region_id);
            $mission->status = false;
        } elseif ($mission->circle == 4) {
            $mission->circle += 1;
            $mission->gemNumber = Helpers::getConsecutiveMissionGem($mission->times);
            $mission->gemGainNumber = 0;
            $mission->gemKind = mt_rand(1, 4);
        } elseif ($mission->circle == 5) {
            $mission->circle += 1;
            $mission->mapId = Helpers::getConsecutiveMissionMap();
            $mission->regionName = Helpers::getRegion(Helpers::getMapRowByMapId($mission->mapId)->region_id);
            $mission->status = false;
        } elseif ($mission->circle == 6) {
            $mission->circle += 1;
            $mission->equipmentThingId = Helpers::getConsecutiveMissionEquipment($mission->times);
        } elseif ($mission->circle == 7) {
            $mission->circle = 1;
            $_ = Helpers::getConsecutiveMissionKillNpc($mission->times, $role_attrs->maxSkillLv);
            $mission->npcId = $_['npc_id'];
            $mission->mapId = $_['map_id'];
            $mission->regionName = Helpers::getRegion(Helpers::getMapRowByMapId($mission->mapId)->region_id);
            $mission->status = false;
        }

        /**
         * 保存任务
         */
        $request->roleRow->mission = json_encode($mission, JSON_UNESCAPED_UNICODE);
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        $sql = <<<SQL
UPDATE `roles` SET `mission` = '{$request->roleRow->mission}' WHERE `id` = $request->roleId;
SQL;

        Helpers::execSql($sql);
        return $this->view($connection, $request);
    }
}
