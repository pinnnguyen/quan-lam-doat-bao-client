<?php

namespace App\Http\Controllers\Role;

use App\Core\Configs\GameConfig;
use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 排行榜
 *
 */
class TopController
{
    /**
     * 排行榜首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Role/Top/index.twig', [
            'request' => $request,
        ])));
    }


    /**
     * 高手如云
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $page
     *
     * @return bool|null
     */
    public function GaoShou(TcpConnection $connection, Request $request, int $page = 1)
    {
        $top_gaoshou_roles = cache()->get('top_gaoshou_roles');
        if (empty($top_gaoshou_roles)) {
            $top_gaoshou_roles = [];
        }
        $next = false;
        if ($page == 1) {
            if (count($top_gaoshou_roles) > 50) {
                $next = true;
            }
            $top_gaoshou_roles = array_slice($top_gaoshou_roles, 0, 50);
            $second = false;
        } else {
            $top_gaoshou_roles = array_slice($top_gaoshou_roles, 50, 100);
            $second = true;
        }
        foreach ($top_gaoshou_roles as $key => $top_gaoshou_role) {
            $role = Helpers::getRoleRowByRoleId($top_gaoshou_role['id']);
            if ($role) {
                $top_gaoshou_roles[$key]['online'] = true;
                if ($top_gaoshou_role['id'] == $request->roleRow->id) {
                    $top_gaoshou_roles[$key]['viewUrl'] = 'Role/Info/index';
                } else {
                    $top_gaoshou_roles[$key]['viewUrl'] = 'Map/Role/view/' . $top_gaoshou_role['id'];
                }
            } else {
                $top_gaoshou_roles[$key]['online'] = false;
            }
        }
        return $connection->send(\cache_response($request, \view('Role/Top/GaoShou.twig', [
            'request' => $request,
            'tops'    => $top_gaoshou_roles,
            'second'  => $second,
            'next'    => $next,
        ])));
    }

    /**
     * Võ công cái thế
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $page
     *
     * @return bool|null
     */
    public function wuGong(TcpConnection $connection, Request $request, int $page = 1)
    {
        $top_wugong_roles = cache()->get('top_wugong_roles');
        if (empty($top_wugong_roles)) {
            $top_wugong_roles = [];
        }
        $next = false;
        if ($page == 1) {
            if (count($top_wugong_roles) > 50) {
                $next = true;
            }
            $top_wugong_roles = array_slice($top_wugong_roles, 0, 50);
            $second = false;
        } else {
            $top_wugong_roles = array_slice($top_wugong_roles, 50, 100);
            $second = true;
        }
        foreach ($top_wugong_roles as $key => $top_wugong_role) {
            $role = Helpers::getRoleRowByRoleId($top_wugong_role['id']);
            if ($role) {
                $top_wugong_roles[$key]['online'] = true;
                if ($top_wugong_role['id'] == $request->roleRow->id) {
                    $top_wugong_roles[$key]['viewUrl'] = 'Role/Info/index';
                } else {
                    $top_wugong_roles[$key]['viewUrl'] = 'Map/Role/view/' . $top_wugong_role['id'];
                }
            } else {
                $top_wugong_roles[$key]['online'] = false;
            }
        }
        return $connection->send(\cache_response($request, \view('Role/Top/wuGong.twig', [
            'request' => $request,
            'tops'    => $top_wugong_roles,
            'second'  => $second,
            'next'    => $next,
        ])));
    }
	
