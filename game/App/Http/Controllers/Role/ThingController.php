<?php


namespace App\Http\Controllers\Role;


use App\Libs\Attrs\FlushRoleAttrs;
use App\Libs\Helpers;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;


class ThingController
{
    /**
     * 物品首页 所有
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $kind
     * @param int           $page
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function index(TcpConnection $connection, Request $request, int $kind = 1, int $page = 1)
    {
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `role_id` = $request->roleId LIMIT 121;
SQL;

        $things = Helpers::queryFetchAll($sql);
        $weight = 0;
        $things = array_map(function ($thing) use (&$weight) {
            $thing->viewUrl = 'Role/Thing/view/' . $thing->id;
            if ($thing->thing_id == 0) {
                if ($thing->is_coma == 1 or $thing->is_body == 1) {
                    $weight += 30000000;
                } else {
                    $weight += 1000000;
                }
                return $thing;
            }
            $thing->row = Helpers::getThingRowByThingId($thing->thing_id);
            $weight += $thing->number * $thing->row->weight;
            return $thing;
        }, $things);
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if (count($things) <= 120 && $role_attrs->weight !== $weight) {
            FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);
        }
        if ($kind == 2) {
            foreach ($things as $key => $thing) {
                if (empty($thing->row->kind) or $thing->row->kind != '装备') {
                    unset($things[$key]);
                }
            }
        }
        if ($kind == 3) {
            foreach ($things as $key => $thing) {
                if (empty($thing->row->kind) or $thing->row->kind != '书籍') {
                    unset($things[$key]);
                }
            }
        }
        if ($kind == 4) {
            foreach ($things as $key => $thing) {
                if ((!empty($thing->row->kind) and $thing->row->kind != '其它')) {
                    unset($things[$key]);
                }
            }
        }
        return $connection->send(\cache_response($request, \view('Role/Thing/index.twig', [
            'request' => $request,
            'things'  => $things,
            'weight'  => round($weight / 1000000, 2),
            'kind'    => $kind,
        ])));
    }


    /**
     * Xem xét vật phẩm
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function view(TcpConnection $connection, Request $request, int $role_thing_id)
    {
        /**
         * 获取物品
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);

        if (empty($role_thing)) {
            return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                'request' => $request,
                'message' => 'Vật phẩm đã biến mất',
            ])));
        }

        /**
         * 判断物品类型
         */
        if ($role_thing->thing_id) {
            /**
             * 普通物品
             */
            $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
            if ($role_thing->row->kind == '装备') {
                /**
                 * 装备
                 */
                $role_thing->throwQuestionUrl = 'Role/Thing/throwEquipmentQuestion/' . $role_thing_id;
//                if ($role_thing->status > 0) {
//                    $role_thing->statusString = str_repeat('*', $role_thing->status);
//                } else {
//                    $role_thing->statusString = '×';
//                }
                $role_thing->statusString = str_repeat('*', $role_thing->status);
                $role_thing->removeUrl = 'Role/Thing/remove/' . $role_thing_id;
                $role_thing->putOnUrl = 'Role/Thing/putOn/' . $role_thing_id;
                $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
                if ($role_thing_id === $role_attrs->weaponRoleThingId and $role_thing->durability != $role_attrs->weaponDurability) {
                    $_sql = <<<SQL
UPDATE `role_things` SET `durability` = $role_attrs->weaponDurability WHERE `id` = $role_thing_id;
SQL;

                    $role_thing->durability = $role_attrs->weaponDurability;
                } elseif ($role_thing_id === $role_attrs->clothesRoleThingId and $role_thing->durability != $role_attrs->clothesDurability) {
                    $_sql = <<<SQL
UPDATE `role_things` SET `durability` = $role_attrs->clothesDurability WHERE `id` = $role_thing_id;
SQL;

                    $role_thing->durability = $role_attrs->clothesDurability;
                } elseif ($role_thing_id === $role_attrs->armorRoleThingId and $role_thing->durability != $role_attrs->armorDurability) {
                    $_sql = <<<SQL
UPDATE `role_things` SET `durability` = $role_attrs->armorDurability WHERE `id` = $role_thing_id;
SQL;

                    $role_thing->durability = $role_attrs->armorDurability;
                } elseif ($role_thing_id === $role_attrs->shoesRoleThingId and $role_thing->durability != $role_attrs->shoesDurability) {
                    $_sql = <<<SQL
UPDATE `role_things` SET `durability` = $role_attrs->shoesDurability WHERE `id` = $role_thing_id;
SQL;

                    $role_thing->durability = $role_attrs->shoesDurability;
                }
                if (isset($_sql)) {
                    $_st = db()->prepare($_sql);
                    $_st->execute();
                    $_st->closeCursor();
                }
                return $connection->send(\cache_response($request, \view('Role/Thing/viewEquipment.twig', [
                    'request'    => $request,
                    'role_thing' => $role_thing,
                ])));
            } elseif ($role_thing->row->kind == '书籍') {
                /**
                 * 秘籍
                 */
                $role_thing->throwQuestionUrl = 'Role/Thing/throwBookQuestion/' . $role_thing_id;
                $role_thing->learnUrl = 'Role/Thing/learnBook/' . $role_thing_id;
                return $connection->send(\cache_response($request, \view('Role/Thing/viewBook.twig', [
                    'request'    => $request,
                    'role_thing' => $role_thing,
                ])));
            } elseif ($role_thing->thing_id == 213) {
                /**
                 * 金钱
                 */
                $role_thing->throwQuestionUrl = 'Role/Thing/throwMoneyQuestion/' . $role_thing_id;
                return $connection->send(\cache_response($request, \view('Role/Thing/viewMoney.twig', [
                    'request'    => $request,
                    'role_thing' => $role_thing,
                ])));
            } elseif (in_array($role_thing->thing_id, [215, 216, 217, 218, 219, 220, 221, 222, 245])) {
                /**
                 * 箱子
                 */
                $role_thing->throwQuestionUrl = 'Role/Thing/throwBoxQuestion/' . $role_thing_id;
                $role_thing->openUrl = 'Role/Thing/openBox/' . $role_thing_id;

                /**
                 * 如果是心法箱子则获取心法数量
                 */
                if ($role_thing->thing_id == 215 or $role_thing->thing_id == 245) {
                    $sql = <<<SQL
SELECT `id` FROM `role_xinfas` WHERE `role_id` = $request->roleId;
SQL;

                    $role_xinfas = Helpers::queryFetchAll($sql);

                    if (is_array($role_xinfas) and count($role_xinfas) >= 10) {
                        $is_full = true;
                    }
                }
                return $connection->send(\cache_response($request, \view('Role/Thing/viewBox.twig', [
                    'request'    => $request,
                    'role_thing' => $role_thing,
                    'is_full'    => $is_full ?? false,
                ])));
            }
        } elseif ($role_thing->is_body) {
            /**
             * 尸体
             */
            $role_thing->throwQuestionUrl = 'Role/Thing/throwBodyQuestion/' . $role_thing_id;
            return $connection->send(\cache_response($request, \view('Role/Thing/viewBody.twig', [
                'request'    => $request,
                'role_thing' => $role_thing,
            ])));
        } elseif ($role_thing->is_letter) {
            /**
             * 书信
             */
            $role_thing->throwQuestionUrl = 'Role/Thing/throwLetterQuestion/' . $role_thing_id;
            return $connection->send(\cache_response($request, \view('Role/Thing/viewLetter.twig', [
                'request'    => $request,
                'role_thing' => $role_thing,
                'map'        => Helpers::getMapRowByMapId($role_thing->letter_map_id),
                'npc'        => Helpers::getNpcRowByNpcId($role_thing->letter_npc_id),
            ])));
        } elseif ($role_thing->is_coma) {
            /**
             * 昏迷的玩家
             */
        }


