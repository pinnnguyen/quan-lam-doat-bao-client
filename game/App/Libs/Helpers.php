<?php


namespace App\Libs;


use App\Core\Components\Cache;
use App\Core\Components\DB;
use App\Core\Components\View;
use App\Core\Configs\CacheConfig;
use App\Core\Configs\GameConfig;
use App\Libs\Attrs\NpcAttrs;
use App\Libs\Attrs\RoleAttrs;
use App\Libs\Objects\MapRow;
use App\Libs\Objects\NpcRow;
use App\Libs\Objects\RoleRow;
use App\Libs\Objects\SkillRow;
use App\Libs\Objects\ThingRow;
use App\Libs\Objects\XinfaRow;
use PDO;
use Redis;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;


/**
 * 通用 帮助类
 */
class Helpers
{
    /**
     * 生成 url
     *
     * @param Request $request
     * @param string  $action
     *
     * @return string
     */
    public static function createUrl(Request $request, string $action): string
    {
        if (empty($request->roleCmds)) {
            $cmd = '1';
            $request->roleCmds = [$cmd => $action];
        } else {
            $tmp = $request->roleCmds;
            if (is_array($tmp)) {
                $cmd = dechex(hexdec(array_key_last($tmp)) + 1);
            } else {
                $cmd = dechex(hexdec($tmp) + 1);
                $tmp = [];
            }
            $tmp[$cmd] = $action;
            $request->roleCmds = $tmp;
        }
        // return GameConfig::PATH . '?cmd=' . $cmd . '&sid=' . $request->roleRow->sid;
        return GameConfig::PATH . '?sid=' . $request->roleRow->sid . '&cmd=' . $cmd;
    }


    /**
     * 获取执行时间 单位 毫秒 保留四位小数
     *
     * @param Request $request
     *
     * @return string
     */
    public static function getExecuteMillisecondTime(Request $request): string
    {
        return sprintf('%.3fms', (microtime(true) - $request->startMicroTime) * 1000);
    }


    /**
     * 获取当前日期时间 格式 YYYY/MM/DD HH:ii:ss
     *
     * @return string
     */
    public static function getCurrentDatetime(): string
    {
        return date(format: 'Y/m/d H:i:s', timestamp: time());
    }


    /**
     * 获取 UA 信息
     *
     * @param Request $request
     *
     * @return string
     */
    public static function getUAInfo(Request $request): string
    {
        if (empty($request->userAgent)) {
            return '';
        }
        return $request->userAgent->today_click . '/' . $request->userAgent->everyday_click;
    }


    /**
     * 获取配置
     *
     * @param string $config
     * @param string $name
     *
     * @return string
     */
    public static function config(string $config = '', string $name = ''): string
    {
        return constant('\App\Core\Configs\\' . $config . 'Config::' . $name);
    }


    /**
     * 定制响应  自定义响应头 Server  开启 Gzip  压缩 Html
     *
     * @param string $body
     * @param int    $status
     * @param array  $headers
     *
     * @return Response
     */
    public static function response(string $body = '', int $status = 200, array $headers = []): Response
    {
        // return new Response($status, $headers + [
        //         'Content-Encoding' => 'gzip',
        //         'Server'           => ServerConfig::NAME . ' ' . ServerConfig::VERSION,
        //     ], gzencode(preg_replace(['/\s+/', '/\s</', '/>\s/', '/\s+<br\/>\s+/'], [' ', ' <', '> ', '<br/>'], $body)));
        return new Response($status, $headers + [
                'Content-Encoding' => 'gzip',
                'Server'           => 'GEM',
            ], gzencode(preg_replace(['/\s+/', '/\s+<br\/>\s+/'], [' ', '<br/>'], $body), 1));
    }


    /**
     * 缓存视图再响应
     *
     * @param Request $request
     * @param string  $body
     *
     * @return Response
     */
    public static function cacheResponse(Request $request, string $body): Response
    {
        cache()->set('role_view_' . $request->roleId, $body);
        return self::response($body);
    }


    /**
     * 生成随机 sid
     *
     * @return string
     */
    public static function sid(): string
    {
        $chars = 'qwertyuioplkjhgfdsazxcvbnmQWERTYUIOPLKJHGFDSAZXCVBNM0987654321';
        $sid = $chars[mt_rand(0, 51)];
        for ($i = 0; $i < 15; $i++) {
            $sid .= $chars[mt_rand(0, 61)];
        }
        return $sid;
    }


    public const MINI_UNITS = ['', '十', '百', '千',];
    public const UNITS = ['', '万', '亿', '兆', '京', '垓', '秭', '穰',];
    public const NUMS = ['', '一', '二', '三', '四', '五', '六', '七', '八', '九',];


    /**
     * 获取数字的汉字读法
     *
     * @param int $number
     *
     * @return string
     */
    public static function getHansNumber(int $number): string
    {
        if ($number <= 0) return '零';
        $hans_number = '';
        $s = strrev((string)$number);
        $l = strlen($s) - 1;
        for ($i = $l; $i >= 0;) {
            $hans_number .= ($s[$i] !== '1' || $i < $l || $i % 4 !== 1 ? self::NUMS[(int)$s[$i]] : '') . ($i % 4 === 0 ? self::UNITS[$i / 4] : self::MINI_UNITS[$i % 4]);
            if ($i % 4 === 0 && $i > 0 && $s[$i - 1] === '0') {
                while ($i-- > 0 && $s[$i] === '0') ;
                if ($i >= 0) $hans_number .= '零';
            } elseif (--$i % 4 >= 0 && $s[$i] === '0') {
                while ($i % 4 !== 0 && $s[--$i] === '0') ;
                if ($i % 4 !== 0 || $s[$i] !== '0') $hans_number .= '零';
            }
        }
        return $hans_number;
    }
    // {
    //     $units = ['', '万', '亿', '万亿', '亿亿'];
    //     $mini_units = ['', '十', '百', '千'];
    //     $chars = ['零', '一', '二', '三', '四', '五', '六', '七', '八', '九'];
    //     $hans_number = $chars[0];
    //     if ($number > 0 && $number < PHP_INT_MAX) {
    //         $numbers = array_reverse(str_split($number));
    //         foreach ($numbers as $key => $number) {
    //             if ($key % 4 === 0) {
    //                 if ($number !== '0' || (isset($numbers[$key + 1]) && $numbers[$key + 1] !== '0') ||
    //                     (isset($numbers[$key + 2]) && $numbers[$key + 2] !== '0') ||
    //                     (isset($numbers[$key + 3]) && $numbers[$key + 3] !== '0')) {
    //                     $hans_number = $units[$key / 4] . $hans_number;
    //                 }
    //             }
    //             $hans_number = $chars[(int)$number] . $mini_units[$key % 4] . $hans_number;
    //         }
    //         $hans_number = preg_replace([
    //             '/零[百十千]/u', '/零+万/u', '/零+亿/u', '/零+万亿/u', '/零+亿亿/u',
    //             '/零+/u', '/零$/u',
    //         ], ['零', '万零', '亿零', '万亿零', '亿亿零', '零', '',], $hans_number);
    //     }
    //     return $hans_number;
    // }