	/**
     * Tâm pháp đứng hàng
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $page
     *
     * @return bool|null
     */
    public function XinFa(TcpConnection $connection, Request $request, int $page = 1)
    {
        $top_xinfa_roles = cache()->get('top_xinfa_roles');
        if (empty($top_xinfa_roles)) {
            $top_xinfa_roles = [];
        }
        $next = false;
        if ($page == 1) {
            if (count($top_xinfa_roles) > 50) {
                $next = true;
            }
            $top_xinfa_roles = array_slice($top_xinfa_roles, 0, 50);
            $second = false;
        } else {
            $top_xinfa_roles = array_slice($top_xinfa_roles, 50, 100);
            $second = true;
        }
        foreach ($top_xinfa_roles as $key => $top_xinfa_role) {
            $role = Helpers::getRoleRowByRoleId($top_xinfa_role['id']);
            if ($role) {
                $top_xinfa_roles[$key]['online'] = true;
                if ($top_xinfa_role['id'] == $request->roleRow->id) {
                    $top_xinfa_roles[$key]['viewUrl'] = 'Role/Info/index';
                } else {
                    $top_xinfa_roles[$key]['viewUrl'] = 'Map/Role/view/' . $top_xinfa_role['id'];
                }
            } else {
                $top_xinfa_roles[$key]['online'] = false;
            }
        }
        return $connection->send(\cache_response($request, \view('Role/Top/XinFa.twig', [
            'request' => $request,
            'tops'    => $top_xinfa_roles,
            'second'  => $second,
            'next'    => $next,
        ])));
    }


    /**
     * Tuyệt thế mỹ nữ
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $page
     *
     * @return bool|null
     */
    public function JueShi(TcpConnection $connection, Request $request, int $page = 1)
    {
        $top_jueshi_roles = cache()->get('top_jueshi_roles');
        if (empty($top_jueshi_roles)) {
            $top_jueshi_roles = [];
        }
        $next = false;
        if ($page == 1) {
            if (count($top_jueshi_roles) > 50) {
                $next = true;
            }
            $top_jueshi_roles = array_slice($top_jueshi_roles, 0, 50);
            $second = false;
        } else {
            $top_jueshi_roles = array_slice($top_jueshi_roles, 50, 100);
            $second = true;
        }
        foreach ($top_jueshi_roles as $key => $top_jueshi_role) {
            $role = Helpers::getRoleRowByRoleId($top_jueshi_role['id']);
            if ($role) {
                $top_jueshi_roles[$key]['online'] = true;
                if ($top_jueshi_role['id'] == $request->roleRow->id) {
                    $top_jueshi_roles[$key]['viewUrl'] = 'Role/Info/index';
                } else {
                    $top_jueshi_roles[$key]['viewUrl'] = 'Map/Role/view/' . $top_jueshi_role['id'];
                }
            } else {
                $top_jueshi_roles[$key]['online'] = false;
            }
        }
        return $connection->send(\cache_response($request, \view('Role/Top/JueShi.twig', [
            'request' => $request,
            'tops'    => $top_jueshi_roles,
            'second'  => $second,
            'next'    => $next,
        ])));
    }


    /**
     * Máu lạnh sát thủ
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $page
     *
     * @return bool|null
     */
    public function LengXue(TcpConnection $connection, Request $request, int $page = 1)
    {
        $top_lengxue_roles = cache()->get('top_lengxue_roles');
        if (empty($top_lengxue_roles)) {
            $top_lengxue_roles = [];
        }
        $next = false;
        if ($page == 1) {
            if (count($top_lengxue_roles) > 50) {
                $next = true;
            }
            $top_lengxue_roles = array_slice($top_lengxue_roles, 0, 50);
            $second = false;
        } else {
            $top_lengxue_roles = array_slice($top_lengxue_roles, 50, 100);
            $second = true;
        }
        foreach ($top_lengxue_roles as $key => $top_lengxue_role) {
            $role = Helpers::getRoleRowByRoleId($top_lengxue_role['id']);
            if ($role) {
                $top_lengxue_roles[$key]['online'] = true;
                if ($top_lengxue_role['id'] == $request->roleRow->id) {
                    $top_lengxue_roles[$key]['viewUrl'] = 'Role/Info/index';
                } else {
                    $top_lengxue_roles[$key]['viewUrl'] = 'Map/Role/view/' . $top_lengxue_role['id'];
                }
            } else {
                $top_lengxue_roles[$key]['online'] = false;
            }
        }
        return $connection->send(\cache_response($request, \view('Role/Top/LengXue.twig', [
            'request' => $request,
            'tops'    => $top_lengxue_roles,
            'second'  => $second,
            'next'    => $next,
        ])));
    }