        return $connection->send(\cache_response($request, \view('Role/Thing/view.twig', [
            'request' => $request,
        ])));
    }


    /**
     * 书籍
     *
     * @var array|array[]
     */
    public static array $books = [
        235 => ['thing_id' => 235, 'skill_id' => 1, 'min' => 0, 'max' => 30], // 剑术指南
        204 => ['thing_id' => 204, 'skill_id' => 2, 'min' => 0, 'max' => 30], // 刀法精要
        197 => ['thing_id' => 197, 'skill_id' => 3, 'min' => 0, 'max' => 30], // 玉佩
        194 => ['thing_id' => 194, 'skill_id' => 4, 'min' => 0, 'max' => 30], // 招架要旨
        210 => ['thing_id' => 210, 'skill_id' => 5, 'min' => 0, 'max' => 30], // 拳脚入门
        207 => ['thing_id' => 207, 'skill_id' => 6, 'min' => 0, 'max' => 30], // 丝罗巾
        236 => ['thing_id' => 236, 'skill_id' => 3, 'min' => 0, 'max' => 30], // 基本杖法
        237 => ['thing_id' => 237, 'skill_id' => 4, 'min' => 0, 'max' => 30], // 扇法指南
        238 => ['thing_id' => 238, 'skill_id' => 5, 'min' => 0, 'max' => 30], // 斧法精要
        246 => ['thing_id' => 246, 'skill_id' => 6, 'min' => 0, 'max' => 30], // 基本棒法

        202 => ['thing_id' => 202, 'skill_id' => 1, 'min' => 30, 'max' => 60], // 高级剑术
        239 => ['thing_id' => 239, 'skill_id' => 2, 'min' => 30, 'max' => 60], // 刀法精要外篇
        198 => ['thing_id' => 198, 'skill_id' => 3, 'min' => 30, 'max' => 60], // 石板
        195 => ['thing_id' => 195, 'skill_id' => 4, 'min' => 30, 'max' => 60], // Hủy đi chiêu giảm bớt lực之术
        211 => ['thing_id' => 211, 'skill_id' => 5, 'min' => 30, 'max' => 60], // 武穆遗书
        208 => ['thing_id' => 208, 'skill_id' => 6, 'min' => 30, 'max' => 60], // 踏雪无痕
        240 => ['thing_id' => 240, 'skill_id' => 3, 'min' => 30, 'max' => 60], // 高级杖法
        241 => ['thing_id' => 241, 'skill_id' => 4, 'min' => 30, 'max' => 60], // 高级扇法
        242 => ['thing_id' => 242, 'skill_id' => 5, 'min' => 30, 'max' => 60], // 斧法精要外篇
        243 => ['thing_id' => 243, 'skill_id' => 6, 'min' => 30, 'max' => 60], // 高级棒法

        244 => ['thing_id' => 244, 'skill_id' => 1, 'min' => 60, 'max' => 150], // 剑术之魂
        206 => ['thing_id' => 206, 'skill_id' => 2, 'min' => 60, 'max' => 150], // 刀法之巅
        199 => ['thing_id' => 199, 'skill_id' => 3, 'min' => 60, 'max' => 150], // 薄绢
        196 => ['thing_id' => 196, 'skill_id' => 4, 'min' => 60, 'max' => 150], // 拆招秘典
        212 => ['thing_id' => 212, 'skill_id' => 5, 'min' => 60, 'max' => 150], // 武穆遗书外篇
        209 => ['thing_id' => 209, 'skill_id' => 6, 'min' => 60, 'max' => 150], // 踏雪无痕外篇

        200 => ['thing_id' => 200, 'skill_id' => 3, 'min' => 120, 'max' => 150], // 易筋经
    ];


    /**
     * 学习书籍
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function learnBook(TcpConnection $connection, Request $request, int $role_thing_id)
    {
        /**
         * 获取玩家信息
         */
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);

        /**
         * 判断Tinh thần是否足够
         */
        if ($role_attrs->jingshen <= 0) {
            return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                'request' => $request,
                'message' => '你太累了，什么也没有学到。',
            ])));
        }

        /**
         * 查询秘籍
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
        $book = self::$books[$role_thing->thing_id];

        /**
         * 查询技能
         */
        $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` = {$book['skill_id']};
SQL;

        $role_skill = Helpers::queryFetchObject($sql);
        if (!$role_skill) {
            $sql = <<<SQL
INSERT INTO `role_skills` (`skill_id`, `role_id`, lv) VALUES ({$book['skill_id']}, $request->roleId, 1);
SQL;


            Helpers::execSql($sql);

            $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` = {$book['skill_id']};
SQL;

            $role_skill = Helpers::queryFetchObject($sql);
        }
        $role_skill->row = Helpers::getSkillRowBySkillId($role_skill->skill_id);

        /**
         * 判断技能等级
         */
        if ($role_skill->lv >= $book['max']) {
            return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                'request' => $request,
                'message' => 'Này ' . $role_thing->row->unit . $role_thing->row->name . ' Phảng phất đã không có gì có thể nghiên đọc.',
            ])));
        }
        if ($role_skill->lv < $book['min']) {
            return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                'request' => $request,
                'message' => 'Ngươi còn không có năng lực nghiên đọc này' . $role_thing->row->unit . $role_thing->row->name . '。',
            ])));
        }

        /**
         * 判断Tu vi
         */
        if (pow($role_skill->lv / 10, 3) > $role_attrs->experience / 1000) {
            return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                'request' => $request,
                'message' => 'Ngươi tu vi không đủ để nghiên đọc này' . $role_thing->row->unit . $role_thing->row->name . '。',
            ])));
        }

        /**
         * 计算
         */
        $need_experience = $role_skill->lv * $role_skill->lv;
        $experience = $need_experience - $role_skill->experience;
        $value = $role_attrs->jingshen;
        if ($value > $experience) $value = $experience;
        $role_attrs->jingshen -= $value;
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);

        if ($value < $experience) {
            /**
             * 全部用光 不升级
             */
            $sql = <<<SQL
UPDATE `role_skills` SET `experience` = `experience` + $value WHERE `id` = $role_skill->id;
SQL;


            Helpers::execSql($sql);

            $lv = $role_skill->lv;
            $percent = Helpers::getPercent($role_skill->experience + $value, $need_experience);
        } else {
            /**
             * 全部用光 升级
             */
            $sql = <<<SQL
UPDATE `role_skills` SET `experience` = 0, `lv` = `lv` + 1 WHERE `id` = $role_skill->id;
SQL;


            Helpers::execSql($sql);

            $lv = $role_skill->lv + 1;
            $percent = 0;
            FlushRoleAttrs::fromRoleSkillByRoleId($request->roleId);
        }
        return $connection->send(\cache_response($request, \view('Role/Thing/continueLearn.twig', [
            'request'      => $request,
            'lv'           => $lv,
            'percent'      => $percent,
            'role_thing'   => $role_thing,
            'role_skill'   => $role_skill,
            'continue_url' => 'Role/Thing/learnBook/' . $role_thing_id,
        ])));
    }


    /**
     * Mở ra箱子
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function openBox(TcpConnection $connection, Request $request, int $role_thing_id)
    {
        /**
         * 获取箱子信息
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);

        if (empty($role_thing)) {
            return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }

//        return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
//            'request' => $request,
//            'message' => '你没有钥匙',
//        ])));

        $probability = mt_rand(1, 10000);
        if ($role_thing->thing_id == 215) {
            $probability = mt_rand(1, 100000);
            /**
             * 心法宝箱
             */
            /**
             * 检查心法宝箱钥匙数量是否足够
             */
            $sql = <<<SQL