    /**
     * 获取金钱的汉字读法
     *
     * @param int $money
     *
     * @return string
     */
    public static function getHansMoney(int $money): string
    {
        if ($money <= 0) return '零文铜钱';
        $hans_money = '';
        $jin = (int)($money / 10000);
        if ($jin > 0) {
            $hans_money .= self::getHansNumber($jin) . '两黄金';
        }
        $yin = (int)($money % 10000 / 100);
        if ($yin > 0) {
            $hans_money .= match (true) {
                    $yin > 19 => self::NUMS[(int)($yin / 10)] . '十' . self::NUMS[$yin % 10],
                    $yin > 9  => '十' . self::NUMS[$yin % 10],
                    default   => self::NUMS[$yin % 10],
                } . '两白银';
        }
        $tong = $money % 100;
        if ($tong > 0) {
            $hans_money .= match (true) {
                    $tong > 19 => self::NUMS[(int)($tong / 10)] . '十' . self::NUMS[$tong % 10],
                    $tong > 9  => '十' . self::NUMS[$tong % 10],
                    default    => self::NUMS[$tong % 10],
                } . '文铜钱';
        }
        return $hans_money;
        // if ($money < 0) {
        //     $money = 0;
        // }
        // $hans_money = '';
        // $jin = (int)($money / 10000);
        // if ($jin > 0) {
        //     $hans_money .= self::getHansNumber($jin) . '两黄金';
        // }
        // $yin = (int)($money % 10000 / 100);
        // if ($yin > 0) {
        //     $hans_money .= self::getHansNumber($yin) . '两白银';
        // }
        // $tong = $money % 100;
        // if ($tong > 0 || $hans_money === '') {
        //     $hans_money .= self::getHansNumber($tong) . '文铜钱';
        // }
        // return $hans_money;
    }


    /**
     * 获取技能所需
     *
     * @param  $skill
     *
     * @return int
     */
    public static function getSkillExp($skill): int
    {
        if ($skill->is_base) {
            return $skill->lv * $skill->lv;
        }
        return intval(1.2 * $skill->lv * $skill->lv);
    }


    /**
     * 获取百分比
     *
     * @param int $up
     * @param int $down
     *
     * @return int
     */
    public static function getPercent(int $up, int $down): int
    {
        return ceil(($up / $down) * 100);
    }


    /**
     * 数据库
     *
     * @return PDO
     */
    public static function db(): PDO
    {
        return DB::getInstance();
    }


    /**
     * 生成视图
     *
     * @param       $name
     * @param array $context
     *
     * @return string
     */
    public static function view($name, array $context = []): string
    {
        try {
            return View::getInstance()->render($name, $context);
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            return 'Error Code: ' . $e->getCode() . ', Error Message: ' . $e->getMessage();
        }
    }


    /**
     * 缓存
     *
     * @return Redis
     */
    public static function cache(): Redis
    {
        return Cache::getInstance();
    }


    /**
     * 缓存实例 / Redis 实例
     *
     * @var Redis|null
     */
    protected static ?Redis $lock = null;


    /**
     * 获取缓存实例 单例
     *
     * @return Redis
     */
    public static function lock(): Redis
    {
        if (static::$lock === null) {
            static::$lock = new Redis();
            //static::$lock->connect('/tmp/redis.sock');
            static::$lock->connect(CacheConfig::HOST);
            static::$lock->select(CacheConfig::DATABASE_NUM);
        }
        return static::$lock;
    }


    /**
     * 门派
     *
     * @var array|null
     */
    public static ?array $sects = null;


    /**
     * 获取门派
     *
     * @param int $sect_id
     *
     * @return string
     */
    public static function getSect(int $sect_id): string
    {
        if (!isset(self::$sects)) {
            self::$sects = self::cache()->get('sects');
        }
        return self::$sects[$sect_id];
    }


    /**
     * NPC 阶层
     *
     * @var array|null
     */
    public static ?array $npcRanks = null;


    /**
     * 获取 NPC 阶层
     *
     * @param int $npc_rank_id
     *
     * @return string
     */
    public static function getNpcRank(int $npc_rank_id): string
    {
        if (!isset(self::$npcRanks)) {
            self::$npcRanks = self::cache()->get('npc_ranks');
        }
        return self::$npcRanks[$npc_rank_id];
    }


    /**
     * NPC 阶层掉落
     *
     * @var array|null
     */
    public static ?array $npcRankThings = null;


    /**
     * 获取 NPC 阶层掉落
     *
     * @param int $npc_rank_id
     *
     * @return array
     */
    public static function getNpcRankThing(int $npc_rank_id): array
    {
        if (!isset(self::$npcRankThings)) {
            self::$npcRankThings = self::cache()->get('npc_rank_things');
        }
        return self::$npcRankThings[$npc_rank_id];
    }


    /**
     * 送信目标
     *
     * @var array|null
     */
    public static ?array $deliverLetterTargets = null;


    /**
     * 获取送信目标
     *
     * @return array
     */
    public static function getDeliverLetterTarget(): array
    {
        if (!isset(self::$deliverLetterTargets)) {
            self::$deliverLetterTargets = self::cache()->get('deliver_letter_targets');
        }
        return self::$deliverLetterTargets[array_rand(self::$deliverLetterTargets)];
    }


    /**
     * 装备种类名称
     *
     * @var array|null
     */
    public static ?array $equipmentKindNames = null;


    /**
     * 获取装备种类
     *
     * @param int $equipment_kind_id
     *
     * @return string
     */
    public static function getEquipmentKindNameByEquipmentKindId(int $equipment_kind_id): string
    {
        if (!isset(self::$equipmentKindNames)) {
            self::$equipmentKindNames = self::cache()->get('equipment_kinds');
        }
        return self::$equipmentKindNames[$equipment_kind_id];
    }


    /**
     * 装备种类 ids
     *
     * @var array|null
     */
    public static ?array $equipmentKindIds = null;


    /**
     * 获取装备种类 id
     *
     * @param int $equipment_kind_name
     *
     * @return int
     */
    public static function getEquipmentKindIdByEquipmentKindName(int $equipment_kind_name): int
    {
        if (!isset(self::$equipmentKindIds)) {
            if (!isset(self::$equipmentKindNames)) {
                self::$equipmentKindNames = self::cache()->get('equipment_kinds');
            }
            self::$equipmentKindIds = array_flip(self::$equipmentKindNames);
        }
        return self::$equipmentKindIds[$equipment_kind_name];
    }


