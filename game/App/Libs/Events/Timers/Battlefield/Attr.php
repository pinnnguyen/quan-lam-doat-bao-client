<?php

namespace App\Libs\Events\Timers\Battlefield;

use App\Libs\Attrs\RoleAttrs;
use App\Libs\Attrs\TrickAttrs;
use App\Libs\Attrs\XinfaTrickAttrs;
use App\Libs\Helpers;
use ReflectionObject;

/**
 * 战场属性
 *
 */
class Attr
{
    /**
     * 攻击部位
     *
     * @var array|string[]
     */
    public static array $positions = [
        '左手', '右手', '左臂', '右臂', '左腿', '右腿', '左眼', '右眼', '左耳', '右耳', '左脚', '右脚',
        '左肩', '右肩', '胸部', '腰部', '颈部', '头部', '裆部', '大腿', '后脑', '面门', '双眼', '面门',
    ];


    /**
     * 获取一个部位
     *
     * @return string
     */
    public static function getPosition(): string
    {
        return self::$positions[array_rand(self::$positions)];
    }


    /**
     * 普通攻击动作描述
     *
     * @var array|string[]
     */
    public static array $ordinaryAttackActionDesc = [
        '$M提起拳头往$O的$P锤去，',
        '$M对准$O的$P用力挥出一拳，',
        '$M纵身轻轻跃起，兵刃光芒如轮疾转，霍霍斩向$O的$P，',
        '$M用$W往$O的$P砍去，',
    ];

    /**
     * 普通攻击结果描述
     *
     * @var array|string[]
     */
    public static array $ordinaryAttackResultDesc = [
        '结果一击命中，$O闷哼一声，显然是吃了不小的亏！',
        '结果$O躲闪不及，重重地挨下了这招！',
        '结果一击命中，$O的$P被打得鲜血飞溅！',
        '结果「嗤」地一声划出一道血淋淋的伤口！',
        '结果在$O的$P刺出一个创口！',
        '结果「噗」地一声刺入了$O$P寸许！',
        '结果「噗」地一声刺进$O的$P，使$O不由自主地退了几步！',
        '结果「嗤」地一声划出一道伤口！',
    ];

    /**
     * 格挡结果描述
     *
     * @var array|array[]
     */
    public static array $blockResultDesc = [
        '0' => [
            '结果$O双拳格挡，接下了这一招。',
            '结果$O双手格挡，接下了这一招。',
            '结果$O双掌一错，接下了这一招。',
        ],
        '3' => [
            '结果$O双拳格挡，接下了这一招。',
            '结果$O双手格挡，接下了这一招。',
            '结果$O双掌一错，接下了这一招。',
        ],
        '1' => [
            '结果$O横刀一挡，接下了这一招。',
        ],
        '2' => [
            '结果$O横剑一挡，接下了这一招。',
        ],
    ];


    /**
     * 获取格挡描述
     *
     * @param int $weapon_kind
     *
     * @return string
     */
    public static function getBlockDesc(int $weapon_kind = 0): string
    {
        return self::$blockResultDesc[$weapon_kind][array_rand(self::$blockResultDesc[$weapon_kind])];
    }


    /**
     * 轻功结果描述
     *
     * @var array|array[]
     */
    public static array $qinggongDesc = [
        '0' => [
            '结果$O身子一侧，巧妙地躲过了$M这一击。',
            '结果$O轻轻一跃，闪到一旁，避开了$M的攻击。',
        ],
        '1' => [
            '结果$O一招「嫦娥奔月」，轻轻一纵，优雅的自$M头顶越过！',
            '结果$O一式「天女散花」，自水袖中散出一片花雨，$M被花影迷蒙了双眼，与$O擦肩而过！',
            '结果$O双脚微动翩然起舞，一招「双成献舞」,堪堪闪过$M的这招。',
            '结果$O双袖一拂，使出一招「织女于归」，躲开了$M的攻击。',
        ],
        '2' => [
            '结果突然之间，白影急幌，$O向后滑出丈余，立时又回到了原地，躲过了$M这一招。',
            '结果$O手臂回转，在$M手肘下一推，顺势闪到一旁。',
            '结果突然$O从身后拍了一下$M的头，轻轻跃开。',
            '结果$O身形飘忽，有如鬼魅，转了几转移步到$M的身后， 躲过了$M这一招。',
            '结果$M眼睛一花，$O已没了踪影，突然$O从身后拍了一下$M的头，轻轻跃开。',
            '结果$O右手伸出，在$M手腕上迅速无比的一按，顺势跳到一旁。',
        ],
        '3' => [
            '结果$O一招「秋风起兮白云飞」轻轻巧巧的避了开去。',
            '结果$O身影飘忽，轻轻一纵，一招「欢乐极兮哀情多」，避开这一击。',
            '结果$O使出「兰有秀兮菊有芳」，轻轻松松地闪过。',
            '结果$O一招「怀佳人兮不能忘」腾空而起，避了开去。',
            '结果$O一招「萧鼓鸣兮发棹歌」使出，早已绕到$M身后！',
            '结果$O使出一招「泛楼船兮济汾河」，身子轻轻飘了开去。',
            '结果$O身影微动，已经藉一招「横中流兮扬素波」轻轻闪过。',
            '结果$O使出一招「少壮时兮奈老何」，轻松躲开这一击。',

        ],
    ];