SELECT * FROM `role_djs` WHERE `role_id` = $request->roleId AND `dj_id` = 12;
SQL;

            $role_dj = Helpers::queryFetchObject($sql);
            if (!is_object($role_dj)) {
                return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                    'request' => $request,
                    'message' => 'Ngươi không có tâm pháp bảo rương chìa khóa!',
                ])));
            }
            if ($role_dj->number <= 1) {
                $sql = <<<SQL
DELETE FROM `role_djs` WHERE `id` = $role_dj->id;
SQL;

            } else {
                $sql = <<<SQL
UPDATE `role_djs` SET `number` = `number` - 1 WHERE `id` = $role_dj->id;
SQL;

            }
            /**
             * 减少心法宝箱的数量
             */
            if ($role_thing->number <= 1) {
                $sql .= <<<SQL
DELETE FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

            } else {
                $sql .= <<<SQL
UPDATE `role_things` SET `number` = `number` - 1 WHERE `id` = $role_thing_id;
SQL;

            }

            Helpers::execSql($sql);

            $t = cache()->incr('role:xinfa:box:' . $request->roleId);
            $last = cache()->get('role:xinfa:box:time:' . $request->roleId);
            if (!empty($last) && $last < strtotime(date('Y-m-d'))) {
                cache()->del('role:xinfa:box:' . $request->roleId);
            }
            cache()->set('role:xinfa:box:time:' . $request->roleId, time());
            if ($probability <= 33340) {
                $xinfa_id = Helpers::getConsecutiveMissionXinfa(0);
            } elseif ($probability <= 66670) {
                $xinfa_id = Helpers::getConsecutiveMissionXinfa(8);
            } elseif ($probability <= 96670) {
                $xinfa_id = Helpers::getConsecutiveMissionXinfa(64);
            } elseif ($probability <= 99780) {
                $xinfa_id = Helpers::getConsecutiveMissionXinfa(128);
            } elseif ($probability <= 99990) {
                if ($t > 50) {
                    $xinfa_id = Helpers::getConsecutiveMissionXinfa(216);
                } else {
                    $xinfa_id = Helpers::getConsecutiveMissionXinfa(64);
                }
            } else {
                if ($t > 200) {
                    $xinfa_id = Helpers::getConsecutiveMissionXinfa(512);
                } else {
                    $xinfa_id = Helpers::getConsecutiveMissionXinfa(128);
                }
            }
            $xinfa = Helpers::getXinfaRowByXinfaId($xinfa_id);
            if ($xinfa->experience == 128 or $xinfa->experience == 216 or $xinfa->experience == 512) {
                /**
                 * 广播
                 */
                $timestamp = time();
                $content = 'Chúc mừng người chơi ' . $request->roleRow->name . 'Mở ra bảo rương đạt được một quyển《' . $xinfa->name . '》 tâm pháp.';
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
             * Tâm Pháp背包是否已经满了
             */
            $sql = <<<SQL
SELECT `id` FROM `role_xinfas` WHERE `role_id` = $request->roleId;
SQL;

            $role_xinfas = Helpers::queryFetchAll($sql);

            if (is_array($role_xinfas) and count($role_xinfas) >= 10) {
                return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                    'request' => $request,
                    'message' => 'Ngươi tâm pháp ba lô đã đầy, mở ra tâm pháp bảo rương được đến' . $xinfa->name . 'Đã bị hệ thống thu về!',
                ])));
            } else {
                $max_lv = mt_rand(40, 80);
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
                    $m = mt_rand(1, 100);
                    if ($m <= 80) {
                        $base_experience = 8;
                    } elseif ($m <= 99) {
                        $base_experience = 7;
                    } else {
                        $base_experience = 6;
                    }
                }
                $sql = <<<SQL