    /**
     * 生命心法增益
     *
     * @var array|null
     */
    public static ?array $xinfaHpTricks = null;


    /**
     * 获取生命心法增益
     *
     * @param int $xinfa_id
     * @param int $xinfa_lv
     *
     * @return int
     */
    public static function getXinfaHpBuff(int $xinfa_id, int $xinfa_lv): int
    {
        if (!isset(self::$xinfaHpTricks)) {
            self::$xinfaHpTricks = self::cache()->get('xinfa_hp_tricks');
        }
        $experiences = [0 => 1, 8 => 2, 64 => 3, 216 => 4, 512 => 5];
        $hp = $experiences[self::$xinfaHpTricks[$xinfa_id]['experience']] * $xinfa_lv;
        $ranks = [0, 40, 80, 160, 240, 400, 560, 720, 880, 1000];
        foreach ($ranks as $rank) {
            if ($xinfa_lv >= $rank) {
                $hp += self::$xinfaHpTricks[$xinfa_id]['lv' . $rank . '_hp'];
            }
        }
        return $hp;
    }


    /**
     * 内功心法增益
     *
     * @var array|null
     */
    public static ?array $xinfaMpTricks = null;


    /**
     * 获取内功心法增益
     *
     * @param int $xinfa_id
     * @param int $xinfa_lv
     *
     * @return array
     */
    public static function getXinfaMpBuff(int $xinfa_id, int $xinfa_lv): array
    {
        if (!isset(self::$xinfaMpTricks)) {
            self::$xinfaMpTricks = self::cache()->get('xinfa_mp_tricks');
        }
        $experiences = [0 => 1, 64 => 2, 128 => 3, 216 => 4, 512 => 5];
        $buff = ['hp' => 0, 'mp' => 0];
        $buff['hp'] = $experiences[self::$xinfaMpTricks[$xinfa_id]['experience']] * $xinfa_lv;
        $buff['mp'] = $experiences[self::$xinfaMpTricks[$xinfa_id]['experience']] * $xinfa_lv;
        $ranks = [0, 40, 80, 160, 240, 400, 560, 720, 880, 1000];
        foreach ($ranks as $rank) {
            if ($xinfa_lv >= $rank) {
                $buff['mp'] += self::$xinfaMpTricks[$xinfa_id]['lv' . $rank . '_mp'];
                $buff['hp'] += self::$xinfaMpTricks[$xinfa_id]['lv' . $rank . '_mp'];
            }
        }
        return $buff;
    }


    /**
     * 所有地图
     *
     * @var array|null
     */
    public static ?array $maps = null;


    /**
     * 获取地图
     *
     * @param int $map_id
     *
     * @return MapRow
     */
    public static function getMapRowByMapId(int $map_id): MapRow
    {
        if (!isset(self::$maps)) {
            if (self::cache()->get('maps') != false){
                self::$maps = self::cache()->get('maps');
            }else{
                return new MapRow();
            }

        }
        return clone self::$maps[$map_id];
    }


    /**
     * 所有 NPC
     *
     * @var array|null
     */
    public static ?array $npcs = null;


    /**
     * 获取 NPC 原生数据
     *
     * @param int $npc_id
     *
     * @return NpcRow
     */
    public static function getNpcRowByNpcId(int $npc_id): NpcRow
    {
        if (!isset(self::$npcs)) {
            self::$npcs = self::cache()->get('npcs');
        }
        return clone self::$npcs[$npc_id];
    }


    /**
     * 所有物品
     *
     * @var array|null
     */
    public static ?array $things = null;


    /**
     * 获取物品
     *
     * @param int $thing_id
     *
     * @return ThingRow
     */
    public static function getThingRowByThingId(int $thing_id): ThingRow
    {
        if (!isset(self::$things)) {
            if (self::cache()->get('things') != false){
                self::$things = self::cache()->get('things');
            }else{
                return new ThingRow();
            }
        }
        return clone self::$things[$thing_id];
    }


    /**
     * 所有技能
     *
     * @var array|null
     */
    public static ?array $skills = null;


    /**
     * 获取技能
     *
     * @param int $skill_id
     *
     * @return SkillRow
     */
    public static function getSkillRowBySkillId(int $skill_id): SkillRow
    {
        if (!isset(self::$skills)) {
            if (self::cache()->get('skills') != false){
                self::$skills = self::cache()->get('skills');
            }else{
                return new SkillRow();
            }

        }
        return clone self::$skills[$skill_id];
    }


    /**
     * 所有心法
     *
     * @var array|null
     */
    public static ?array $xinfas = null;


    /**
     * 获取心法
     *
     * @param int $xinfa_id
     *
     * @return XinfaRow
     */
    public static function getXinfaRowByXinfaId(int $xinfa_id): XinfaRow
    {
        if (!isset(self::$xinfas)) {
            $xinfa = self::cache()->get('xinfas');
            if ($xinfa != false){
                self::$xinfas = self::cache()->get('xinfas');
            }else{
                return new XinfaRow();
            }

        }
        return clone self::$xinfas[$xinfa_id];
    }


    /**
     * 所有地区
     *
     * @var array|null
     */
    public static ?array $regions = null;


    /**
     * 获取地区
     *
     * @param int $region_id
     *
     * @return string
     */
    public static function getRegion(int $region_id): string
    {
        if (!isset(self::$regions)) {
            self::$regions = self::cache()->get('regions');
        }
        return self::$regions[$region_id];
    }


    /**
     * 所有攻击心法招式
     *
     * @var array|null
     */
    public static ?array $xinfaAttackTricks = null;


    /**
     * 获取攻击心法招式
     *
     * @param int $xinfa_id
     *
     * @return object
     */
    public static function getXinfaAttackTrick(int $xinfa_id): object
    {
        if (!isset(self::$xinfaAttackTricks)) {
            self::$xinfaAttackTricks = self::cache()->get('xinfa_attack_tricks');
        }
        return clone self::$xinfaAttackTricks[$xinfa_id];
    }


    /**
     * 所有地图 NPC 属性
     *
     * @var array|null
     */
    public static ?array $mapNpcsAttrs = null;


    /**
     * 获取地图 NPC 属性
     *
     * @param string $map_npc_id
     *
     * @return NpcAttrs
     */
    public static function getMapNpcAttrs(string $map_npc_id): NpcAttrs
    {
        if (!isset(self::$mapNpcsAttrs)) {
            self::$mapNpcsAttrs = self::cache()->get('map_npcs_attrs');
        }
        return clone self::$mapNpcsAttrs[$map_npc_id];
    }


    /**
     * 商店
     *
     * @var array|null
     */
    public static ?array $shops = null;