    /**
     * 获取闪避描述
     *
     * @param int $sect_id
     *
     * @return string
     */
    public static function getDodgeDesc(int $sect_id = 0): string
    {
        return self::$qinggongDesc[$sect_id][array_rand(self::$qinggongDesc[$sect_id])];
    }


    /**
     * 伤害描述
     *
     * @var string
     */
    public static string $damageDesc = '$M对$O造成了$D点伤害！';

    /**
     * 投降描述
     *
     * @var string
     */
    public static string $surrenderDesc = '$M对$O大喊道：「别打了，别打了，我投降了!」';

    /**
     * 逃跑失败描述
     *
     * @var string
     */
    public static string $escapeFailedDesc = '$M见形势不利，转身欲逃，结果$O早有预料，身形一闪，挡住了$M的去路！';

    /**
     * 击杀描述
     *
     * @var string
     */
    public static string $killDesc = '$M杀死了$O！';

    /**
     * 心法攻击动作描述
     *
     * @var string
     */
    public static string $xinfaDesc = '$M使出一招绝世武功「$N」，击向$O的$P，';

    /**
     * 技能等级
     *
     * @var array|int[]
     */
    public static array $skillTrickLevels = [5, 10, 20, 40, 80, 120, 160, 180, 240, 300, 360, 420, 480, 700, 1000,];

    /**
     * 技能序号
     *
     * @var array|int[]
     */
    public static array $skillTrickNumbers = [
        5   => 1, 10 => 2, 20 => 3, 40 => 4, 80 => 5, 120 => 6, 160 => 7, 180 => 8, 240 => 9, 300 => 10,
        360 => 11, 420 => 12, 480 => 13, 700 => 14, 1000 => 15,
    ];

    /**
     * 技能内力消耗
     *
     * @var array|int[]
     */
    public static array $skillTrickMps = [0, 20, 40, 80, 120, 180, 240, 350, 480, 650, 800, 1200, 1400, 3000, 4500,];

    /**
     * 技能招式
     *
     * @var array|TrickAttrs[][]
     */
    public static array $skillsTricks = [];


    /**
     * 获取技能招式
     *
     * @param int $skill_id
     * @param int $skill_trick_number
     *
     * @return mixed
     */
    public static function getSkillTrick(int $skill_id, int $skill_trick_number): TrickAttrs
    {
        if (empty(self::$skillsTricks[$skill_id][$skill_trick_number])) {
            $row = Helpers::getSkillRowBySkillId($skill_id);
            $ref_obj = new ReflectionObject($row);
            $level = Attr::$skillTrickLevels[$skill_trick_number - 1];
            try {
                $name = $ref_obj->getProperty('lv' . $level . '_name')->getValue($row);
                $damage = $ref_obj->getProperty('lv' . $level . '_damage')->getValue($row);
                $action_description = $ref_obj->getProperty('lv' . $level . '_action_description')->getValue($row);
                $result_description = $ref_obj->getProperty('lv' . $level . '_result_description')->getValue($row);
            } catch (\ReflectionException $e) {
                $name = '';
                $damage = 0;
                $action_description = '';
                $result_description = '';
            }
            $trick = new TrickAttrs();
            $trick->skillId = $skill_id;
            $trick->number = $skill_trick_number;
            $trick->level = $level;
            $trick->name = $name;
            $trick->damage = $damage;
            $trick->mp = self::$skillTrickMps[$skill_trick_number - 1];
            $trick->action_description = $action_description;
            $trick->result_description = $result_description;
            self::$skillsTricks[$skill_id][$skill_trick_number] = $trick;
        }
        return self::$skillsTricks[$skill_id][$skill_trick_number];
    }