INSERT INTO `role_xinfas` (`role_id`, `xinfa_id`, `lv`, `base_experience`, `max_lv`) VALUES ($request->roleId, $xinfa_id, 1, $base_experience, $max_lv);
SQL;

                $log = '【使用道具】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') .
                    '】使用道具【心法宝箱钥匙】，获得【' . $xinfa->name . '】心法，剩余' . $role_dj->number . '把。';
                $sql .= <<<SQL
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;

                Helpers::execSql($sql);

                return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                    'request' => $request,
                    'message' => 'Ngươi mở ra tâm pháp bảo rương, được đến một quyển' . $xinfa->name . '！',
                ])));
            }
        } elseif ($role_thing->thing_id == 245) {
            /**
             * 空中神匣
             */
            /**
             * 减少心法宝箱的数量
             */
            if ($role_thing->number <= 1) {
                $sql = <<<SQL
DELETE FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

            } else {
                $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` - 1 WHERE `id` = $role_thing_id;
SQL;

            }

            Helpers::execSql($sql);


            /**
             * 获得一本 64 命或内
             */
            while (true) {
                $xinfa_id = Helpers::getConsecutiveMissionXinfa(64);
                $xinfa = Helpers::getXinfaRowByXinfaId($xinfa_id);
                if ($xinfa->kind != '攻击') break;
            }


            /**
             * Tâm Pháp背包是否已经满了
             */
            $sql = <<<SQL
SELECT `id` FROM `role_xinfas` WHERE `role_id` = $request->roleId;
SQL;

            $role_xinfas = Helpers::queryFetchAll($sql);

            if (is_array($role_xinfas) and count($role_xinfas) >= 10) {
                return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                    'request' => $request,
                    'message' => 'Ngươi tâm pháp ba lô đã đầy, mở ra không trung thần hộp được đến' . $xinfa->name . 'Đã bị hệ thống thu về!',
                ])));
            } else {
                $max_lv = mt_rand(40, 80);
                $base_experience = mt_rand(3, 4);
                $sql = <<<SQL
INSERT INTO `role_xinfas` (`role_id`, `xinfa_id`, `lv`, `base_experience`, `max_lv`) VALUES ($request->roleId, $xinfa_id, 1, $base_experience, $max_lv);
SQL;


                Helpers::execSql($sql);

                return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                    'request' => $request,
                    'message' => 'Ngươi mở ra không trung thần hộp, được đến một quyển' . $xinfa->name . '！',
                ])));
            }
        } elseif ($role_thing->thing_id == 216) {
            /**
             * 武器宝箱
             */
            /**
             * 检查武器宝箱钥匙数量是否足够
             */
            $sql = <<<SQL
SELECT * FROM `role_djs` WHERE `role_id` = $request->roleId AND `dj_id` = 13;
SQL;

            $role_dj = Helpers::queryFetchObject($sql);
            if (!is_object($role_dj)) {
                return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                    'request' => $request,
                    'message' => 'Ngươi không có vũ khí bảo rương chìa khóa!',
                ])));
            }

            if ($role_dj->number <= 1) {
                $sql = <<<SQL
DELETE FROM `role_djs` WHERE `id` = $role_dj->id;
SQL;

            } else {
                $sql = <<<SQL
UPDATE `role_djs` SET `number` = `number` - 1 WHERE `id` = $role_dj->id;
SQL;

            }

            /**
             * 减少武器宝箱的数量
             */
            if ($role_thing->number <= 1) {
                $sql .= <<<SQL
DELETE FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

            } else {
                $sql .= <<<SQL
UPDATE `role_things` SET `number` = `number` - 1 WHERE `id` = $role_thing_id;
SQL;

            }

            Helpers::execSql($sql);


            GET_WEAPON:
            if ($probability <= 3000) {
                $thing_id = Helpers::getConsecutiveMissionRewardEquipment(100);
            } elseif ($probability <= 6000) {
                $thing_id = Helpers::getConsecutiveMissionRewardEquipment(500);
            } elseif ($probability <= 8000) {
                $thing_id = Helpers::getConsecutiveMissionRewardEquipment(900);
            } elseif ($probability <= 9800) {
                $thing_id = Helpers::getConsecutiveMissionRewardEquipment(3000);
            } else {
                $thing_id = Helpers::getConsecutiveMissionRewardNoDropEquipment();
            }
            $thing = Helpers::getThingRowByThingId($thing_id);

            if ($thing->equipment_kind != 1 and $thing->equipment_kind != 2 and $thing->equipment_kind != 3) {
                goto GET_WEAPON;
            }
            if ($thing->is_no_drop) {
                /**
                 * 广播
                 */
                $timestamp = time();
                $content = 'Chúc mừng người chơi ' . $request->roleRow->name . 'Mở ra bảo rương đạt được một' . $thing->unit . '「' . $thing->name . '」。';
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
            $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`, `status`, `durability`) VALUES ($request->roleId, $thing_id, 1, 4, $thing->max_durability);
SQL;

            $log = '【使用道具】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') .
                '】使用道具【武器宝箱钥匙】，获得【' . $thing->name . '】，剩余' . $role_dj->number . '把。';
            $sql .= <<<SQL
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;

            Helpers::execSql($sql);

            return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                'request' => $request,
                'message' => 'Ngươi mở ra vũ khí bảo rương, được đến một' . $thing->unit . $thing->name . '！',
            ])));
        } elseif ($role_thing->thing_id == 217) {
            /**
             * 靴子宝箱
             */
            /**
             * 检查靴子宝箱钥匙数量是否足够
             */
            $sql = <<<SQL
SELECT * FROM `role_djs` WHERE `role_id` = $request->roleId AND `dj_id` = 14;
SQL;

            $role_dj = Helpers::queryFetchObject($sql);
            if (!is_object($role_dj)) {
                return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                    'request' => $request,
                    'message' => 'Ngươi không có giày bảo rương chìa khóa!',
                ])));
            }
            if ($role_dj->number <= 1) {
                $sql = <<<SQL
DELETE FROM `role_djs` WHERE `id` = $role_dj->id;
SQL;

            } else {
                $sql = <<<SQL
UPDATE `role_djs` SET `number` = `number` - 1 WHERE `id` = $role_dj->id;
SQL;

            }

            /**
             * 减少靴子宝箱的数量
             */
            if ($role_thing->number <= 1) {
                $sql .= <<<SQL
DELETE FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

            } else {
                $sql .= <<<SQL
UPDATE `role_things` SET `number` = `number` - 1 WHERE `id` = $role_thing_id;
SQL;

            }

            Helpers::execSql($sql);


            GET_SHOES:
            if ($probability <= 3000) {
                $thing_id = Helpers::getConsecutiveMissionRewardEquipment(100);
            } elseif ($probability <= 6000) {
                $thing_id = Helpers::getConsecutiveMissionRewardEquipment(500);
            } elseif ($probability <= 8000) {
                $thing_id = Helpers::getConsecutiveMissionRewardEquipment(900);
            } elseif ($probability <= 9800) {
                $thing_id = Helpers::getConsecutiveMissionRewardEquipment(3000);
            } else {
                $thing_id = Helpers::getConsecutiveMissionRewardNoDropEquipment();
            }
            $thing = Helpers::getThingRowByThingId($thing_id);

            if ($thing->equipment_kind != 6) {
                goto GET_SHOES;
            }
            if ($thing->is_no_drop) {
                /**
                 * 广播
                 */
                $timestamp = time();
                $content = 'Chúc mừng người chơi ' . $request->roleRow->name . 'Mở ra bảo rương đạt được một' . $thing->unit . '「' . $thing->name . '」。';
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
            $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`, `status`, `durability`) VALUES ($request->roleId, $thing_id, 1, 4, $thing->max_durability);
SQL;

            $log = '【使用道具】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') .
                '】使用道具【靴子宝箱钥匙】，获得【' . $thing->name . '】，剩余' . $role_dj->number . '把。';
            $sql .= <<<SQL
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;

            Helpers::execSql($sql);

            return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                'request' => $request,
                'message' => 'Ngươi mở ra giày bảo rương, được đến một' . $thing->unit . $thing->name . '！',
            ])));
        } elseif ($role_thing->thing_id == 218) {
            /**
             * 衣服宝箱
             */
            /**
             * 检查衣服宝箱钥匙数量是否足够
             */
            $sql = <<<SQL
SELECT * FROM `role_djs` WHERE `role_id` = $request->roleId AND `dj_id` = 15;
SQL;

            $role_dj = Helpers::queryFetchObject($sql);
            if (!is_object($role_dj)) {
                return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                    'request' => $request,
                    'message' => 'Ngươi không có quần áo bảo rương chìa khóa!',
                ])));
            }
            if ($role_dj->number <= 1) {
                $sql = <<<SQL
DELETE FROM `role_djs` WHERE `id` = $role_dj->id;
SQL;

            } else {
                $sql = <<<SQL
UPDATE `role_djs` SET `number` = `number` - 1 WHERE `id` = $role_dj->id;
SQL;

            }

            /**
             * 减少衣服宝箱的数量
             */
            if ($role_thing->number <= 1) {
                $sql .= <<<SQL
DELETE FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

            } else {
                $sql .= <<<SQL
UPDATE `role_things` SET `number` = `number` - 1 WHERE `id` = $role_thing_id;
SQL;

            }

            Helpers::execSql($sql);


            GET_CLOTHES:
            if ($probability <= 3000) {
                $thing_id = Helpers::getConsecutiveMissionRewardEquipment(100);
            } elseif ($probability <= 6000) {
                $thing_id = Helpers::getConsecutiveMissionRewardEquipment(500);
            } elseif ($probability <= 8000) {
                $thing_id = Helpers::getConsecutiveMissionRewardEquipment(900);
            } elseif ($probability <= 9800) {
                $thing_id = Helpers::getConsecutiveMissionRewardEquipment(3000);
            } else {
                $thing_id = Helpers::getConsecutiveMissionRewardNoDropEquipment();
            }
            $thing = Helpers::getThingRowByThingId($thing_id);

            if ($thing->equipment_kind != 4) {
                goto GET_CLOTHES;
            }
            if ($thing->is_no_drop) {
                /**
                 * 广播
                 */
                $timestamp = time();
                $content = 'Chúc mừng người chơi ' . $request->roleRow->name . 'Mở ra bảo rương đạt được một' . $thing->unit . '「' . $thing->name . '」。';
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
            $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`, `status`, `durability`) VALUES ($request->roleId, $thing_id, 1, 4, $thing->max_durability);