    /**
     * 获取商店
     *
     * @param int $shop_id
     *
     * @return array
     */
    public static function getShop(int $shop_id): array
    {
        if (!isset(self::$shops)) {
            self::$shops = self::cache()->get('shops');
        }
        return self::$shops[$shop_id];
    }


    /**
     * 配置
     *
     * @var array|null
     */
    public static ?array $settings = null;


    /**
     * 获取配置
     *
     * @param string $setting_item
     *
     * @return mixed
     */
    public static function getSetting(string $setting_item): mixed
    {
        if (!isset(self::$settings)) {
            self::$settings = self::cache()->get('settings');
        }
        return self::$settings[$setting_item];
    }


    /**
     * 潜能倍率
     *
     * @var float|null
     */
    public static ?float $qiannengRatio = null;


    /**
     * 获取潜能倍率
     *
     * @return float
     */
    public static function getQiannengRatio(): float
    {
        if (!isset(self::$qiannengRatio)) {
            self::$qiannengRatio = self::getSetting('qianneng_ratio') / 1000000;
        }
        return self::$qiannengRatio;
    }


    /**
     * 修为倍率
     *
     * @var float|null
     */
    public static ?float $experienceRatio = null;


    /**
     * 获取修为倍率
     *
     * @return float
     */
    public static function getExperienceRatio(): float
    {
        if (!isset(self::$experienceRatio)) {
            self::$experienceRatio = self::getSetting('experience_ratio') / 1000000;
        }
        return self::$experienceRatio;
    }


    /**
     * 心法经验倍率
     *
     * @var float|null
     */
    public static ?float $xinfaExperienceRatio = null;


    /**
     * 获取心法经验倍率
     *
     * @return float
     */
    public static function getXinfaExperienceRatio(): float
    {
        if (!isset(self::$xinfaExperienceRatio)) {
            self::$xinfaExperienceRatio = self::getSetting('xinfa_experience_ratio') / 1000000;
        }
        return self::$xinfaExperienceRatio;
    }


    /**
     * 所有地图NPC ID
     *
     * @var array|null
     */
    public static ?array $mapNpcs = null;


    /**
     * 获取地图 NPC ID
     *
     * @param int $map_id
     *
     * @return array
     */
    public static function getMapNpcs(int $map_id): array
    {
        if (!isset(self::$mapNpcs)) {
            $mapNpcs = self::cache()->get('map_npcs');
            if ($mapNpcs != false){
                self::$mapNpcs = self::cache()->get('map_npcs');
            }
        }
        if (isset(self::$mapNpcs[$map_id])) {
            return self::$mapNpcs[$map_id];
        }
        return [];
    }


