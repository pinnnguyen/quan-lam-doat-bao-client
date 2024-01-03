<?php

namespace App\Http\Controllers\Role;

use App\Core\Configs\GameConfig;
use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * Cá nhân tin tức
 *
 */
class InfoController
{
    /**
     * Cá nhân tin tức 首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);

        /**
         * 我的装备
         *
         */
        $equipments = [];
        if ($role_attrs->weaponThingId > 0) $equipments[] = Helpers::getThingRowByThingId($role_attrs->weaponThingId);
        if ($role_attrs->clothesThingId > 0) $equipments[] = Helpers::getThingRowByThingId($role_attrs->clothesThingId);
        if ($role_attrs->armorThingId > 0) $equipments[] = Helpers::getThingRowByThingId($role_attrs->armorThingId);
        if ($role_attrs->shoesThingId > 0) $equipments[] = Helpers::getThingRowByThingId($role_attrs->shoesThingId);

        $role_title = '【' . Helpers::getTitle($request->roleRow->sect_id, $role_attrs->experience) . '】';
        $role_title .= ($request->roleRow->sect_id > 0) ? Helpers::getSect($request->roleRow->sect_id) . 'đệ' .
            Helpers::getHansNumber($request->roleRow->seniority) . 'đệ tử' : '';

        if (!empty($request->roleRow->nickname)){
            $role_title .= '「'.$request->roleRow->nickname.'」';
        }

        $role_title .= $request->roleRow->name;
        if ($role_attrs->reviveTimestamp > time()) {
            $role_title .= '的鬼魂';
        }

        /**
         * Ta kỹ năng
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId;
SQL;

        $role_skills_st = db()->query($sql);
        $role_skills = $role_skills_st->fetchAll(\PDO::FETCH_ASSOC);
        $role_skills_st->closeCursor();
        $role_skills = array_column($role_skills, null, 'id');

        $skill_status = [];
        foreach ($role_skills as $role_skill) {
            $skill = Helpers::getSkillRowBySkillId($role_skill['skill_id']);
            if ($skill->is_base != 1) {
                continue;
            }
            $skill_status[$skill->id] = [
                'base_skill_name'  => $skill->name,
                'comprehensive_lv' => intval($role_skill['lv'] / 2),
            ];
            if ($role_skill['set_role_skill_id'] > 0) {
                $sect_skill = Helpers::getSkillRowBySkillId($role_skills[$role_skill['set_role_skill_id']]['skill_id']);
                $skill_status[$skill->id]['sect_skill_name'] = $sect_skill->name;
                $skill_status[$skill->id]['comprehensive_lv'] += $role_skills[$role_skill['set_role_skill_id']]['lv'];
            }
        }


        // $vip = match (true) {
        //     $request->roleRow->vip_score >= GameConfig::VIP10_SCORE => 10,
        //     $request->roleRow->vip_score >= GameConfig::VIP9_SCORE  => 9,
        //     $request->roleRow->vip_score >= GameConfig::VIP8_SCORE  => 8,
        //     $request->roleRow->vip_score >= GameConfig::VIP7_SCORE  => 7,
        //     $request->roleRow->vip_score >= GameConfig::VIP6_SCORE  => 6,
        //     $request->roleRow->vip_score >= GameConfig::VIP5_SCORE  => 5,
        //     $request->roleRow->vip_score >= GameConfig::VIP4_SCORE  => 4,
        //     $request->roleRow->vip_score >= GameConfig::VIP3_SCORE  => 3,
        //     $request->roleRow->vip_score >= GameConfig::VIP2_SCORE  => 2,
        //     $request->roleRow->vip_score >= GameConfig::VIP1_SCORE  => 1,
        //     default                                                 => 0,
        // };

        return $connection->send(\cache_response($request, \view('Role/Info/index.twig', [
            'request'            => $request,
            'role_title'         => $role_title,
            'role_age'           => intval($request->roleRow->age / 70 / 3600),
            'equipments'         => $equipments,
            'wugong_description' => Helpers::getWugongDescription($role_attrs->comprehensiveSkillLv),
            'attack_description' => Helpers::getAttackDescription($role_attrs->attack),
            'status_description' => Helpers::getStatusDescription($role_attrs->hp, $role_attrs->maxHp),
            'skill_status'       => $skill_status,
            // 'vip'                => $vip,
        ])));
    }


    /**
     * 手动数据存盘
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function save(TcpConnection $connection, Request $request)
    {
        /**
         * 存档操作
         *
         */
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);

        /**
         * 同步地图、气血、Nội lực、Nội lực、Tinh thần、Tu vi
         *
         */
        $sql = <<<SQL