    /**
     * 获取随机的技能招式
     *
     * @param int $skill_id
     * @param int $max_trick_number
     *
     * @return mixed|void
     */
    public static function getRandomSkillTrick(int $skill_id, int $max_trick_number)
    {
        // $weight = $max_trick_number * ($max_trick_number + 1) / 2;
        // $rand = mt_rand(1, $weight);
        // for ($i = 1; $i <= $max_trick_number; $i++) {
        //     $weight -= $i;
        //     if ($rand > $weight) {
        //         return self::getSkillTrick($skill_id, $max_trick_number + 1 - $i);
        //     }
        // }
        return self::getSkillTrick($skill_id, mt_rand(1, $max_trick_number));
    }


    /**
     * 心法等级
     *
     * @var array|int[]
     */
    public static array $xinfaTrickLevels = [0, 40, 80, 160, 240, 400, 560, 720, 880, 1000,];

    /**
     * 心法序号
     *
     * @var array|int[]
     */
    public static array $xinfaTrickNumbers = [
        0 => 1, 40 => 2, 80 => 3, 160 => 4, 240 => 5, 400 => 6, 560 => 7, 720 => 8, 880 => 9, 1000 => 10,
    ];

    /**
     * 心法内力消耗
     *
     * @var array|int[]
     */
    public static array $xinfaTrickMps = [0, 50, 100, 300, 500, 800, 1200, 1500, 1800,];

    /**
     * 心法招式
     *
     * @var array|XinfaTrickAttrs[][]
     */
    public static array $xinfasTricks = [];


    /**
     * 获取心法招式
     *
     * @param int $xinfa_id
     * @param int $xinfa_trick_number
     *
     * @return mixed
     */
    public static function getXinfaTrick(int $xinfa_id, int $xinfa_trick_number): XinfaTrickAttrs
    {
        if (empty(self::$xinfasTricks[$xinfa_id][$xinfa_trick_number])) {
            $row = Helpers::getXinfaAttackTrick($xinfa_id);
            $ref_obj = new ReflectionObject($row);
            $level = Attr::$xinfaTrickLevels[$xinfa_trick_number - 1];
            try {
                $name = $ref_obj->getProperty('lv' . $level . '_name')->getValue($row);
                $damage = $ref_obj->getProperty('lv' . $level . '_damage')->getValue($row);
            } catch (\ReflectionException $e) {
                $name = '';
                $damage = 0;
            }
            $trick = new XinfaTrickAttrs();
            $trick->xinfaId = $xinfa_id;
            $trick->number = $xinfa_trick_number;
            $trick->level = $level;
            $trick->name = $name;
            $trick->damage = $damage;
            $trick->mp = self::$xinfaTrickMps[$xinfa_trick_number - 1];
            self::$xinfasTricks[$xinfa_id][$xinfa_trick_number] = $trick;
        }
        return self::$xinfasTricks[$xinfa_id][$xinfa_trick_number];
    }


    /**
     * 内力不足提示
     *
     * @var string
     */
    public static string $neiLi = '$M正欲转内力使出一式「$N」，忽觉内力不济，只得变换了招式！';


    /**
     * 判断是否处于战斗
     *
     * @param array $battlefield
     *
     * @return bool
     */
    public static function isFree(array &$battlefield): bool
    {
        if ($battlefield['b1_state'] or $battlefield['b2_state'] or $battlefield['b3_state']) {
            return false;
        }
        return true;
    }


    /**
     * 恢复状态
     *
     * @param RoleAttrs $role_attrs
     */
    public static function recover(RoleAttrs &$role_attrs)
    {
        $ratio = 0.5 + $role_attrs->renshen / 10;
        $role_attrs->hp = $role_attrs->hp > $role_attrs->maxHp * $ratio ? $role_attrs->hp : $role_attrs->maxHp * $ratio;
        $role_attrs->mp = $role_attrs->mp > $role_attrs->maxMp * $ratio ? $role_attrs->mp : $role_attrs->maxMp * $ratio;
    }
}