    /**
     * 计算概率
     *
     * @param int $target 目标
     * @param int $total  总量
     *
     * @return bool
     */
    public static function getProbability(int $target, int $total): bool
    {
        if (mt_rand(1, $total) <= $target) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 获取中文修为
     *
     * @param int $experience
     *
     * @return string
     */
    public static function getHansExperience(int $experience): string
    {
        if ($experience <= 0) return '零时辰';
        $hans_experience = '';
        $year = (int)($experience / 1000);
        if ($year > 0) {
            $hans_experience .= self::getHansNumber($year) . '年';
        }
        $day = (int)($experience % 1000 / 4);
        if ($day > 0) {
            if ($day >= 100) {
                $hans_experience .= self:: NUMS[(int)($day / 100)] . '百';
                $day %= 100;
                $hans_experience .= match (true) {
                        $day > 9 => self::NUMS[(int)($day / 10)] . '十' . self:: NUMS[$day % 10],
                        $day > 0 => '零' . self::NUMS[$day % 10],
                        default  => '',
                    } . '天';
            } else {
                $hans_experience .= match (true) {
                        $day > 19 => self::NUMS[(int)($day / 10)] . '十' . self::NUMS[$day % 10],
                        $day > 9  => '十' . self:: NUMS[$day % 10],
                        default   => self::NUMS[$day % 10],
                    } . '天';
            }
        }
        $hour = $experience % 4 * 3;
        if ($hour > 0) {
            $hans_experience .= match (true) {
                    $hour < 10 => self:: NUMS[$hour % 10],
                    default    => '十' . self::NUMS[$hour % 10],
                } . '时辰';
        }
        return $hans_experience;
        // if ($experience < 0) {
        //     $experience = 0;
        // }
        // $hans_experience = '';
        // $year = (int)($experience / 1000);
        // if ($year > 0) {
        //     $hans_experience .= self::getHansNumber($year) . '年';
        // }
        // $day = (int)($experience % 1000 / 4);
        // if ($day > 0) {
        //     $hans_experience .= self::getHansNumber($day) . '天';
        // }
        // $hour = $experience % 4 * 3;
        // if ($hour > 0 || $hans_experience === '') {
        //     $hans_experience .= self::getHansNumber($hour) . '时辰';
        // }
        // return $hans_experience;
    }


    /**
     * 获取武功描述
     *
     * @param int $wugong
     *
     * @return string
     */
    public static function getWugongDescription(int $wugong): string
    {
        return match (true) {
            $wugong <= 4    => '不堪一击',
            $wugong <= 9    => '毫不足虑',
            $wugong <= 19   => '不足挂齿',
            $wugong <= 29   => '普普通通',
            $wugong <= 39   => '马马虎虎',
            $wugong <= 49   => '初窥门径',
            $wugong <= 59   => '平淡无奇',
            $wugong <= 69   => '半生不熟',
            $wugong <= 79   => '略有小成',
            $wugong <= 89   => '已有小成',
            $wugong <= 129  => '驾轻就熟',
            $wugong <= 159  => '了然于胸',
            $wugong <= 189  => '炉火纯青',
            $wugong <= 219  => '略有大成',
            $wugong <= 249  => '神乎奇技',
            $wugong <= 279  => '已有大成',
            $wugong <= 309  => '一代宗师',
            $wugong <= 339  => '震古烁今',
            $wugong <= 369  => '惊世骇俗',
            $wugong <= 399  => '傲视群雄',
            $wugong <= 474  => '出神入化',
            $wugong <= 549  => '笑傲江湖',
            $wugong <= 624  => '举世无双',
            $wugong <= 699  => '空前绝后',
            $wugong <= 849  => '飞花摘叶',
            $wugong <= 999  => '神功内蕴',
            $wugong <= 1149 => '深藏不露',
            $wugong <= 1349 => '深不可测',
            $wugong <= 1499 => '返璞归真',
            default         => '天人合一',
        };
    }


    /**
     * 获取攻击力描述
     *
     * @param int $attack
     *
     * @return string
     */
    public static function getAttackDescription(int $attack): string
    {
        return match (true) {
            $attack <= 100 => '出手似乎极轻',
            $attack <= 200 => '出手似乎很轻',
            $attack <= 300 => '出手似乎很重',
            $attack <= 500 => '出手似乎极重',
            $attack <= 700 => '出手似乎匪夷所思',
            $attack <= 900 => '出手似乎神鬼莫测',
            default        => '出手似乎破碎虚空',
        };
    }


    /**
     * 获取生命状态描述
     *
     * @param int $hp
     * @param int $maxHp
     *
     * @return string
     */
    public static function getStatusDescription(int $hp, int $maxHp): string
    {
        $percent = self::getPercent($hp, $maxHp);
        //Helpers::log_message("statusDescription:".$percent);
        return match (true) {
            $percent >= 90 => '看起来气血充盈，并没有受伤',
            $percent >= 80 => '看起来有点疲倦了，但是仍然十分有活力',
            $percent >= 70 => '看起来手脚似乎不太灵光，但是仍然有条不紊',
            $percent >= 60 => '看起来受了点轻伤',
            $percent >= 50 => '受伤不轻，看起来状况并不太好',
            $percent >= 40 => '看起来已经力不从心了',
            $percent >= 30 => '已经伤痕累累，正在勉力支撑着不倒下去',
            $percent >= 20 => '看起来深受重伤，只怕会有生命危险',
            $percent >= 10 => '看起来命在旦夕，似乎一阵风就能要了命',
            default        => '已经有如风中残烛，随时都可能断气',
        };
    }


    /**
     * 根据等级获取技能总经验
     *
     * @param int $skill_lv
     *
     * @return int
     */
    public static function getSkillTotalExperience(int $skill_lv): int
    {
        if ($skill_lv > 0) {
            return $skill_lv * $skill_lv + self::getSkillTotalExperience($skill_lv - 1);
        } else {
            return 0;
        }
    }


    /**
     * 根据总经验获取技能等级
     *
     * @param int $skill_experience
     * @param int $skill_lv
     *
     * @return array
     */
    public static function getSkillTotalLv(int $skill_experience, int $skill_lv = 0)
    {
        if ($skill_experience >= intval(ceil($skill_lv * $skill_lv * 1.2))) {
            return self::getSkillTotalLv($skill_experience - intval(ceil($skill_lv * $skill_lv * 1.2)), $skill_lv + 1);
        } else {
            return ['lv' => $skill_lv - 1, 'exp' => $skill_experience];
        }
    }


    /**
     * 句子
     *
     * @var array|string[]
     */
    public static array $sentences = [
        '我家门前有两棵树，一棵是枣树，另一棵也是枣树。',
        '其实地上本没有路，走的人多了，便成了路。',
        '时间就是生命，无端的空耗别人的时间，其实无异于谋财害命的。',
        '莫非他造塔的时候，竟没有想到塔是终究要倒的么？',
        '哀其不幸，怒其不争。',
        '从来如此，便对么？',
        '悲剧将人生的有价值的东西毁灭给人看，喜剧将那无价值的撕破给人看。',
    ];


    /**
     * 随机句子
     *
     * @return string
     */
    public static function randomSentence(): string
    {
        return self::$sentences[array_rand(self::$sentences)];
    }


    /**
     * 获取验证码
     *
     * @return array
     */
    public static function getCaptcha(): array
    {
        $numbers = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            ['零', '一', '二', '三', '四', '五', '六', '七', '八', '九', '十'],
            ['零', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖', '拾'],
        ];
        $operators = [
            ['＋', '－', '×', '÷', '＝'],
            ['加上', '减去', '乘以', '除以', '等于'],
        ];
        $divs = [
            [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            [1],
            [1, 2],
            [1, 3],
            [1, 2, 4],
            [1, 5],
            [1, 3, 6],
            [1, 7],
            [1, 2, 4, 8],
            [1, 3, 9],
            [1, 5, 10],
        ];
        $m = mt_rand(0, 10);
        $operator = mt_rand(0, 3);
        if ($operator === 1) {
            $n = mt_rand(0, $m);
            $result = $m - $n;
        } elseif ($operator === 3) {
            $n = $divs[$m][array_rand($divs[$m])];
            $result = $m / $n;
        } else {
            $n = mt_rand(0, 10);
            if ($operator === 0) {
                $result = $m + $n;
            } else {
                $result = $m * $n;
            }
        }
        $str_m = $numbers[mt_rand(0, 2)][$m];
        $str_o = $operators[mt_rand(0, 1)][$operator];
        $str_n = $numbers[mt_rand(0, 2)][$n];
        $str_e = $operators[mt_rand(0, 1)][4];

        $image = imagecreatetruecolor(160, 40);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        $font = __DIR__ . '/Fonts/fzyhjt.ttf';
        $size_m = imagettfbbox(20, 0, $font, $str_m);
        $size_o = imagettfbbox(20, 0, $font, $str_o);
        $size_n = imagettfbbox(20, 0, $font, $str_n);
        imagettftext($image, 20, mt_rand(-30, 30), 2, 30, imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)), $font, $str_m);
        imagettftext($image, 20, mt_rand(-30, 30), 4 + $size_m[2] - $size_m[0], 30, imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)), $font, $str_o);
        imagettftext($image, 20, mt_rand(-30, 30), 6 + $size_m[2] - $size_m[0] + $size_o[2] - $size_o[0], 30, imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)), $font, $str_n);
        imagettftext($image, 20, mt_rand(-30, 30), 8 + $size_m[2] - $size_m[0] + $size_o[2] - $size_o[0] + $size_n[2] - $size_n[0], 30, imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)), $font, $str_e);
        for ($i = 0; $i < 35; $i++) {
            imagesetpixel($image, mt_rand(0, 159), mt_rand(0, 39), imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
        }
        for ($i = 0; $i < 7; $i++) {
            imageline($image, mt_rand(0, 159), mt_rand(0, 39), mt_rand(0, 159), mt_rand(0, 39), imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
        }
        ob_start();
        imagejpeg($image);
        $ob_image = ob_get_clean();
        $captcha_base64 = base64_encode($ob_image);
        imagedestroy($image);
        return [
            'captcha_base64' => $captcha_base64,
            'result'         => $result,
        ];
    }


    /**
     * 获得连续任务宝石数量
     *
     * @param int $times
     *
     * @return int
     */
    public static function getConsecutiveMissionGem(int $times): int
    {
        if ($times < 50) {
            return 1;
        } elseif ($times < 100) {
            return 2;
        } elseif ($times < 150) {
            return 3;
        } elseif ($times < 200) {
            return 4;
        } elseif ($times < 250) {
            return 5;
        } elseif ($times < 300) {
            return 6;
        } else {
            return 7;
        }
    }


    /**
     * 获得连续任务随机装备
     *
     * @param int $times
     *
     * @return mixed
     */
    public static function getConsecutiveMissionEquipment(int $times): mixed
    {
        if (!isset(self::$consecutiveMissionEquipments)) {
            self::$consecutiveMissionEquipments = self::cache()->get('consecutive_mission_equipments');
        }
        if ($times < 100) {
            $e = 0;
        } elseif ($times < 200) {
            $e = 0;
        } elseif ($times < 300) {
            $e = 1;
        } elseif ($times < 400) {
            $e = 1;
        } elseif ($times < 500) {
            $e = 2;
        } elseif ($times < 600) {
            $e = 2;
        } elseif ($times < 700) {
            $e = 3;
        } elseif ($times < 800) {
            $e = 3;
        } elseif ($times < 900) {
            $e = 4;
        } elseif ($times < 1000) {
            $e = 4;
        } elseif ($times < 1500) {
            $e = 5;
        } elseif ($times < 2000) {
            $e = 6;
        } else {
            $e = 6;
        }
        return self::$consecutiveMissionEquipments[$e][array_rand(self::$consecutiveMissionEquipments[$e])]['thing_id'];
    }


    /**
     * 连续任务奖励装备
     *
     * @param int $times
     *
     * @return mixed
     */
    public static function getConsecutiveMissionRewardEquipment(int $times): mixed
    {
        if (!isset(self::$consecutiveMissionEquipments)) {
            self::$consecutiveMissionEquipments = self::cache()->get('consecutive_mission_equipments');
        }
        if ($times < 200) {
            $e = 0;
        } elseif ($times < 400) {
            $e = 1;
        } elseif ($times < 600) {
            $e = 2;
        } elseif ($times < 800) {
            $e = 3;
        } elseif ($times < 1000) {
            $e = 4;
        } elseif ($times < 1500) {
            $e = 5;
        } elseif ($times < 2000) {
            $e = 6;
        } else {
            $e = 7;
        }
        return self::$consecutiveMissionEquipments[$e][array_rand(self::$consecutiveMissionEquipments[$e])]['thing_id'];
    }


    /**
     * 连续任务奖励不掉装备
     *
     * @return mixed
     */
    public static function getConsecutiveMissionRewardNoDropEquipment(): mixed
    {
        if (!isset(self::$consecutiveMissionEquipments)) {
            self::$consecutiveMissionEquipments = self::cache()->get('consecutive_mission_equipments');
        }
        return self::$consecutiveMissionEquipments[8][array_rand(self::$consecutiveMissionEquipments[8])]['thing_id'];
    }


    /**
     * 获得连续任务随机地图
     *
     * @return mixed
     */
    public static function getConsecutiveMissionMap(): mixed
    {
        if (!isset(self::$consecutiveMissionMaps)) {
            self::$consecutiveMissionMaps = self::cache()->get('consecutive_mission_maps');
        }
        return self::$consecutiveMissionMaps[array_rand(self::$consecutiveMissionMaps)];
    }


    /**
     * 连续任务心法
     *
     * @var array|null
     */
    public static ?array $consecutiveMissionXinfas = null;


    /**
     * 获得连续任务随机心法
     *
     * @param int $experience
     *
     * @return mixed
     */
    public static function getConsecutiveMissionXinfa(int $experience): mixed
    {
        if (!isset(self::$consecutiveMissionXinfas)) {
            self::$consecutiveMissionXinfas = self::cache()->get('consecutive_mission_xinfas');
        }
        return self::$consecutiveMissionXinfas[$experience][array_rand(self::$consecutiveMissionXinfas[$experience])];
    }


    /**
     * 获得连续任务随机 NPC
     *
     * @return mixed
     */
    public static function getConsecutiveMissionNpc(): mixed
    {
        if (!isset(self::$consecutiveMissionNpcs)) {
            self::$consecutiveMissionNpcs = self::cache()->get('consecutive_mission_npcs');
        }
        return self::$consecutiveMissionNpcs[array_rand(self::$consecutiveMissionNpcs)];
    }


    /**
     * 获取击杀NPC连续任务
     *
     * @param int $times
     * @param int $max_lv
     *
     * @return array
     */
    public static function getConsecutiveMissionKillNpc(int $times, int $max_lv): array
    {
        $probability = mt_rand(1, 100);
        if ($times < 200) {
            if ($probability <= 5) {
                $min = $max_lv - 150;
                $max = $max_lv - 20;
            } elseif ($probability <= 89) {
                $min = $max_lv - 20;
                $max = $max_lv + 30;
            } elseif ($probability <= 94) {
                $min = $max_lv + 30;
                $max = $max_lv + 60;
            } elseif ($probability <= 97) {
                $min = $max_lv + 60;
                $max = $max_lv + 90;
            } elseif ($probability <= 99) {
                $min = $max_lv + 90;
                $max = $max_lv + 120;
            } else {
                $min = $max_lv + 120;
                $max = 1000;
            }
        } elseif ($times < 300) {
            if ($probability <= 3) {
                $min = $max_lv - 150;
                $max = $max_lv - 20;
            } elseif ($probability <= 83) {
                $min = $max_lv - 20;
                $max = $max_lv + 30;
            } elseif ($probability <= 91) {
                $min = $max_lv + 30;
                $max = $max_lv + 60;
            } elseif ($probability <= 96) {
                $min = $max_lv + 60;
                $max = $max_lv + 90;
            } elseif ($probability <= 98) {
                $min = $max_lv + 90;
                $max = $max_lv + 120;
            } else {
                $min = $max_lv + 120;
                $max = 1000;
            }
        } elseif ($times < 400) {
            if ($probability <= 3) {
                $min = $max_lv - 150;
                $max = $max_lv - 20;
            } elseif ($probability <= 78) {
                $min = $max_lv - 20;
                $max = $max_lv + 30;
            } elseif ($probability <= 88) {
                $min = $max_lv + 30;
                $max = $max_lv + 60;
            } elseif ($probability <= 94) {
                $min = $max_lv + 60;
                $max = $max_lv + 90;
            } elseif ($probability <= 97) {
                $min = $max_lv + 90;
                $max = $max_lv + 120;
            } else {
                $min = $max_lv + 120;
                $max = 1000;
            }
        } elseif ($times < 500) {
            if ($probability <= 3) {
                $min = $max_lv - 150;
                $max = $max_lv - 20;
            } elseif ($probability <= 73) {
                $min = $max_lv - 20;
                $max = $max_lv + 30;
            } elseif ($probability <= 85) {
                $min = $max_lv + 30;
                $max = $max_lv + 60;
            } elseif ($probability <= 93) {
                $min = $max_lv + 60;
                $max = $max_lv + 90;
            } elseif ($probability <= 97) {
                $min = $max_lv + 90;
                $max = $max_lv + 120;
            } else {
                $min = $max_lv + 120;
                $max = 1000;
            }
        } elseif ($times < 600) {
            if ($probability <= 2) {
                $min = $max_lv - 150;
                $max = $max_lv - 20;
            } elseif ($probability <= 67) {
                $min = $max_lv - 20;
                $max = $max_lv + 30;
            } elseif ($probability <= 82) {
                $min = $max_lv + 30;
                $max = $max_lv + 60;
            } elseif ($probability <= 92) {
                $min = $max_lv + 60;
                $max = $max_lv + 90;
            } elseif ($probability <= 97) {
                $min = $max_lv + 90;
                $max = $max_lv + 120;
            } else {
                $min = $max_lv + 120;
                $max = 1000;
            }
        } elseif ($times < 700) {
            if ($probability <= 2) {
                $min = $max_lv - 150;
                $max = $max_lv - 20;
            } elseif ($probability <= 62) {
                $min = $max_lv - 20;
                $max = $max_lv + 30;
            } elseif ($probability <= 80) {
                $min = $max_lv + 30;
                $max = $max_lv + 60;
            } elseif ($probability <= 92) {
                $min = $max_lv + 60;
                $max = $max_lv + 90;
            } elseif ($probability <= 97) {
                $min = $max_lv + 90;
                $max = $max_lv + 120;
            } else {
                $min = $max_lv + 120;
                $max = 1000;
            }
        } elseif ($times < 800) {
            if ($probability <= 2) {
                $min = $max_lv - 150;
                $max = $max_lv - 20;
            } elseif ($probability <= 57) {
                $min = $max_lv - 20;
                $max = $max_lv + 30;
            } elseif ($probability <= 77) {
                $min = $max_lv + 30;
                $max = $max_lv + 60;
            } elseif ($probability <= 92) {
                $min = $max_lv + 60;
                $max = $max_lv + 90;
            } elseif ($probability <= 97) {
                $min = $max_lv + 90;
                $max = $max_lv + 120;
            } else {
                $min = $max_lv + 120;
                $max = 1000;
            }
        } elseif ($times < 900) {
            if ($probability <= 2) {
                $min = $max_lv - 150;
                $max = $max_lv - 20;
            } elseif ($probability <= 52) {
                $min = $max_lv - 20;
                $max = $max_lv + 30;
            } elseif ($probability <= 74) {
                $min = $max_lv + 30;
                $max = $max_lv + 60;
            } elseif ($probability <= 90) {
                $min = $max_lv + 60;
                $max = $max_lv + 90;
            } elseif ($probability <= 96) {
                $min = $max_lv + 90;
                $max = $max_lv + 120;
            } else {
                $min = $max_lv + 120;
                $max = 1000;
            }
        } elseif ($times < 1000) {
            if ($probability <= 1) {
                $min = $max_lv - 150;
                $max = $max_lv - 20;
            } elseif ($probability <= 46) {
                $min = $max_lv - 20;
                $max = $max_lv + 30;
            } elseif ($probability <= 70) {
                $min = $max_lv + 30;
                $max = $max_lv + 60;
            } elseif ($probability <= 88) {
                $min = $max_lv + 60;
                $max = $max_lv + 90;
            } elseif ($probability <= 95) {
                $min = $max_lv + 90;
                $max = $max_lv + 120;
            } else {
                $min = $max_lv + 120;
                $max = 1000;
            }
        } elseif ($times < 1500) {
            if ($probability <= 1) {
                $min = $max_lv - 150;
                $max = $max_lv - 20;
            } elseif ($probability <= 41) {
                $min = $max_lv - 20;
                $max = $max_lv + 30;
            } elseif ($probability <= 66) {
                $min = $max_lv + 30;
                $max = $max_lv + 60;
            } elseif ($probability <= 86) {
                $min = $max_lv + 60;
                $max = $max_lv + 90;
            } elseif ($probability <= 94) {
                $min = $max_lv + 90;
                $max = $max_lv + 120;
            } else {
                $min = $max_lv + 120;
                $max = 1000;
            }
        } elseif ($times < 2000) {
            if ($probability <= 1) {
                $min = $max_lv - 150;
                $max = $max_lv - 20;
            } elseif ($probability <= 31) {
                $min = $max_lv - 20;
                $max = $max_lv + 30;
            } elseif ($probability <= 61) {
                $min = $max_lv + 30;
                $max = $max_lv + 60;
            } elseif ($probability <= 83) {
                $min = $max_lv + 60;
                $max = $max_lv + 90;
            } elseif ($probability <= 93) {
                $min = $max_lv + 90;
                $max = $max_lv + 120;
            } else {
                $min = $max_lv + 120;
                $max = 1000;
            }
        } else {
            if ($probability <= 1) {
                $min = $max_lv - 150;
                $max = $max_lv - 20;
            } elseif ($probability <= 26) {
                $min = $max_lv - 20;
                $max = $max_lv + 30;
            } elseif ($probability <= 56) {
                $min = $max_lv + 30;
                $max = $max_lv + 60;
            } elseif ($probability <= 81) {
                $min = $max_lv + 60;
                $max = $max_lv + 90;
            } elseif ($probability <= 92) {
                $min = $max_lv + 90;
                $max = $max_lv + 120;
            } else {
                $min = $max_lv + 120;
                $max = 1000;
            }
        }
        if ($min < 1) {
            $min = 1;
        }
        if ($min > 1000) {
            $min = 1000;
        }
        if ($max < 1) {
            $max = 1;
        }
        if ($max > 1000) {
            $max = 1000;
        }
        if (!isset(self::$consecutiveMissionKillNpcs)) {
            self::$consecutiveMissionKillNpcs = self::cache()->get('consecutive_mission_kill_npcs');
        }
        while (true) {
            $lv = mt_rand($min, $max);
            if (array_key_exists($lv, self::$consecutiveMissionKillNpcs)) {
                return self::$consecutiveMissionKillNpcs[$lv][array_rand(self::$consecutiveMissionKillNpcs[$lv])];
            }
        }
    }


    /**
     * 连续任务击杀NPC
     *
     * @var array|null
     */
    public static ?array $consecutiveMissionKillNpcs = null;

    /**
     * 连续任务NPC
     *
     * @var array|null
     */
    public static ?array $consecutiveMissionNpcs = null;

    /**
     * 连续任务地图
     *
     * @var array|null
     */
    public static ?array $consecutiveMissionMaps = null;

    /**
     * 连续任务装备
     *
     * @var array|null
     */
    public static ?array $consecutiveMissionEquipments = null;


    /**
     * 掉落宝石概率
     *
     * @param int $role_max_lv
     * @param int $npc_max_lv
     *
     * @return bool
     */
    public static function getGemProbability(int $role_max_lv, int $npc_max_lv): bool
    {
        if ($npc_max_lv - $role_max_lv < -100) {
            return self::getProbability(50, 100);
        } elseif ($npc_max_lv - $role_max_lv < -80) {
            return self::getProbability(50, 100);
        } elseif ($npc_max_lv - $role_max_lv < -60) {
            return self::getProbability(60, 100);
        } elseif ($npc_max_lv - $role_max_lv < -40) {
            return self::getProbability(70, 100);
        } elseif ($npc_max_lv - $role_max_lv < -20) {
            return self::getProbability(80, 100);
        } elseif ($npc_max_lv - $role_max_lv < 10) {
            return self::getProbability(90, 100);
        } else {
            return true;
        }
    }


    /**
     * 通过角色 sid 获取角色 id
     *
     * @param string $role_sid 角色 sid
     *
     * @return int|false|null
     */
    public static function getRoleIdByRoleSid(string $role_sid): int|false|null
    {
        return Cache::getInstance()->get('role_id_' . $role_sid);
    }


    /**
     * 通过角色 id 获取角色原生数据
     *
     * @param int $role_id 角色 ID
     *
     * @return RoleRow|false|null
     */
    public static function getRoleRowByRoleId(int $role_id): RoleRow|false|null
    {
        return Cache::getInstance()->get('role_row_' . $role_id);
    }


    /**
     * 通过角色 id 保存角色原生数据
     *
     * @param int     $role_id 角色 id
     * @param RoleRow $role_row
     *
     * @return bool
     */
    public static function setRoleRowByRoleId(int $role_id, RoleRow &$role_row): bool
    {
        return Cache::getInstance()->set('role_row_' . $role_id, $role_row);
    }


    /**
     * 通过角色 id 获取角色属性
     *
     * @param int $role_id 角色 id
     *
     * @return RoleAttrs|false|null
     */
    public static function getRoleAttrsByRoleId(int $role_id): RoleAttrs|false|null
    {
        return Cache::getInstance()->get('role_attrs_' . $role_id);
    }


    /**
     * 通过角色 id 保存角色属性
     *
     * @param int       $role_id
     * @param RoleAttrs $role_attrs
     *
     * @return bool
     */
    public static function setRoleAttrsByRoleId(int $role_id, RoleAttrs &$role_attrs): bool
    {
        return Cache::getInstance()->set('role_attrs_' . $role_id, $role_attrs);
    }


    /**
     * 获取缓存中的 NPC 属性对象
     *
     * @param string $map_npc_id
     *
     * @return NpcAttrs|false|null
     */
    public static function getMapNpcAttrsByMapNpcId(string $map_npc_id): NpcAttrs|false|null
    {
        return Cache::getInstance()->get($map_npc_id);
    }


    /**
     * 获取人物称号
     *
     * @param int $sect_id
     * @param int $experience
     *
     * @return string
     */
    public static function getTitle(int $sect_id, int $experience): string
    {
        return match ($sect_id) {
            1       => match (true) {
                $experience / 1000 < 100  => '教众',
                $experience / 1000 < 400  => '掌旗手',
                $experience / 1000 < 800  => '护教',
                $experience / 1000 < 1500 => '护法',
                $experience / 1000 < 3000 => '堂主',
                default                   => '长老',
            },
            2       => match (true) {
                $experience / 1000 < 100  => '小沙弥',
                $experience / 1000 < 500  => '比丘',
                $experience / 1000 < 1000 => '禅师',
                $experience / 1000 < 2000 => '尊者',
                $experience / 1000 < 5000 => '圣僧',
                $experience / 1000 < 8000 => '罗汉',
                default                   => '长老',
            },
            3       => match (true) {
                $experience / 1000 < 100  => '剑士',
                $experience / 1000 < 400  => '剑侠',
                $experience / 1000 < 1000 => '剑痴',
                $experience / 1000 < 2000 => '剑圣',
                default                   => '剑神',
            },
            default => '平民',
        };
    }


    /**
     * 获取心法基础经验
     *
     * @param int $experience
     *
     * @return int
     */
    public static function getXinfaBaseExperience(int $experience): int
    {
        return match ($experience) {
            0       => 2,
            8       => mt_rand(2, 3),
            64      => mt_rand(3, 4),
            128     => mt_rand(3, 5),
            216     => mt_rand(5, 6),
            default => mt_rand(6, 8)
        };
    }


    /**
     * 以预处理的方式执行一条 SQL
     *
     * @param string $sql
     */
    public static function execSql(string $sql)
    {
        try {
            $statement = self::db()->prepare($sql);
            $statement->execute();
            $statement->closeCursor();
        } catch (\Exception $exception) {
            var_dump('');
            var_dump(str_repeat('+', 50));
            var_dump($exception->getMessage());
            var_dump($sql);
            var_dump(str_repeat('+', 50));
            var_dump('');
        }
    }


    /**
     * 查询一条记录
     *
     * @param string $sql
     * @param string $class
     * @param array  $constructorArgs
     *
     * @return mixed
     */
    public static function queryFetchObject(string $sql, string $class = \stdClass::class, array $constructorArgs = []): mixed
    {
        try {
            $statement = self::db()->query($sql);
            $result = $statement->fetchObject($class, $constructorArgs);
            $statement->closeCursor();
        } catch (\Exception $exception) {
            $result = false;
            var_dump('');
            var_dump(str_repeat('+', 50));
            var_dump($exception->getMessage());
            var_dump($sql);
            var_dump(str_repeat('+', 50));
            var_dump('');
        }
        return $result;
    }


    /**
     * 查询一个结果集
     *
     * @param string $sql
     *
     * @return array|bool
     */
    public static function queryFetchAll(string $sql): array|bool
    {
        try {
            $statement = self::db()->query($sql);
            $results = $statement->fetchAll(PDO::FETCH_OBJ);
            $statement->closeCursor();
        } catch (\Exception $exception) {
            var_dump('');
            var_dump(str_repeat('+', 50));
            var_dump($exception->getMessage());
            var_dump($sql);
            var_dump(str_repeat('+', 50));
            var_dump('');
        }
        if (empty($results)) {
            $results = [];
        }
        return $results;
    }


    /**
     * 清理自身足迹
     *
     * @param $footprints
     * @param $request
     *
     * @return array
     */
    public static function clearMyselfFootprint($footprints, &$request): array
    {
        if (!is_array($footprints)) {
            return [];
        }
        $ghost_name = $request->roleRow->name . '的鬼魂';
        return array_filter(array_unique($footprints), function ($footprint) use ($ghost_name, &$request) {
            $name = mb_substr($footprint, 0, -5);
            if ($name === $request->roleRow->name or $name === $ghost_name) return false; else return true;
        });
    }

    public static function log_message($content,$type=''){
        $file = dirname(__FILE__) . '/../Runtime/Logs/' . date('YmdH') . '.log';
        $strContent = date('Y-m-d H:i:s');
        if (!empty($type)){
            $strContent .= ' ['.$type.'] ';
        }
        $strContent .= " ".$content.PHP_EOL;
        file_put_contents($file,$strContent,FILE_APPEND|LOCK_EX);
    }
}