    /**
     * Phong lưu phóng khoáng
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $page
     *
     * @return bool|null
     */
    public function FengLiu(TcpConnection $connection, Request $request, int $page = 1)
    {
        $top_fengliu_roles = cache()->get('top_fengliu_roles');
        if (empty($top_fengliu_roles)) {
            $top_fengliu_roles = [];
        }
        $next = false;
        if ($page == 1) {
            if (count($top_fengliu_roles) > 50) {
                $next = true;
            }
            $top_fengliu_roles = array_slice($top_fengliu_roles, 0, 50);
            $second = false;
        } else {
            $top_fengliu_roles = array_slice($top_fengliu_roles, 50, 100);
            $second = true;
        }
        foreach ($top_fengliu_roles as $key => $top_fengliu_role) {
            $role = Helpers::getRoleRowByRoleId($top_fengliu_role['id']);
            if ($role) {
                $top_fengliu_roles[$key]['online'] = true;
                if ($top_fengliu_role['id'] == $request->roleRow->id) {
                    $top_fengliu_roles[$key]['viewUrl'] = 'Role/Info/index';
                } else {
                    $top_fengliu_roles[$key]['viewUrl'] = 'Map/Role/view/' . $top_fengliu_role['id'];
                }
            } else {
                $top_fengliu_roles[$key]['online'] = false;
            }
        }
        return $connection->send(\cache_response($request, \view('Role/Top/FengLiu.twig', [
            'request' => $request,
            'tops'    => $top_fengliu_roles,
            'second'  => $second,
            'next'    => $next,
        ])));
    }


    /**
     * Phú giáp thiên hạ
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $page
     *
     * @return bool|null
     */
    public function FuJia(TcpConnection $connection, Request $request, int $page = 1)
    {
        $top_fujia_roles = cache()->get('top_fujia_roles');
        if (empty($top_fujia_roles)) {
            $top_fujia_roles = [];
        }
        $next = false;
        if ($page == 1) {
            if (count($top_fujia_roles) > 50) {
                $next = true;
            }
            $top_fujia_roles = array_slice($top_fujia_roles, 0, 50);
            $second = false;
        } else {
            $top_fujia_roles = array_slice($top_fujia_roles, 50, 100);
            $second = true;
        }
        foreach ($top_fujia_roles as $key => $top_fujia_role) {
            $role = Helpers::getRoleRowByRoleId($top_fujia_role['id']);
            if ($role) {
                $top_fujia_roles[$key]['online'] = true;
                if ($top_fujia_role['id'] == $request->roleRow->id) {
                    $top_fujia_roles[$key]['viewUrl'] = 'Role/Info/index';
                } else {
                    $top_fujia_roles[$key]['viewUrl'] = 'Map/Role/view/' . $top_fujia_role['id'];
                }
            } else {
                $top_fujia_roles[$key]['online'] = false;
            }
        }
        return $connection->send(\cache_response($request, \view('Role/Top/FuJia.twig', [
            'request' => $request,
            'tops'    => $top_fujia_roles,
            'second'  => $second,
            'next'    => $next,
        ])));
    }


    /**
     * 名动江湖
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $page
     *
     * @return bool|null
     */
    public function MingDong(TcpConnection $connection, Request $request, int $page = 1)
    {
        return $connection->send(\cache_response($request, \view('Role/Top/MingDong.twig', [
            'request' => $request,
        ])));
    }