SQL;

            $log = '【使用道具】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') .
                '】使用道具【衣服宝箱钥匙】，获得【' . $thing->name . '】，剩余' . $role_dj->number . '把。';
            $sql .= <<<SQL
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;

            Helpers::execSql($sql);

            return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                'request' => $request,
                'message' => 'Ngươi mở ra quần áo bảo rương, được đến một' . $thing->unit . $thing->name . '！',
            ])));
        } elseif ($role_thing->thing_id == 219) {
            /**
             * 铠甲宝箱
             */
            /**
             * 检查铠甲宝箱钥匙数量是否足够
             */
            $sql = <<<SQL
SELECT * FROM `role_djs` WHERE `role_id` = $request->roleId AND `dj_id` = 16;
SQL;

            $role_dj = Helpers::queryFetchObject($sql);
            if (!is_object($role_dj)) {
                return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                    'request' => $request,
                    'message' => 'Ngươi không có áo giáp bảo rương chìa khóa!',
                ])));
            }
            if ($role_dj->number <= 1) {
                $sql = <<<SQL
DELETE FROM `role_djs` WHERE `id` = $role_dj->id;
SQL;

            } else {
                $sql = <<<SQL
UPDATE `role_djs` SET `number` = `number` - 1 WHERE `id` = $role_dj->id;
SQL;

            }

            /**
             * 减少铠甲宝箱的数量
             */
            if ($role_thing->number <= 1) {
                $sql .= <<<SQL
DELETE FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

            } else {
                $sql .= <<<SQL
UPDATE `role_things` SET `number` = `number` - 1 WHERE `id` = $role_thing_id;
SQL;

            }

            Helpers::execSql($sql);


            GET_ARMOR:
            if ($probability <= 3000) {
                $thing_id = Helpers::getConsecutiveMissionRewardEquipment(100);
            } elseif ($probability <= 6000) {
                $thing_id = Helpers::getConsecutiveMissionRewardEquipment(500);
            } elseif ($probability <= 8000) {
                $thing_id = Helpers::getConsecutiveMissionRewardEquipment(900);
            } elseif ($probability <= 9800) {
                $thing_id = Helpers::getConsecutiveMissionRewardEquipment(3000);
            } else {
                $thing_id = Helpers::getConsecutiveMissionRewardNoDropEquipment();
            }
            $thing = Helpers::getThingRowByThingId($thing_id);

            if ($thing->equipment_kind != 5) {
                goto GET_ARMOR;
            }
            if ($thing->is_no_drop) {
                /**
                 * 广播
                 */
                $timestamp = time();
                $content = 'Chúc mừng người chơi ' . $request->roleRow->name . 'Mở ra bảo rương đạt được một' . $thing->unit . '「' . $thing->name . '」。';
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
            $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`, `status`, `durability`) VALUES ($request->roleId, $thing_id, 1, 4, $thing->max_durability);
SQL;

            $log = '【使用道具】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') .
                '】使用道具【铠甲宝箱钥匙】，获得【' . $thing->name . '】，剩余' . $role_dj->number . '把。';
            $sql .= <<<SQL
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;

            Helpers::execSql($sql);

            return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                'request' => $request,
                'message' => 'Ngươi mở ra áo giáp bảo rương, được đến một' . $thing->unit . $thing->name . '！',
            ])));
        } elseif ($role_thing->thing_id == 220 or $role_thing->thing_id == 221 or $role_thing->thing_id == 222) {
            /**
             * Hoàng kim宝箱（小）
             */
            /**
             * Hoàng kim宝箱（中）
             */
            /**
             * Hoàng kim宝箱（大）
             */
            /**
             * 检查Hoàng kim宝箱钥匙数量是否足够
             */
            if ($role_thing->thing_id == 220) {
                $sql = <<<SQL
SELECT * FROM `role_djs` WHERE `role_id` = $request->roleId AND `dj_id` = 9;
SQL;

                $role_dj = Helpers::queryFetchObject($sql);
                if (!is_object($role_dj)) {
                    return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                        'request' => $request,
                        'message' => 'Ngươi không có hoàng kim bảo rương chìa khóa ( tiểu )!',
                    ])));
                }
            } elseif ($role_thing->thing_id == 221) {
                $sql = <<<SQL
SELECT * FROM `role_djs` WHERE `role_id` = $request->roleId AND `dj_id` = 10;
SQL;

                $role_dj = Helpers::queryFetchObject($sql);
                if (!is_object($role_dj)) {
                    return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                        'request' => $request,
                        'message' => 'Ngươi không có hoàng kim bảo rương chìa khóa ( trung )!',
                    ])));
                }
            } else {
                $sql = <<<SQL
SELECT * FROM `role_djs` WHERE `role_id` = $request->roleId AND `dj_id` = 11;
SQL;

                $role_dj = Helpers::queryFetchObject($sql);
                if (!is_object($role_dj)) {
                    return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                        'request' => $request,
                        'message' => 'Ngươi không có hoàng kim bảo rương chìa khóa ( đại )!',
                    ])));
                }
            }
            if ($role_dj->number <= 1) {
                $sql = <<<SQL
DELETE FROM `role_djs` WHERE `id` = $role_dj->id;
SQL;

            } else {
                $sql = <<<SQL
UPDATE `role_djs` SET `number` = `number` - 1 WHERE `id` = $role_dj->id;
SQL;

            }


            /**
             * 减少Hoàng kim宝箱的数量
             */
            if ($role_thing->number <= 1) {
                $sql .= <<<SQL
DELETE FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

            } else {
                $sql .= <<<SQL
UPDATE `role_things` SET `number` = `number` - 1 WHERE `id` = $role_thing_id;
SQL;

            }

            Helpers::execSql($sql);


            /**
             * 获取Hoàng kim的数量
             */
            if ($role_thing->thing_id == 220) {
                $number = mt_rand(20, 100);
            } elseif ($role_thing->thing_id == 221) {
                $number = mt_rand(100, 500);
            } else {
                $number = mt_rand(500, 1000);
            }
            $number *= 10000;

            /**
             * 查询金钱是否存在
             */
            $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

            $role_money = Helpers::queryFetchObject($sql);

            if ($role_money) {
                $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` + $number WHERE `id` = $role_money->id;
SQL;

            } else {
                $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, 213, $number);
SQL;

            }
            $role_dj->row = ShopController::$djs[$role_dj->dj_id];
            $log = '【使用道具】玩家【' . $request->roleRow->name . '】于【' . date('Y-m-d H:i:s') .
                '】使用道具【' . $role_dj->row['name'] . '】，获得【' . Helpers::getHansMoney($number) . '】，剩余' . $role_dj->number . '把。';
            $sql .= <<<SQL
