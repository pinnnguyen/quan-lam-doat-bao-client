<?php

namespace App\Libs\Events\Initializers;

use App\Libs\Objects\SkillRow;
use PDO;

/**
 * 初始化技能
 */
class SkillInitializer
{
    public static function hook()
    {
        /**
         * 获取所有技能
         */
        $sql = <<<SQL
SELECT * FROM `skills`;
SQL;

        $skills_st = db()->query($sql);
        $skills = $skills_st->fetchAll(PDO::FETCH_CLASS, SkillRow::class);
        $skills_st->closeCursor();

        /**
         * 缓存所有技能
         */
        cache()->set('skills', array_column($skills, null, 'id'));
    }
}