    /**
     * @param \Workerman\Connection\TcpConnection $connection
     * @param \Workerman\Protocols\Http\Request   $request
     *
     * @return null|bool
     */
    public function Vip(TcpConnection $connection, Request $request)
    {
        $top_vip_roles = cache()->get('top_vip_roles');
        if (empty($top_vip_roles)) {
            $top_vip_roles = [];
        }
        foreach ($top_vip_roles as $key => $top_vip_role) {
            $role = Helpers::getRoleRowByRoleId($top_vip_role['id']);
            if ($role) {
                $top_vip_roles[$key]['online'] = true;
                if ($top_vip_role['id'] == $request->roleRow->id) {
                    $top_vip_roles[$key]['viewUrl'] = 'Role/Info/index';
                } else {
                    $top_vip_roles[$key]['viewUrl'] = 'Map/Role/view/' . $top_vip_role['id'];
                }
            } else {
                $top_vip_roles[$key]['online'] = false;
            }
        }
        return $connection->send(\cache_response($request, \view('Role/Top/Vip.twig', [
            'request' => $request,
            'tops'    => $top_vip_roles,
        ])));
    }


    /**
     * @param \Workerman\Connection\TcpConnection $connection
     * @param \Workerman\Protocols\Http\Request   $request
     *
     * @return null|bool
     */
    public function VipDay(TcpConnection $connection, Request $request)
    {
        $sql = <<<SQL
SELECT `vip_get_time`, `vip_score`, `vip_double_time` FROM `roles` WHERE `id` = $request->roleId; 
SQL;

        $role = Helpers::queryFetchObject($sql);
        if (!is_object($role) or $role->vip_get_time > strtotime(date('Y-m-d'))) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => '你已经领取过今天的奖励了！',
            ])));
        }
        $time = match (true) {
            $role->vip_score >= GameConfig::VIP10_SCORE => 300,
            $role->vip_score >= GameConfig::VIP9_SCORE  => 240,
            $role->vip_score >= GameConfig::VIP8_SCORE  => 190,
            $role->vip_score >= GameConfig::VIP7_SCORE  => 150,
            $role->vip_score >= GameConfig::VIP6_SCORE  => 120,
            $role->vip_score >= GameConfig::VIP5_SCORE  => 100,
            $role->vip_score >= GameConfig::VIP4_SCORE  => 80,
            $role->vip_score >= GameConfig::VIP3_SCORE  => 60,
            $role->vip_score >= GameConfig::VIP2_SCORE  => 40,
            $role->vip_score >= GameConfig::VIP1_SCORE  => 20,
            default                                     => 10,
        };
        if ($role->vip_double_time > time()) {
            $request->roleRow->vip_double_time = $role->vip_double_time + $time * 60;
        } else {
            $request->roleRow->vip_double_time = $time * 60 + time();
        }
        $ttime = time();
        $sql = <<<SQL