INSERT INTO `role_shop_logs` (`log`) VALUES ('$log');
SQL;

            Helpers::execSql($sql);

            $thing = Helpers::getThingRowByThingId($role_thing->thing_id);
            return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                'request' => $request,
                'message' => 'Ngươi mở ra ' . $thing->name . ' ,Được đến' . Helpers::getHansMoney($number) . '！',
            ])));
        }
    }


    /**
     * 丢弃书籍 询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function throwBookQuestion(TcpConnection $connection, Request $request, int $role_thing_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);

        $role_thing->throwUrl = 'Role/Thing/throwBook/' . $role_thing_id;
        $role_thing->backUrl = 'Role/Thing/view/' . $role_thing_id;

        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);

        return $connection->send(\cache_response($request, \view('Role/Thing/throwBookQuestion.twig', [
            'request'    => $request,
            'role_thing' => $role_thing,
        ])));
    }


    /**
     * 丢弃书籍
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function throwBook(TcpConnection $connection, Request $request, int $role_thing_id)
    {
        /**
         * 获取书籍
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);

        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);

        /**
         * 移除书籍
         */
        $sql = <<<SQL
DELETE FROM `role_things` WHERE `id` = $role_thing_id;
SQL;


        Helpers::execSql($sql);


        FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);
        /**
         * 添加到地上
         */
        $map_things = cache()->hGet('map_things_' . $request->roleRow->map_id, 'things');

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

        cache()->hSet('map_things_' . $request->roleRow->map_id, 'things', serialize($map_things));

        return $connection->send(\cache_response($request, \view('Role/Thing/throwBook.twig', [
            'request'    => $request,
            'role_thing' => $role_thing,
        ])));
    }


    /**
     * 丢弃箱子 询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function throwBoxQuestion(TcpConnection $connection, Request $request, int $role_thing_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);

        $role_thing->throwUrl = 'Role/Thing/throwBox/' . $role_thing_id;
        $role_thing->backUrl = 'Role/Thing/view/' . $role_thing_id;

        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);

        return $connection->send(\cache_response($request, \view('Role/Thing/throwBoxQuestion.twig', [
            'request'    => $request,
            'role_thing' => $role_thing,
        ])));
    }


    /**
     * 丢弃箱子 Xác nhận
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function throwBox(TcpConnection $connection, Request $request, int $role_thing_id)
    {
        if (strtoupper($request->method()) != 'POST') {
            return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $number = trim($request->post('number'));
        if (!is_numeric($number) or $number < 1) {
            $number = 1;
        } else {
            $number = intval($number);
        }

        /**
         * 获取箱子
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);

        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);

        /**
         * 判断箱子是否足够
         */
        if ($role_thing->number < $number) {
            return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                'request' => $request,
                'message' => 'Ngươi trên người không có như vậy nhiều ' . $role_thing->row->name . ' Có thể vứt bỏ!',
            ])));
        }

        /**
         * 减少箱子数量
         */
        if ($role_thing->number == $number) {
            $sql = <<<SQL
DELETE FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        } else {
            $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` - $number WHERE `id` = $role_thing_id;
SQL;

        }

        Helpers::execSql($sql);


        FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);
        /**
         * 添加到地上
         */
        $map_boxes = cache()->hGet('map_things_' . $request->roleRow->map_id, 'boxes');

        if ($map_boxes) {
            $map_boxes = unserialize($map_boxes);
        } else {
            $map_boxes = [];
        }

        for ($i = 0; $i < $number; $i++) {
            $map_boxes[md5(mt_rand(1, 88888888))] = [
                'expire'          => time() + 300,
                'thing_id'        => $role_thing->thing_id,
                'protect_role_id' => 0,
            ];
        }

        cache()->hSet('map_things_' . $request->roleRow->map_id, 'boxes', serialize($map_boxes));

        return $connection->send(\cache_response($request, \view('Role/Thing/throwBox.twig', [
            'request'    => $request,
            'number'     => $number,
            'role_thing' => $role_thing,
        ])));
    }


    /**
     * 丢弃金钱 询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function throwMoneyQuestion(TcpConnection $connection, Request $request, int $role_thing_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);

        $role_thing->throwUrl = 'Role/Thing/throwMoney/' . $role_thing_id;
        $role_thing->backUrl = 'Role/Thing/view/' . $role_thing_id;

        return $connection->send(\cache_response($request, \view('Role/Thing/throwMoneyQuestion.twig', [
            'request'    => $request,
            'role_thing' => $role_thing,
        ])));
    }


    /**
     * 丢弃金钱 Xác nhận
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function throwMoney(TcpConnection $connection, Request $request, int $role_thing_id)
    {
        if (strtoupper($request->method()) != 'POST') {
            return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $number = trim($request->post('number'));
        if (!is_numeric($number) or $number < 1) {
            $number = 1;
        } else {
            $number = intval($number);
        }

        /**
         * 获取金钱
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);

        /**
         * 判断金钱是否足够
         */
        if ($role_thing->number < $number) {
            return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                'request' => $request,
                'message' => 'Ngươi trên người không có như vậy nhiều 钱 Có thể vứt bỏ!',
            ])));
        }

        /**
         * 减少金钱数量
         */
        if ($role_thing->number == $number) {
            $sql = <<<SQL
DELETE FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        } else {
            $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` - $number WHERE `id` = $role_thing_id;
SQL;

        }

        Helpers::execSql($sql);

        FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);

        /**
         * 添加到地上
         */
        $map_money = cache()->hGet('map_things_' . $request->roleRow->map_id, 'money');

        if ($map_money) {
            $map_money = unserialize($map_money);
            $map_money['expire'] = time() + 300;
            $map_money['number'] += $number;
        } else {
            $map_money = [];
            $map_money['expire'] = time() + 300;
            $map_money['protect_role_id'] = 0;
            $map_money['number'] = $number;
            if ($number == 1) {
                $map_money['is_no_expire'] = true;
            } else {
                $map_money['is_no_expire'] = false;
            }
        }

        cache()->hSet('map_things_' . $request->roleRow->map_id, 'money', serialize($map_money));

        return $connection->send(\cache_response($request, \view('Role/Thing/throwMoney.twig', [
            'request' => $request,
            'number'  => $number,
        ])));
    }


    /**
     * 丢弃装备 询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function throwEquipmentQuestion(TcpConnection $connection, Request $request, int $role_thing_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);

        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);

        $role_thing->throwUrl = 'Role/Thing/throwEquipment/' . $role_thing_id;
        $role_thing->backUrl = 'Role/Thing/view/' . $role_thing_id;

        return $connection->send(\cache_response($request, \view('Role/Thing/throwEquipmentQuestion.twig', [
            'request'    => $request,
            'role_thing' => $role_thing,
        ])));
    }


    /**
     * 丢弃装备 Xác nhận
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function throwEquipment(TcpConnection $connection, Request $request, int $role_thing_id)
    {
        /**
         * 获取物品
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);

        /**
         * 判断是否在装备中
         */
        if ($role_thing->equipped) {
            return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                'request' => $request,
                'message' => 'Ngươi không thể vứt bỏ đang ở sử dụng trang bị!',
            ])));
        }

        /**
         * 删除物品
         */
        $sql = <<<SQL
