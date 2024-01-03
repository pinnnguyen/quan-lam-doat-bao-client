<?php


namespace App\Libs\Events\Timers;


use App\Libs\Helpers;


/**
 * 重新生成排行榜
 *
 */
class RegenerateTopTimer
{
    public static function hookV1()
    {
        /**
         * 高手如云
         * 获取技能等级最高的一百位玩家、查询这一百位玩家的技能等级、分配进一个数组、所有玩家
         */
        $sql = <<<SQL
SELECT `role_skills`.`id`, `set_role_skill_id`, `role_id`, `lv`, `skill_id`, `name` FROM `role_skills` INNER JOIN `roles` ON `role_skills`.`role_id` = `roles`.`id`;
SQL;

        $all_role_skills_st = db()->query($sql);
        $all_role_skills = $all_role_skills_st->fetchAll(\PDO::FETCH_ASSOC);
        $all_role_skills_st->closeCursor();
        $role_skillss = [];
        foreach ($all_role_skills as $all_role_skill) {
            $role_skillss[$all_role_skill['role_id']][] = $all_role_skill;
        }
        $top_gaoshou_roles = [];
        foreach ($role_skillss as $role_skills) {
            $top_gaoshou_roles[] = [
                'id'    => $role_skills[0]['role_id'],
                'name'  => $role_skills[0]['name'],
                'value' => self::getComprehensiveSkillLv($role_skills),
            ];
        }
        $sorts = array_column($top_gaoshou_roles, 'value');
        array_multisort($sorts, SORT_DESC, $top_gaoshou_roles);
        $top_gaoshou_roles = array_slice($top_gaoshou_roles, 0, 100);
        $top = 1;
        $top_gaoshou_roles_id = [];
        foreach ($top_gaoshou_roles as $key => $top_gaoshou_role) {
            $top_gaoshou_roles[$key]['top'] = Helpers::getHansNumber($top);
            $top_gaoshou_roles_id[] = $top_gaoshou_role['id'];
            $top++;
        }
        cache()->set('top_gaoshou_roles', $top_gaoshou_roles);
        cache()->set('top_gaoshou_roles_id', $top_gaoshou_roles_id);
		
	

        /**
         * 富甲天下
         *
         */
        $sql = <<<SQL
SELECT `roles`.`id`, `roles`.`name`, IFNULL(`role_things`.`number`, 0) + `roles`.`bank_balance` AS `wealth`
FROM `roles`
LEFT JOIN `role_things` ON `role_things`.`role_id` = `roles`.`id` AND `role_things`.`thing_id` = 213
INNER JOIN `users` ON `roles`.`user_id` = `users`.`id` AND `users`.`is_ban` != 1
ORDER BY `wealth` DESC
LIMIT 100;
SQL;
        $roles_st = db()->query($sql);
        $roles = $roles_st->fetchAll(\PDO::FETCH_ASSOC);
        $roles_st->closeCursor();
        $mid = array_column($roles, 'wealth');
        array_multisort($mid, SORT_DESC, SORT_NUMERIC, $roles);
        $top = 1;
        foreach ($roles as $key => $role) {
            $roles[$key]['value'] = Helpers::getHansMoney($role['wealth']);
            $roles[$key]['top'] = Helpers::getHansNumber($top);
            $top++;
        }
        cache()->set('top_fujia_roles', $roles);

        /**
         * 冷血杀手
         *
         */
        $sql = <<<SQL
SELECT `id`, `name`, `kills` FROM `roles` ORDER BY `kills` DESC LIMIT 100;
SQL;

        $roles_st = db()->query($sql);
        $roles = $roles_st->fetchAll(\PDO::FETCH_ASSOC);
        $roles_st->closeCursor();
        $top = 1;
        foreach ($roles as $key => $role) {
            $roles[$key]['value'] = $role['kills'];
            $roles[$key]['top'] = Helpers::getHansNumber($top);
            $top++;
        }
        cache()->set('top_lengxue_roles', $roles);

        /**
         * 绝世美女
         *
         */
        $sql = <<<SQL
SELECT `id`, `name`, `charm` FROM `roles` WHERE `gender` = '女' ORDER BY `charm` DESC LIMIT 100;
SQL;

        $roles_st = db()->query($sql);
        $roles = $roles_st->fetchAll(\PDO::FETCH_ASSOC);
        $roles_st->closeCursor();
        $top = 1;
        foreach ($roles as $key => $role) {
            $roles[$key]['value'] = $role['charm'];
            $roles[$key]['top'] = Helpers::getHansNumber($top);
            $top++;
        }
        cache()->set('top_jueshi_roles', $roles);

        /**
         * 风流倜傥
         *
         */
        $sql = <<<SQL
SELECT `id`, `name`, `charm` FROM `roles` WHERE `gender` = '男' ORDER BY `charm` DESC LIMIT 100;
SQL;

        $roles_st = db()->query($sql);
        $roles = $roles_st->fetchAll(\PDO::FETCH_ASSOC);
        $roles_st->closeCursor();
        $top = 1;
        foreach ($roles as $key => $role) {
            $roles[$key]['value'] = $role['charm'];
            $roles[$key]['top'] = Helpers::getHansNumber($top);
            $top++;
        }
        cache()->set('top_fengliu_roles', $roles);

        /**
         * 风流倜傥
         *
         */
        $sql = <<<SQL
SELECT `id`, `name`, `vip_score` FROM `roles` ORDER BY `vip_score` DESC LIMIT 50;
SQL;

        $roles_st = db()->query($sql);
        $roles = $roles_st->fetchAll(\PDO::FETCH_ASSOC);
        $roles_st->closeCursor();
        $top = 1;
        foreach ($roles as $key => $role) {
            $roles[$key]['value'] = $role['vip_score'];
            $roles[$key]['top'] = Helpers::getHansNumber($top);
            $top++;
        }
        cache()->set('top_vip_roles', $roles);
    }

