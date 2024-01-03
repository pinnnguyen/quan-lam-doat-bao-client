<?php
/**
 * @date   2022/4/30 17:19
 * @author pinerge@gmail.com
 */
declare(strict_types=1);

namespace App\Http\Controllers\Role;

use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

class RelationController
{
    /**
     * @param \Workerman\Connection\TcpConnection $connection
     * @param \Workerman\Protocols\Http\Request   $request
     *
     * @return null|bool
     */
    public function index(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Role/Relation/index.twig', [
            'request' => $request,
        ])));
    }


    /**
     * @param \Workerman\Connection\TcpConnection $connection
     * @param \Workerman\Protocols\Http\Request   $request
     *
     * @return null|bool
     */
    public function enemy(TcpConnection $connection, Request $request)
    {
        $sql = <<<SQL
SELECT DISTINCT `roles`.`id`, `roles`.`name` FROM `role_kill_logs`
INNER JOIN `roles` ON `role_kill_logs`.`o_role_id` = `roles`.`id`
WHERE `role_kill_logs`.`role_id` = $request->roleId
ORDER BY `role_kill_logs`.`id` DESC
LIMIT 20;
SQL;

        $enemies = Helpers::queryFetchAll($sql);

        foreach ($enemies as $enemy) {
            if (cache()->exists('role_attrs_' . $enemy->id)) {
                $enemy->online = true;
                $enemy->viewUrl = 'Map/Role/view/' . $enemy->id;
            } else {
                $enemy->online = false;
            }
        }

        return $connection->send(\cache_response($request, \view('Role/Relation/enemy.twig', [
            'request' => $request,
            'enemies' => $enemies,
        ])));
    }
}