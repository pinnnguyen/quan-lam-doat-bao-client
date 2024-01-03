<?php

namespace App\Http\Controllers\Help;

use App\Core\Configs\ServerConfig;
use App\Http\Controllers\Error\HttpController;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 服务器状态
 *
 */
class StatusController
{
    /**
     * 状态Xem xét
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        $ips = cache()->keys('ip_*');
        $roles = cache()->mget(cache()->keys('role_row_*'));
        if (empty($roles) or empty($ips)) {
            return (new HttpController())->notFound($connection, $request);
        }
        $roles = array_column($roles, null, 'id');
        $infos = [];
        foreach ($ips as $ip) {
            $ip_address = substr($ip, 3);
            $infos[$ip] = [
                'ip'    => $ip_address,
                'count' => cache()->get($ip),
                'roles' => '',
            ];
            $infos[$ip]['rate'] = round($infos[$ip]['count'] / (microtime(true) - ServerConfig::$startMicroTime), 2);
        }
        foreach ($roles as $role) {
            if (!empty($role->ip)) {
                $ip = 'ip_' . $role->ip;
                if (in_array($ip, $ips)) {
                    $infos[$ip]['roles'] .= $role->name . '、';
                }
            }
        }
        $counts = array_column($infos, 'count');
        array_multisort($counts, SORT_DESC, $infos);
        return $connection->send(\response(\view('Help/Status/index.twig', [
            'request'           => $request,
            'online_role_count' => count($roles),
            'online_ip_count'   => count($ips),
            'infos'             => $infos,
        ])));
    }
}