    public static function hook()
    {
        /**
         * 高手如云
         * 获取技能等级最高的一百位玩家、查询这一百位玩家的技能等级、分配进一个数组、所有玩家
         */
        $sql = "select id,`name`,experience,saved_experience from roles";
        $all_role_skills_st = db()->query($sql);
        $all_role_skills = $all_role_skills_st->fetchAll(\PDO::FETCH_ASSOC);
        $all_role_skills_st->closeCursor();

        $top_gaoshou_roles = [];
        foreach ($all_role_skills as $role) {
            $role['value'] = $role['experience'] + $role['saved_experience'];
            $top_gaoshou_roles[] = $role;
        }
        $sorts = array_column($top_gaoshou_roles, 'value');
        array_multisort($sorts, SORT_DESC, $top_gaoshou_roles);
        $top_gaoshou_roles = array_slice($top_gaoshou_roles, 0, 100);
        $top = 1;
        $top_gaoshou_roles_id = [];
        foreach ($top_gaoshou_roles as $key => $top_gaoshou_role) {
            $top_gaoshou_roles[$key]['top'] = Helpers::getHansNumber($top);
            $top_gaoshou_roles_id[] = $top_gaoshou_role['id'];
            $top++;
        }
        cache()->set('top_gaoshou_roles', $top_gaoshou_roles);
        cache()->set('top_gaoshou_roles_id', $top_gaoshou_roles_id);



        /**
         * 富甲天下
         *
         */
        $sql = <<<SQL
SELECT `roles`.`id`, `roles`.`name`, IFNULL(`role_things`.`number`, 0) + `roles`.`bank_balance` AS `wealth`
FROM `roles`
LEFT JOIN `role_things` ON `role_things`.`role_id` = `roles`.`id` AND `role_things`.`thing_id` = 213
INNER JOIN `users` ON `roles`.`user_id` = `users`.`id` AND `users`.`is_ban` != 1
ORDER BY `wealth` DESC
LIMIT 100;
SQL;
        $roles_st = db()->query($sql);
        $roles = $roles_st->fetchAll(\PDO::FETCH_ASSOC);
        $roles_st->closeCursor();
        $mid = array_column($roles, 'wealth');
        array_multisort($mid, SORT_DESC, SORT_NUMERIC, $roles);
        $top = 1;
        foreach ($roles as $key => $role) {
            $roles[$key]['value'] = Helpers::getHansMoney($role['wealth']);
            $roles[$key]['top'] = Helpers::getHansNumber($top);
            $top++;
        }
        cache()->set('top_fujia_roles', $roles);


        /**
         * 武功盖世
         * 获取技能等级最高的一百位玩家、查询这一百位玩家的技能等级、分配进一个数组、所有玩家
         */
        $sql = <<<SQL
SELECT `role_skills`.`id`, `set_role_skill_id`, `role_id`, `lv`, `skill_id`, `name` FROM `role_skills` INNER JOIN `roles` ON `role_skills`.`role_id` = `roles`.`id`;
SQL;

        $all_role_skills_st = db()->query($sql);
        $all_role_skills = $all_role_skills_st->fetchAll(\PDO::FETCH_ASSOC);
        $all_role_skills_st->closeCursor();
        $role_skillss = [];
        foreach ($all_role_skills as $all_role_skill) {
            $role_skillss[$all_role_skill['role_id']][] = $all_role_skill;
        }
        $top_wugong_roles = [];
        foreach ($role_skillss as $role_skills) {
            $top_wugong_roles[] = [
                'id'    => $role_skills[0]['role_id'],
                'name'  => $role_skills[0]['name'],
                'value' => self::getComprehensiveSkillLv($role_skills),
            ];
        }
        $sorts = array_column($top_wugong_roles, 'value');
        array_multisort($sorts, SORT_DESC, $top_wugong_roles);
        $top_wugong_roles = array_slice($top_wugong_roles, 0, 100);
        $top = 1;
        $top_wugong_roles_id = [];
        foreach ($top_wugong_roles as $key => $top_wugong_role) {
            $top_wugong_roles[$key]['top'] = Helpers::getHansNumber($top);
            $top_wugong_roles_id[] = $top_wugong_role['id'];
            $top++;
        }
        cache()->set('top_wugong_roles', $top_wugong_roles);
        cache()->set('top_wugong_roles_id', $top_wugong_roles_id);


        /**
         * 冷血杀手
         *
         */
        $sql = <<<SQL
SELECT `id`, `name`, `kills` FROM `roles` ORDER BY `kills` DESC LIMIT 100;
SQL;

        $roles_st = db()->query($sql);
        $roles = $roles_st->fetchAll(\PDO::FETCH_ASSOC);
        $roles_st->closeCursor();
        $top = 1;
        foreach ($roles as $key => $role) {
            $roles[$key]['value'] = $role['kills'];
            $roles[$key]['top'] = Helpers::getHansNumber($top);
            $top++;
        }
        cache()->set('top_lengxue_roles', $roles);

        /**
         * 绝世美女
         *
         */
        $sql = <<<SQL
SELECT `id`, `name`, `charm` FROM `roles` WHERE `gender` = '女' ORDER BY `charm` DESC LIMIT 100;
SQL;

        $roles_st = db()->query($sql);
        $roles = $roles_st->fetchAll(\PDO::FETCH_ASSOC);
        $roles_st->closeCursor();
        $top = 1;
        foreach ($roles as $key => $role) {
            $roles[$key]['value'] = $role['charm'];
            $roles[$key]['top'] = Helpers::getHansNumber($top);
            $top++;
        }
        cache()->set('top_jueshi_roles', $roles);

        /**
         * 风流倜傥
         *
         */
        $sql = <<<SQL
SELECT `id`, `name`, `charm` FROM `roles` WHERE `gender` = '男' ORDER BY `charm` DESC LIMIT 100;
SQL;

        $roles_st = db()->query($sql);
        $roles = $roles_st->fetchAll(\PDO::FETCH_ASSOC);
        $roles_st->closeCursor();
        $top = 1;
        foreach ($roles as $key => $role) {
            $roles[$key]['value'] = $role['charm'];
            $roles[$key]['top'] = Helpers::getHansNumber($top);
            $top++;
        }
        cache()->set('top_fengliu_roles', $roles);

        /**
         * vip排行
         *
         */
        $sql = <<<SQL
SELECT `id`, `name`, `vip_score` FROM `roles` ORDER BY `vip_score` DESC LIMIT 50;
SQL;

        $roles_st = db()->query($sql);
        $roles = $roles_st->fetchAll(\PDO::FETCH_ASSOC);
        $roles_st->closeCursor();
        $top = 1;
        foreach ($roles as $key => $role) {
            $roles[$key]['value'] = $role['vip_score'];
            $roles[$key]['top'] = Helpers::getHansNumber($top);
            $top++;
        }
        cache()->set('top_vip_roles', $roles);

        /**
         * 心法排行
         */
        $sql = <<<SQL
SELECT distinct xf.id, xf.`name`, rxf.max_lv FROM `xinfas` xf left join role_xinfas rxf on xf.id = rxf.xinfa_id where xf.experience = 512  ORDER BY rxf.max_lv DESC LIMIT 50;
SQL;

        $roles_st = db()->query($sql);
        $roles512 = $roles_st->fetchAll(\PDO::FETCH_ASSOC);
        $roles_st->closeCursor();

        $sql = <<<SQL
SELECT distinct xf.id, xf.`name`, rxf.max_lv FROM `xinfas` xf left join role_xinfas rxf on xf.id = rxf.xinfa_id where xf.experience = 216  ORDER BY rxf.max_lv DESC LIMIT 50;
SQL;

        $roles_st = db()->query($sql);
        $roles216 = $roles_st->fetchAll(\PDO::FETCH_ASSOC);
        $roles_st->closeCursor();

        $roles = array_merge($roles512,$roles216);


        $arrValue = array_column($roles,'max_lv');
        array_multisort($arrValue,SORT_DESC,$roles);

        $top = 1;
        foreach ($roles as $key => $role) {
            $roles[$key]['value'] = $role['max_lv'];
            $roles[$key]['top'] = Helpers::getHansNumber($top);
            $top++;
        }


        cache()->set('top_xinfa_roles', $roles);


    }