DELETE FROM `role_things` WHERE `id` = $role_thing_id;
SQL;


        Helpers::execSql($sql);


        FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);
        /**
         * 添加到地上
         */
        $map_things = cache()->hGet('map_things_' . $request->roleRow->map_id, 'things');

        if ($map_things) {
            $map_things = unserialize($map_things);
        } else {
            $map_things = [];
        }

        $map_things[md5(microtime(true))] = [
            'expire'          => time() + 300,
            'thing_id'        => $role_thing->thing_id,
            'protect_role_id' => 0,
            'status'          => $role_thing->status,
            'durability'      => $role_thing->durability,
        ];

        cache()->hSet('map_things_' . $request->roleRow->map_id, 'things', serialize($map_things));

        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);

        return $connection->send(\cache_response($request, \view('Role/Thing/throwEquipment.twig', [
            'request'    => $request,
            'role_thing' => $role_thing,
        ])));
    }


    /**
     * 丢弃书信 询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function throwLetterQuestion(TcpConnection $connection, Request $request, int $role_thing_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        $role_thing->throwUrl = 'Role/Thing/throwLetter/' . $role_thing_id;
        $role_thing->backUrl = 'Role/Thing/view/' . $role_thing_id;

        return $connection->send(\cache_response($request, \view('Role/Thing/throwLetterQuestion.twig', [
            'request'    => $request,
            'role_thing' => $role_thing,
            'map'        => Helpers::getMapRowByMapId($role_thing->letter_map_id),
            'npc'        => Helpers::getNpcRowByNpcId($role_thing->letter_npc_id),
        ])));
    }


    /**
     * 丢弃书信 Xác nhận
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function throwLetter(TcpConnection $connection, Request $request, int $role_thing_id)
    {
        /**
         * 获取物品
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);

        /**
         * 删除物品
         */
        $sql = <<<SQL
DELETE FROM `role_things` WHERE `id` = $role_thing_id;
SQL;


        Helpers::execSql($sql);


        FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);
        return $connection->send(\cache_response($request, \view('Role/Thing/throwLetter.twig', [
            'request'    => $request,
            'role_thing' => $role_thing,
            'map'        => Helpers::getMapRowByMapId($role_thing->letter_map_id),
            'npc'        => Helpers::getNpcRowByNpcId($role_thing->letter_npc_id),
        ])));
    }


    /**
     * 丢弃尸体 询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function throwBodyQuestion(TcpConnection $connection, Request $request, int $role_thing_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        if (empty($role_thing)) {
            return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                'request' => $request,
                'message' => 'Vật phẩm đã biến mất',
            ])));
        }
        $role_thing->throwUrl = 'Role/Thing/throwBody/' . $role_thing_id;
        $role_thing->backUrl = 'Role/Thing/view/' . $role_thing_id;

        return $connection->send(\cache_response($request, \view('Role/Thing/throwBodyQuestion.twig', [
            'request'    => $request,
            'role_thing' => $role_thing,
        ])));
    }


    /**
     * 丢弃尸体 Xác nhận
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function throwBody(TcpConnection $connection, Request $request, int $role_thing_id)
    {
        /**
         * 获取物品
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;

        $role_thing = Helpers::queryFetchObject($sql);
        if (empty($role_thing)) {
            return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
                'request' => $request,
                'message' => 'Vật phẩm đã biến mất',
            ])));
        }
        /**
         * 删除物品
         */
        $sql = <<<SQL
DELETE FROM `role_things` WHERE `id` = $role_thing_id;
SQL;


        Helpers::execSql($sql);


        FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);
        /**
         * 添加到地上
         */
        $map_bodies = cache()->hGet('map_things_' . $request->roleRow->map_id, 'bodies');

        if ($map_bodies) {
            $map_bodies = unserialize($map_bodies);
        } else {
            $map_bodies = [];
        }

        $map_bodies[md5(microtime(true))] = [
            'expire' => $role_thing->body_expire,
            'name'   => $role_thing->body_name,
        ];

        cache()->hSet('map_things_' . $request->roleRow->map_id, 'bodies', serialize($map_bodies));

        return $connection->send(\cache_response($request, \view('Role/Thing/throwBody.twig', [
            'request'    => $request,
            'role_thing' => $role_thing,
        ])));
    }

//    /**
//     * 丢弃物品 Xác nhận
//     * @param TcpConnection $connection
//     * @param Request $request
//     * @param int $role_thing_id
//     * @return bool|null
//     * @throws LoaderError
//     * @throws RuntimeError
//     * @throws SyntaxError
//     */
//    public function throwQuestion(TcpConnection $connection, Request $request, int $role_thing_id)
//    {
//        $sql = <<<SQL
//SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
//SQL;
//
//        $thing = db()->query($sql)->fetch(\PDO::FETCH_OBJ);
//        $thing->row = Helpers::getThingRowByThingId($thing->thing_id);
//        $thing->throwUrl = 'Role/Thing/throw/' . $role_thing_id;
//
//        return $connection->send(\cache_response($request, \view('Role/Thing/throwQuestion.twig', [
//            'request' => $request,
//            'thing'   => $thing,
//        ])));
//    }
//
//    /**
//     * 丢弃物品
//     * @param TcpConnection $connection
//     * @param Request $request
//     * @param int $role_thing_id
//     * @return bool|null
//     * @throws LoaderError
//     * @throws RuntimeError
//     * @throws SyntaxError
//     */
//    public function throw(TcpConnection $connection, Request $request, int $role_thing_id)
//    {
//        $sql = <<<SQL
//SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
//SQL;
//
//        $thing = db()->query($sql)->fetch(\PDO::FETCH_OBJ);
//        $thing->row = Helpers::getThingRowByThingId($thing->thing_id);
//        if (strtoupper($request->method()) == 'POST') {
//            if (is_numeric($request->post('number'))) {
//                $number = intval($request->post('number'));
//                if ($number < 1) {
//                    $number = 1;
//                }
//            } else {
//                $number = 1;
//            }
//        } else {
//            $number = 1;
//        }
//        if ($number >= $thing->number) {
//            $sql = <<<SQL
//DELETE FROM `role_things` WHERE `id` = $role_thing_id;
//SQL;
//            $number = $thing->number;
//        } else {
//            $result = $thing->number - $number;
//            $sql = <<<SQL
//UPDATE `role_things` SET `number` = $result WHERE `id` = $role_thing_id;
//SQL;
//
//        }
//
//        db()->exec($sql);
//
//        return $connection->send(\cache_response($request, \view('Role/Thing/message.twig', [
//            'request' => $request,
//            'thing'   => $thing,
//            'message' => 'Ngươi vứt bỏ ',
//        ])));
//    }

    /**
     * 穿戴装备
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function putOn(TcpConnection $connection, Request $request, int $role_thing_id)
    {
        /**
         * 检测其他部位是否有装备、取下装备
         */
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;


        $role_thing = Helpers::queryFetchObject($sql);
        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
        if ($role_thing->durability < 0) {
            return $connection->send(\cache_response($request, \view('Role/Thing/putOn.twig', [
                'request' => $request,
                'message' => 'Trang bị đã hư hao, vô pháp sử dụng!',
            ])));
        }

        if ($role_thing->row->equipment_kind == 1 or $role_thing->row->equipment_kind == 2 or $role_thing->row->equipment_kind == 3) {
            $sql = <<<SQL
UPDATE `role_things` INNER JOIN `things` ON `things`.`id` = `role_things`.`thing_id` AND `equipment_kind` IN (1, 2, 3) SET `equipped` = 0 WHERE `role_things`.`role_id` = $request->roleId;
SQL;

        } else {
            $sql = <<<SQL
UPDATE `role_things` INNER JOIN `things` ON `things`.`id` = `role_things`.`thing_id` AND `equipment_kind` = {$role_thing->row->equipment_kind} SET `equipped` = 0 WHERE `role_things`.`role_id` = $request->roleId;
SQL;

        }

        Helpers::execSql($sql);


        /**
         * 穿上装备
         */
        $sql = <<<SQL
