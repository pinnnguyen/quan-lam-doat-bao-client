<?php

namespace App\Libs\Objects;

/**
 * 角色原生数据
 *
 */
class RoleRow
{
    public int $id;
    public int $user_id;
    public string $sid;
    public string $name;
    public string $gender;
    public string $appearance;
    public int $age;
    public bool $plot;
    public int $sect_id;
    public int $master;
    public int $seniority;
    public int $leave_timestamp;
    public int $map_id;
    public int $experience;
    public int $saved_experience;
    public int $hp;
    public int $mp;
    public int $base_jingshen;
    public int $jingshen;
    public int $qianneng;
    public int $bank_balance;
//    public string $question1_timestamp;
//    public string $question2_timestamp;
    public ?string $question_timestamp;
    public int $click_times;
    public int $login_times;
    public int $charm;

    public int $storage;
    public int $xinfa_storage;

    public int $double_experience;
    public int $triple_experience;
    public int $double_qianneng;
    public int $triple_qianneng;
    public int $double_xinfa;
    public int $triple_xinfa;
    public int $no_kill;
    public int $no_kill_times;

    public int $renshen;

    public int $switch_public;
    public int $switch_stranger;
    public int $switch_arena;
    public int $switch_faction;
    public int $switch_rumour;
    public int $switch_jianghu;

    public int $vip_double_time;
    public int $vip_score;

    public int $kills;
    public int $killed;

    public int $red;
    public int $release_time;

    public ?string $follows;
    public ?string $blocks;
    public ?string $mission;
    public ?string $ip;

}
