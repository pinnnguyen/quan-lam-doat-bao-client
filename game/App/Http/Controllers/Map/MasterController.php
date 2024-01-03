<?php

namespace App\Http\Controllers\Map;

use App\Libs\Attrs\FlushRoleAttrs;
use App\Libs\Helpers;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 师门
 */
class MasterController
{
    /**
     * Bái sưXác nhận询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $npc_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function joinQuestion(TcpConnection $connection, Request $request, int $npc_id)
    {
        $npc = Helpers::getNpcRowByNpcId($npc_id);
        return $connection->send(\cache_response($request, \view('Map/Master/joinQuestion.twig', [
            'request' => $request,
            'sect'    => Helpers::getSect($npc->sect_id),
            'joinUrl' => 'Map/Master/join/' . $npc->id,
            'npc'     => $npc,
        ])));
    }


    /**
     * Bái sư
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $npc_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function join(TcpConnection $connection, Request $request, int $npc_id)
    {
        $npc = Helpers::getNpcRowByNpcId($npc_id);

        if ($request->roleRow->sect_id != 0 && $npc->sect_id != $request->roleRow->sect_id) {
            $message = 'Ngươi không thể bái nhập mặt khác môn phái!';
            goto END;
        }
        if ($request->roleRow->sect_id != 0 && $npc->seniority >= $request->roleRow->seniority) {
            $message = 'Ngươi không thể bái so ngươi bối phận thấp nhân vi sư!';
            goto END;
        }
        if ($npc->sect_id == 3) {
            // 五岳
            $conditions = [1 => 0, 193 => 40, 187 => 80, 195 => 120, 197 => 160,];
            
            // 单项技能检测
            $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` IN (1, 5, 6, 7, 8, 9);
SQL;

            $role_skills_st = db()->query($sql);
            $role_skills = $role_skills_st->fetchAll(\PDO::FETCH_ASSOC);
            $role_skills_st->closeCursor();
            $role_skills = array_column($role_skills, 'lv', 'id');
            if (max($role_skills) < $conditions[$npc->id]) {
                $message = 'Ngươi còn chưa đạt tới bái nhập ' . $npc->name . 'Môn hạ tư cách, nỗ lực tăng lên chính mình đi.';
                goto END;
            };


            // 180级 等级检测
            $levels = [190 => 180,];
            if ($levels[$npc->id] >= 180) {
            // 所有技能
                $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` IN (1, 5, 6, 7, 8, 9);
SQL;

                $role_skills_st = db()->query($sql);
                $role_skills = $role_skills_st->fetchAll(\PDO::FETCH_ASSOC);
                $role_skills_st->closeCursor();
                $role_skills = array_column($role_skills, 'lv', 'id');
                if (count($role_skills) < 6 || max($role_skills) < 180 || min($role_skills) < 180) {
                    $message = 'Ngươi kiến thức cơ bản cập đối ứng môn phái kỹ năng bất mãn 180 cấp, vô pháp bái nhập ' . $npc->name . ' Môn hạ, nỗ lực tăng lên chính mình đi.';
                    goto END;
                }
            };
            
            
                        // 500级 等级检测
            $levels = [ 198 => 500,];
            if ($levels[$npc->id] >= 500) {
            // 全技能检测
            $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` IN (1, 5, 6, 7, 8, 9);
SQL;

            $role_skills_st = db()->query($sql);
            $role_skills = $role_skills_st->fetchAll(\PDO::FETCH_ASSOC);
            $role_skills_st->closeCursor();
            $role_skills = array_column($role_skills, 'lv', 'id');
            if (count($role_skills) < 6 || max($role_skills) < 500 || min($role_skills) < 500) {
              $message = 'Ngươi kiến thức cơ bản cập đối ứng môn phái kỹ năng bất mãn 500 cấp, vô pháp bái nhập ' . $npc->name . ' Môn hạ, nỗ lực tăng lên chính mình đi.';
                goto END;
                  }
            };
            $seniority = $npc->seniority + 1;
            //  bái 入
            $sql = <<<SQL
UPDATE `roles` SET `sect_id` = $npc->sect_id, `seniority` = $seniority, `master` = $npc->id WHERE `id` = $request->roleId;
SQL;


            Helpers::execSql($sql);

            // 更新 row
            $request->roleRow->sect_id = $npc->sect_id;
            $request->roleRow->seniority = $seniority;
            $request->roleRow->master = $npc->id;

            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        } elseif ($npc->sect_id == 2) {
            
            
            //Thiếu Lâm
            $conditions = [
                3   => 0,
                114 => 40, 113 => 40,
                128 => 80, 131 => 80,
                120 => 120, 134 => 120, 126 => 120, 118 => 120, 127 => 120, 132 => 120,
                507 => 160, 506 => 160, 583 => 160, 585 => 160, 115 => 160, 579 => 160, 580 => 160, 581 => 160,
            ];
            // 单项技能检测
            $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` IN (1, 5, 6, 10, 11, 12);
SQL;

            $role_skills_st = db()->query($sql);
            $role_skills = $role_skills_st->fetchAll(\PDO::FETCH_ASSOC);
            $role_skills_st->closeCursor();
            $role_skills = array_column($role_skills, 'lv', 'id');


            if (max($role_skills) < $conditions[$npc->id]) {
                $message = 'Ngươi còn chưa đạt tới bái nhập ' . $npc->name . 'Môn hạ tư cách, nỗ lực tăng lên chính mình đi.';
                goto END;
            };

            // 180级检测
             $levels = [129 => 180, 130 => 180,];
            if($levels[$npc->id] >= 180) {
            // 所有技能
                $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` IN (1, 5, 6, 10, 11, 12);
SQL;

                $role_skills_st = db()->query($sql);
                $role_skills = $role_skills_st->fetchAll(\PDO::FETCH_ASSOC);
                $role_skills_st->closeCursor();
                $role_skills = array_column($role_skills, 'lv', 'id');
                if (count($role_skills) < 6 || max($role_skills) < 180 || min($role_skills) < 180) {
                    $message = 'Ngươi kiến thức cơ bản cập đối ứng môn phái kỹ năng bất mãn 180 cấp, vô pháp bái nhập ' . $npc->name . ' Môn hạ, nỗ lực tăng lên chính mình đi.';
                    goto END;
                }
            };
            
            //500级检测
             $levels = [833 => 500,];
            if($levels[$npc->id] >= 500) {
            // 所有技能
                $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` IN (1, 5, 6, 10, 11, 12);
SQL;

                $role_skills_st = db()->query($sql);
                $role_skills = $role_skills_st->fetchAll(\PDO::FETCH_ASSOC);
                $role_skills_st->closeCursor();
                $role_skills = array_column($role_skills, 'lv', 'id');
                if (count($role_skills) < 6 || max($role_skills) < 500 || min($role_skills) < 500) {
                    $message = 'Ngươi kiến thức cơ bản cập đối ứng môn phái kỹ năng bất mãn 500 cấp, vô pháp bái nhập ' . $npc->name . ' Môn hạ, nỗ lực tăng lên chính mình đi.';
                    goto END;
                }
            };
            $seniority = $npc->seniority + 1;
            //  bái 入
            $sql = <<<SQL
UPDATE `roles` SET `sect_id` = $npc->sect_id, `seniority` = $seniority, `master` = $npc->id WHERE `id` = $request->roleId;
SQL;


            Helpers::execSql($sql);

            // 更新 row
            $request->roleRow->sect_id = $npc->sect_id;
            $request->roleRow->seniority = $seniority;
            $request->roleRow->master = $npc->id;

            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);

        } elseif ($npc->sect_id == 1) {
            
            //Ma giáo
            $conditions = [
                2  => 0,
                48 => 40, 49 => 40, 51 => 40,
                54 => 80, 52 => 80, 53 => 80, 55 => 80,
                59 => 120,
                58 => 160,
            ];
            // 单项技能检测
            $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` IN (2, 5, 6, 13, 14, 15);
SQL;

            $role_skills_st = db()->query($sql);
            $role_skills = $role_skills_st->fetchAll(\PDO::FETCH_ASSOC);
            $role_skills_st->closeCursor();
            $role_skills = array_column($role_skills, 'lv', 'id');
            if (max($role_skills) < $conditions[$npc->id]) {
                $message = 'Ngươi còn chưa đạt tới bái nhập ' . $npc->name . 'Môn hạ tư cách, nỗ lực tăng lên chính mình đi.';
                goto END;
            };
            
            // 180级检测
             $levels = [ 57 => 180,];
            if($levels[$npc->id] >= 180) {
            // 所有技能
                $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` IN (2, 5, 6, 13, 14, 15);
SQL;

                $role_skills_st = db()->query($sql);
                $role_skills = $role_skills_st->fetchAll(\PDO::FETCH_ASSOC);
                $role_skills_st->closeCursor();
                $role_skills = array_column($role_skills, 'lv', 'id');
                if (count($role_skills) < 6 || max($role_skills) < 180 || min($role_skills) < 180) {
                    $message = 'Ngươi kiến thức cơ bản cập đối ứng môn phái kỹ năng bất mãn 180 cấp, vô pháp bái nhập ' . $npc->name . ' Môn hạ, nỗ lực tăng lên chính mình đi.';
                    goto END;
                }
            };


            // 500级检测
             $levels = [ 832 => 500,];
            if($levels[$npc->id] >= 500) {
            // 所有技能
                $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` IN (2, 5, 6, 13, 14, 15);
SQL;

                $role_skills_st = db()->query($sql);
                $role_skills = $role_skills_st->fetchAll(\PDO::FETCH_ASSOC);
                $role_skills_st->closeCursor();
                $role_skills = array_column($role_skills, 'lv', 'id');
                if (count($role_skills) < 6 || max($role_skills) < 500 || min($role_skills) < 500) {
                    $message = 'Ngươi kiến thức cơ bản cập đối ứng môn phái kỹ năng bất mãn 500 cấp, vô pháp bái nhập ' . $npc->name . ' Môn hạ, nỗ lực tăng lên chính mình đi.';
                    goto END;
                }
            };

            $seniority = $npc->seniority + 1;
            //  bái 入
            $sql = <<<SQL
UPDATE `roles` SET `sect_id` = $npc->sect_id, `seniority` = $seniority, `master` = $npc->id WHERE `id` = $request->roleId;
SQL;


            Helpers::execSql($sql);

            // 更新 row
            $request->roleRow->sect_id = $npc->sect_id;
            $request->roleRow->seniority = $seniority;
            $request->roleRow->master = $npc->id;

            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);

        }

        END:
        return $connection->send(\cache_response($request, \view('Map/Master/join.twig', [
            'request'   => $request,
            'message'   => $message ?? false,
            'sect'      => Helpers::getSect($npc->sect_id),
            'npc'       => $npc,
            'seniority' => $seniority ?? null,
        ])));
    }


    /**
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $npc_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function learn(TcpConnection $connection, Request $request, int $npc_id)
    {
        $npc = Helpers::getNpcRowByNpcId($npc_id);
        $skills = json_decode($npc->master_skills, true);
        foreach ($skills as $key => $skill) {
            $skills[$key]['row'] = Helpers::getSkillRowBySkillId($skill['skill_id']);
            $skills[$key]['learnUrl'] = 'Map/Master/learnSkill/' . $npc->id . '/' . $skill['skill_id'] . '/' . $skill['skill_lv'];
        }

        return $connection->send(\cache_response($request, \view('Map/Master/learn.twig', [
            'request' => $request,
            'npc'     => $npc,
            'skills'  => $skills,
            'number'  => count($skills),
        ])));
    }


    /**
     * 学习技能
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $npc_id
     * @param int           $skill_id 技能库 ID
     * @param int           $skill_lv
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function learnSkill(TcpConnection $connection, Request $request, int $npc_id, int $skill_id, int $skill_lv)
    {
        $npc = Helpers::getNpcRowByNpcId($npc_id);
        $skill = Helpers::getSkillRowBySkillId($skill_id);
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);

        // 判断Tinh thần是否足够
        if ($role_attrs->jingshen <= 0) {
            $message = 'Ngươi quá mệt mỏi, cái gì cũng không có học được.';
            goto END;
        }

        // 判断Nội lực是否足够
        if ($role_attrs->qianneng <= 0) {
            $message = 'Ngươi tiềm năng không đủ, thông qua chiến đấu có thể đạt được càng nhiều tiềm năng.';
            goto END;
        }

        // 获取玩家技能
        $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` = $skill_id;
SQL;

        $role_skill = Helpers::queryFetchObject($sql);

        // 技能是否已经学习
        if (!$role_skill) {
            $sql = <<<SQL
INSERT INTO `role_skills` (`skill_id`, `role_id`, lv) VALUES ($skill_id, $request->roleId, 1);
SQL;


            Helpers::execSql($sql);

            // 再次获取玩家技能
            $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` = $skill_id;
SQL;

            $role_skill = Helpers::queryFetchObject($sql);
        }

        // 判断技能等级
        if ($role_skill->lv >= $skill_lv) {
            $message = 'Sư phụ đã không có gì có thể truyền thụ cho ngươi.';
            goto END;
        }

        if (pow($role_skill->lv / 10, 3) > $role_attrs->experience / 1000) {
            $message = 'Ngươi tu vi quá thấp, không thể học tập càng cao thâm võ công.';
            goto END;
        }

        // 计算升一级的经验
        if ($skill->is_base) {
            $need_experience = $role_skill->lv * $role_skill->lv;
        } else {
            $need_experience = intval(ceil(1.2 * $role_skill->lv * $role_skill->lv));
        }
        $experience = $need_experience - $role_skill->experience;

        if ($role_attrs->jingshen <= $role_attrs->qianneng) {
            //用Tinh thần比较
            $value = $role_attrs->jingshen;
        } else {
            //用Nội lực比较
            $value = $role_attrs->qianneng;
        }

        if ($value > $experience) {
            $value = $experience;
        }

        $role_attrs->jingshen -= $value;
        $role_attrs->qianneng -= $value;
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
        if ($value < $experience) {
            // 全部使用光，并且不升级
            $sql = <<<SQL
UPDATE `role_skills` SET `experience` = `experience` + $value WHERE `role_id` = $request->roleId AND `skill_id` = $skill_id;
SQL;


            Helpers::execSql($sql);

            $lv = $role_skill->lv;
            $percent = Helpers::getPercent($role_skill->experience + $value, $need_experience);
        } else {
            // 使用光，升级
            $sql = <<<SQL
UPDATE `role_skills` SET `experience` = 0, `lv` = `lv` + 1 WHERE `role_id` = $request->roleId AND `skill_id` = $skill_id;
SQL;


            Helpers::execSql($sql);

            $lv = $role_skill->lv + 1;
            $percent = 0;
            FlushRoleAttrs::fromRoleSkillByRoleId($request->roleId);
        }
        END:
        return $connection->send(\cache_response($request, \view('Map/Master/learnSkill.twig', [
            'request'     => $request,
            'npc'         => $npc,
            'skill'       => $skill,
            'backUrl'     => 'Map/Master/learn/' . $npc->id,
            'continueUrl' => 'Map/Master/learnSkill/' . $npc->id . '/' . $skill_id . '/' . $skill_lv,
            'lv'          => $lv ?? 0,
            'percent'     => $percent ?? 0,
            'message'     => $message ?? 0,
        ])));
    }


    /**
     * 叛师
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $npc_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function leave(TcpConnection $connection, Request $request, int $npc_id)
    {
        $npc = Helpers::getNpcRowByNpcId($npc_id);

        //判断Tu vi
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->experience / 1000 < 100) {
            goto LEAVE;
        }
        // 是否第一次
        if ($request->roleRow->leave_timestamp <= 0) {
            // Cho冷静期
            $timestamp = time();
            $sql = <<<SQL
UPDATE `roles` SET `leave_timestamp` = $timestamp WHERE `id` = $request->roleId;
SQL;


            Helpers::execSql($sql);

            $request->roleRow->leave_timestamp = $timestamp;
            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
            $message = 'Tu vi vượt qua một trăm năm phản bội sư yêu cầu bình tĩnh 24 giờ, thỉnh 24 giờ sau lại làm tính toán!';
            goto END;
        }
        if (time() - $request->roleRow->leave_timestamp < 86400) {
            $message = 'Tu vi vượt qua một trăm năm phản bội sư yêu cầu bình tĩnh 24 giờ, thỉnh 24 giờ sau lại làm tính toán!';
            goto END;
        }
        if (time() - $request->roleRow->leave_timestamp >= 86400) {
            LEAVE:
            // 脱离门派
            // 修改门派 辈分 师父 冷静期
            $sql = <<<SQL
UPDATE `roles` SET `leave_timestamp` = 0, `seniority` = 0, `sect_id` = 0, `master` = 0 WHERE `id` = $request->roleId;
SQL;


            Helpers::execSql($sql);

            $request->roleRow->leave_timestamp = 0;
            $request->roleRow->seniority = 0;
            $request->roleRow->sect_id = 0;
            $request->roleRow->master = 0;
            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
            // 技能减半
            if ($npc->sect_id == 1) {
                $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` IN (13, 14, 15);
SQL;

            } elseif ($npc->sect_id == 2) {
                $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` IN (10, 11, 12);
SQL;

            } else {
                $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` IN (7, 8, 9);
SQL;

            }

            $role_skills = Helpers::queryFetchAll($sql);
            foreach ($role_skills as $role_skill) {
                $experience = intval(ceil(Helpers::getSkillTotalExperience($role_skill->lv) + $role_skill->experience / 2));
                $skill = Helpers::getSkillTotalLv($experience, 0);
                $sql = <<<SQL
UPDATE `role_skills` SET `experience` = {$skill['exp']}, `lv` = {$skill['lv']} WHERE `id` = $role_skill->id; 
SQL;


                Helpers::execSql($sql);

            }

            FlushRoleAttrs::fromRoleSkillByRoleId($request->roleId);

            $message = 'Ngươi đã rời đi ' . Helpers::getSect($npc->sect_id) . '！';
        }
        END:
        return $connection->send(\cache_response($request, \view('Map/Master/leave.twig', [
            'request' => $request,
            'npc'     => $npc,
            'message' => $message ?? null,
        ])));
    }


    /**
     * 叛师Xác nhận
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $npc_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function leaveQuestion(TcpConnection $connection, Request $request, int $npc_id)
    {
        $npc = Helpers::getNpcRowByNpcId($npc_id);
        return $connection->send(\cache_response($request, \view('Map/Master/leaveQuestion.twig', [
            'request'  => $request,
            'npc'      => $npc,
            'leaveUrl' => 'Map/Master/leave/' . $npc_id,
        ])));
    }
}