UPDATE `role_things` SET `equipped` = 1 WHERE `id` = $role_thing_id;
SQL;


        Helpers::execSql($sql);


        /**
         * 更新属性
         */
        FlushRoleAttrs::fromRoleEquipmentByRoleId($request->roleId);
        if (in_array($role_thing->row->equipment_kind, [1, 2, 3])) {
            FlushRoleAttrs::fromRoleSkillByRoleId($request->roleId);
        }

        if ($role_thing->row->equipment_kind == 1 or $role_thing->row->equipment_kind == 2 or $role_thing->row->equipment_kind == 3) {
            $message = 'Ngươi lấy ra ' . Helpers::getHansNumber(1) . $role_thing->row->unit . $role_thing->row->name . ',Nắm trong tay!';
        } else {
            $message = 'Ngươi lấy ra ' . Helpers::getHansNumber(1) . $role_thing->row->unit . $role_thing->row->name . ',Mặc ở trên người!';
        }

        return $connection->send(\cache_response($request, \view('Role/Thing/putOn.twig', [
            'request' => $request,
            'message' => $message,
        ])));
    }


    /**
     * 取下装备
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function remove(TcpConnection $connection, Request $request, int $role_thing_id)
    {
        // 检测部位是否存在其他装备 取下装备
        $sql = <<<SQL
SELECT * FROM `role_things` WHERE `id` = $role_thing_id;
SQL;


        $role_thing = Helpers::queryFetchObject($sql);
        $role_thing->row = Helpers::getThingRowByThingId($role_thing->thing_id);
        // 穿上装备
        $sql = <<<SQL
UPDATE `role_things` SET `equipped` = 0 WHERE `id` = $role_thing_id;
SQL;


        Helpers::execSql($sql);


        // 更新属性
        FlushRoleAttrs::fromRoleEquipmentByRoleId($request->roleId);
        if (in_array($role_thing->row->equipment_kind, [1, 2, 3])) {
            FlushRoleAttrs::fromRoleSkillByRoleId($request->roleId);
        }

        if ($role_thing->row->equipment_kind == 1 or $role_thing->row->equipment_kind == 2 or $role_thing->row->equipment_kind == 3) {
            $message = 'Ngươi cầm trong tay ' . $role_thing->row->name . ',Thả lại tùy thân vật phẩm!';
        } else {
            $message = 'Ngươi gỡ xuống mặc ' . $role_thing->row->name . ',Thả lại tùy thân vật phẩm!';
        }

        return $connection->send(\cache_response($request, \view('Role/Thing/remove.twig', [
            'request' => $request,
            'message' => $message,
        ])));
    }


    /**
     * Chữa thương dược
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function drug(TcpConnection $connection, Request $request)
    {
        $sql = <<<SQL
SELECT * FROM `role_drugs` WHERE `role_id` = $request->roleId;
SQL;

        $drugs = Helpers::queryFetchAll($sql);
        $drugs = array_map(function ($drug) {
            $drug->viewUrl = 'Role/Thing/viewDrug/' . $drug->id;
            $drug->useUrl = 'Role/Thing/useDrug/' . $drug->id;
            $drug->row = Helpers::getThingRowByThingId($drug->thing_id);
            return $drug;
        }, $drugs);


        return $connection->send(\cache_response($request, \view('Role/Thing/drug.twig', [
            'request' => $request,
            'drugs'   => $drugs,
        ])));
    }


    /**
     * Xem xétChữa thương dược
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $drug_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function viewDrug(TcpConnection $connection, Request $request, int $drug_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_drugs` WHERE `id` = $drug_id;
SQL;

        $drug = Helpers::queryFetchObject($sql);

        $drug->row = Helpers::getThingRowByThingId($drug->thing_id);
        $drug->useUrl = 'Role/Thing/useDrug/' . $drug->id;
        return $connection->send(\cache_response($request, \view('Role/Thing/viewDrug.twig', [
            'request' => $request,
            'drug'    => $drug,
        ])));
    }


    /**
     * Sử dụng
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $drug_id
     *
     * @return bool|null
     */
    public function useDrug(TcpConnection $connection, Request $request, int $drug_id)
    {
        /**
         * 查询
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_drugs` WHERE `id` = $drug_id;
SQL;

        $drug = Helpers::queryFetchObject($sql);

        $drug->row = Helpers::getThingRowByThingId($drug->thing_id);

        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);

        $o = '气色';

        if ($role_attrs->hp < $role_attrs->maxHp) {
            $role_attrs->hp += $drug->row->hp;
            if ($role_attrs->hp > $role_attrs->maxHp) {
                $role_attrs->hp = $role_attrs->maxHp;
            }
        }
        if ($role_attrs->mp < $role_attrs->maxMp) {
            $role_attrs->mp += $drug->row->mp;
            if ($role_attrs->mp > $role_attrs->maxMp) {
                $role_attrs->mp = $role_attrs->maxMp;
            }
        }
        if ($role_attrs->jingshen < $role_attrs->maxJingshen) {
            if ($drug->row->jingshen > 0) {
                $role_attrs->jingshen += $drug->row->jingshen;
                if ($role_attrs->jingshen > $role_attrs->maxJingshen) {
                    $role_attrs->jingshen = $role_attrs->maxJingshen;
                }
                $o = 'Tinh thần';
            }
        }

        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);

        $messages = [];
        $messages[] = 'Ngươi ăn xong một' . $drug->row->unit . $drug->row->name . '，' . $o . 'Thoạt nhìn khá hơn nhiều.';
        $messages[] = 'Khí huyết:' . $role_attrs->hp . '/' . $role_attrs->maxHp;
        $messages[] = 'Nội lực:' . $role_attrs->mp . '/' . $role_attrs->maxMp;
        $messages[] = 'Tinh thần:' . $role_attrs->jingshen . '/' . $role_attrs->maxJingshen;

        if ($drug->number > 1) {
            $sql = <<<SQL
UPDATE `role_drugs` SET `number` = `number` - 1 WHERE `id` = $drug_id;
SQL;

        } else {
            $sql = <<<SQL
DELETE FROM `role_drugs` WHERE `id` = $drug_id;
SQL;

        }
        Helpers::execSql($sql);

        $drug->useUrl = 'Role/Thing/useDrug/' . $drug->id;

        return $connection->send(\cache_response($request, \view('Role/Thing/useDrug.twig', [
            'request'  => $request,
            'drug'     => $drug,
            'messages' => $messages,
        ])));
    }
}