UPDATE `roles` SET `kills` = {$request->roleRow->kills},
                   `killed` = {$request->roleRow->killed},
                   `release_time` = {$request->roleRow->release_time},
                   `red` = {$request->roleRow->red},
                   `click_times` = {$request->roleRow->click_times},
                   `login_times` = {$request->roleRow->login_times},
                   `map_id` = {$request->roleRow->map_id},
                   `hp` = $role_attrs->hp,
                   `mp` = $role_attrs->mp,
                   `qianneng` = $role_attrs->qianneng,
                   `jingshen` = $role_attrs->jingshen,
                   `experience` = $role_attrs->experience WHERE `id` = {$request->roleRow->id};
SQL;

        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Role/Info/save.twig', [
            'request' => $request,
        ])));
    }

    public function setting(TcpConnection $connection, Request $request){
        return $connection->send(\cache_response($request, \view('Role/Info/setting.twig', [
            'request' => $request,
        ])));
    }

    public function settingNick(TcpConnection $connection, Request $request){
        $role = Helpers::getRoleRowByRoleId($request->roleId);
        //print_r($role);
        return $connection->send(\cache_response($request, \view('Role/Info/settingNick.twig', [
            'request' => $request,
            'role' => $role
        ])));
    }

    public function saveNickName(TcpConnection $connection, Request $request){
        $nickname = $request->post("nickname");
        if (preg_match("/[\x7f-\xff]/", $nickname)) {  //判断字符串中是否有中文
            if (!empty($nickname)){
                $sql = "update roles set nickname = '".$nickname."' where id = ".$request->roleId;
                Helpers::execSql($sql);
                $role = Helpers::getRoleRowByRoleId($request->roleId);
                $role->nickname = $nickname;
                Helpers::setRoleRowByRoleId($request->roleId,$role);
                $message = 'Đặt biệt hiệu thành công!';
            }
        } else {
            if ($nickname == 0){
                $sql = "update roles set nickname = '' where id = ".$request->roleId;
                Helpers::execSql($sql);

                $role = Helpers::getRoleRowByRoleId($request->roleId);
                $role->nickname = '';
                Helpers::setRoleRowByRoleId($request->roleId,$role);
                $message = 'Biệt danh đã được xóa!';
            }else{
                $message = 'Biệt danh không hợp lý, vui lòng nhập 1-6 ký tự tiếng Trung, không được dùng từ nhạy cảm!';
            }

        }

        if (strlen($nickname) > 18){
            $message = 'Biệt danh không hợp lý, vui lòng nhập 1-6 ký tự tiếng Trung, không được dùng từ nhạy cảm!';
        }



        return $connection->send(\cache_response($request, \view('Role/Info/settingNick.twig', [
            'request' => $request,
            'action' => 'save',
            'message' => $message
        ])));
    }

    public function settingNickDescription(TcpConnection $connection, Request $request){
        return $connection->send(\cache_response($request, \view('Role/Info/settingNickDescription.twig', [
            'request' => $request
        ])));
    }

    public function settingEscapeRatio(TcpConnection $connection, Request $request){
        $escapeRatio = $request->post("escape_ratio");
        $action = 'form';
        $message = '';
        $role = Helpers::getRoleRowByRoleId($request->roleId);
            if (!empty($escapeRatio)){
                $action = 'save';
                if ($escapeRatio > 100 || $escapeRatio < 0){
                    $message = 'Tham số đầu vào không nằm trong phạm vi 0-100 và số chỉ có thể được điền trong khoảng 0-100.';
                }else{
                    $sql = "update roles set escape_ratio = '".$escapeRatio."' where id = ".$request->roleId;
                    Helpers::execSql($sql);

                    $role->escape_ratio = $escapeRatio;
                    Helpers::setRoleRowByRoleId($request->roleId,$role);
                    $message = 'Cài đặt thành công. Hệ số thoát hiện tại của bạn là'.$escapeRatio;
                }
            }


        return $connection->send(\cache_response($request, \view('Role/Info/settingEscapeRatio.twig', [
            'request' => $request,
            'action' => $action,
            'message' => $message,
            'role' => $role
        ])));
    }

    public function settingEscapeRatioDescription(TcpConnection $connection, Request $request){
        return $connection->send(\cache_response($request, \view('Role/Info/settingEscapeRatioDescription.twig', [
            'request' => $request
        ])));
    }
}