UPDATE `roles` SET `vip_double_time` = {$request->roleRow->vip_double_time}, `vip_get_time` = $ttime WHERE `id` = $request->roleId;
SQL;

        Helpers::execSql($sql);
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);

        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => 'Lĩnh thành công, ngươi đạt được' . $time . 'Phút gấp đôi tu tiềm thời gian, mau đi thăng cấp đi!',
        ])));
    }


    /**
     * @param \Workerman\Connection\TcpConnection $connection
     * @param \Workerman\Protocols\Http\Request   $request
     *
     * @return null|bool
     */
    public function VipLevel(TcpConnection $connection, Request $request)
    {
        $sql = <<<SQL
SELECT `vip_score`, `vip_reward` FROM `roles` WHERE `id` = $request->roleId; 
SQL;

        $role = Helpers::queryFetchObject($sql);
        if (!is_object($role)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi không có có thể lĩnh lễ bao!',
            ])));
        }


        if ($role->vip_score >= GameConfig::VIP10_SCORE && ($role->vip_reward | GameConfig::VIP10) != $role->vip_reward) {
            $vip_reward = $role->vip_reward | GameConfig::VIP10;
            $num = 60;
            $vip = 10;

        } elseif ($role->vip_score >= GameConfig::VIP9_SCORE && ($role->vip_reward | GameConfig::VIP9) != $role->vip_reward) {
            $vip_reward = $role->vip_reward | GameConfig::VIP9;
            $num = 46;
            $vip = 9;

        } elseif ($role->vip_score >= GameConfig::VIP8_SCORE && ($role->vip_reward | GameConfig::VIP8) != $role->vip_reward) {
            $vip_reward = $role->vip_reward | GameConfig::VIP8;
            $num = 34;
            $vip = 8;

        } elseif ($role->vip_score >= GameConfig::VIP7_SCORE && ($role->vip_reward | GameConfig::VIP7) != $role->vip_reward) {
            $vip_reward = $role->vip_reward | GameConfig::VIP7;
            $num = 24;
            $vip = 7;

        } elseif ($role->vip_score >= GameConfig::VIP6_SCORE && ($role->vip_reward | GameConfig::VIP6) != $role->vip_reward) {
            $vip_reward = $role->vip_reward | GameConfig::VIP6;
            $num = 16;
            $vip = 6;

        } elseif ($role->vip_score >= GameConfig::VIP5_SCORE && ($role->vip_reward | GameConfig::VIP5) != $role->vip_reward) {
            $vip_reward = $role->vip_reward | GameConfig::VIP5;
            $num = 10;
            $vip = 5;

        } elseif ($role->vip_score >= GameConfig::VIP4_SCORE && ($role->vip_reward | GameConfig::VIP4) != $role->vip_reward) {
            $vip_reward = $role->vip_reward | GameConfig::VIP4;
            $num = 8;
            $vip = 4;

        } elseif ($role->vip_score >= GameConfig::VIP3_SCORE && ($role->vip_reward | GameConfig::VIP3) != $role->vip_reward) {
            $vip_reward = $role->vip_reward | GameConfig::VIP3;
            $num = 6;
            $vip = 3;

        } elseif ($role->vip_score >= GameConfig::VIP2_SCORE && ($role->vip_reward | GameConfig::VIP2) != $role->vip_reward) {
            $vip_reward = $role->vip_reward | GameConfig::VIP2;
            $num = 4;
            $vip = 2;

        } elseif ($role->vip_score >= GameConfig::VIP1_SCORE && ($role->vip_reward | GameConfig::VIP1) != $role->vip_reward) {
            $vip_reward = $role->vip_reward | GameConfig::VIP1;
            $num = 2;
            $vip = 1;

        } else {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi không có có thể lĩnh lễ bao!',
            ])));
        }

        $sql = <<<SQL
SELECT * FROM `role_djs` WHERE `role_id` = $request->roleId AND `dj_id` = 12;
SQL;

        $role_dj = Helpers::queryFetchObject($sql);

        if ($role_dj) {
            $sql = <<<SQL
UPDATE `role_djs` SET `number` = `number` + $num WHERE `id` = $role_dj->id;
SQL;

        } else {
            $sql = <<<SQL
INSERT INTO `role_djs` (`role_id`, `dj_id`, `number`) VALUES ($request->roleId, 12, $num);
SQL;

        }
        $sql .= <<<SQL
UPDATE `roles` SET `vip_reward` = $vip_reward WHERE `id` = $request->roleId;
SQL;

        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => 'Lĩnh VIP ' . $vip . ' Lễ bao thành công, ngươi đạt được ' . $num . ' Đem tâm pháp bảo rương chìa khóa!',
        ])));
    }


    /**
     * @param \Workerman\Connection\TcpConnection $connection
     * @param \Workerman\Protocols\Http\Request   $request
     *
     * @return null|bool
     */
    public function VipRe(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Role/Top/VipRe.twig', [
            'request' => $request,
        ])));
    }
}