    /**
     * 获取综合武功和等级
     *
     * @param array $role_skills
     *
     * @return int
     */
    public static function getComprehensiveSkillLv(array &$role_skills): int
    {
        $base_quanjiao_lv = 0;
        $base_daofa_lv = 0;
        $base_jianfa_lv = 0;
        $base_neigong_lv = 0;
        $base_qinggong_lv = 0;
        $base_zhaojia_lv = 0;
        $sect_qinggong_lv = 0;
        $sect_zhaojia_lv = 0;
        $sect_quanjiao_lv = 0;
        $sect_daofa_lv = 0;
        $sect_jianfa_lv = 0;
        $role_skills = array_column($role_skills, null, 'id');
        foreach ($role_skills as $role_skill) {
            $skill = Helpers::getSkillRowBySkillId($role_skill['skill_id']);
            if (!empty($skill->is_base)) {
                switch ($skill->kind) {
                    case '拳脚':
                        $base_quanjiao_lv = $role_skill['lv'];
                        if ($role_skill['set_role_skill_id'] > 0) {
                            $sect_quanjiao_lv = $role_skills[$role_skill['set_role_skill_id']]['lv'];
                        }
                        break;
                    case '刀法':
                        $base_daofa_lv = $role_skill['lv'];
                        if ($role_skill['set_role_skill_id'] > 0) {
                            $sect_daofa_lv = $role_skills[$role_skill['set_role_skill_id']]['lv'];
                        }
                        break;
                    case '剑法':
                        $base_jianfa_lv = $role_skill['lv'];
                        if ($role_skill['set_role_skill_id'] > 0) {
                            $sect_jianfa_lv = $role_skills[$role_skill['set_role_skill_id']]['lv'];
                        }
                        break;
                    case '内功':
                        $base_neigong_lv = $role_skill['lv'];
                        break;
                    case '轻功':
                        $base_qinggong_lv = $role_skill['lv'];
                        if ($role_skill['set_role_skill_id'] > 0) {
                            $sect_qinggong_lv = $role_skills[$role_skill['set_role_skill_id']]['lv'];
                        }
                        break;
                    case '招架':
                        $base_zhaojia_lv = $role_skill['lv'];
                        if ($role_skill['set_role_skill_id'] > 0) {
                            $sect_zhaojia_lv = $role_skills[$role_skill['set_role_skill_id']]['lv'];
                        }
                        break;
                }
            }
        }
        return (int)max([
            $base_jianfa_lv * 0.5 + $sect_jianfa_lv,
            $base_quanjiao_lv * 0.5 + $sect_quanjiao_lv,
            $base_daofa_lv * 0.5 + $sect_daofa_lv,
            $base_zhaojia_lv * 0.5 + $sect_zhaojia_lv,
            $base_qinggong_lv * 0.5 + $sect_qinggong_lv,
            $base_neigong_lv * 0.5,
        ]);
    }
}
