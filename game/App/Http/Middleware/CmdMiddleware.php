<?php

namespace App\Http\Middleware;

use App\Core\Configs\FlushConfig;
use App\Http\Controllers\Error\HttpController;
use App\Libs\Attrs\FlushRoleAttrs;
use App\Libs\Helpers;
use Closure;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * Cmd 检测
 *
 */
class CmdMiddleware
{
    /**
     * cmd 检测中间件
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param Closure       $next
     *
     * @return mixed
     */
    public function handle(TcpConnection $connection, Request $request, Closure $next): mixed
    {
        /**
         * 检查 cmd
         *
         */

        $request->cmd = $request->get('cmd');
        //Helpers::log_message("cmd:".$request->cmd);
        if (empty($request->cmd)) {
            return (new HttpController())->notFound($connection, $request);
        }

        /**
         * 检查 sid
         *
         */
        $request->roleSid = $request->get('sid');
        if (empty($request->roleSid)) {
            return (new HttpController())->unauthorized($connection, $request);
        }

        /**
         * 检查 role_sid & role_id
         *
         */
        $request->roleId = Helpers::getRoleIdByRoleSid($request->roleSid);
        if (empty($request->roleId)) {
            return (new HttpController())->unauthorized($connection, $request);
        }

        [$request->roleCmds, $request->roleRow, $flush_weight] = cache()->mget([
            'role_cmds_' . $request->roleId,
            'role_row_' . $request->roleId,
            'role_flush_weight_' . $request->roleId,
        ]);
        /**
         * 检查 role_cmds
         *
         */
//        $request->roleCmds = cache()->get('role_cmds_' . $request->roleId);
        if (empty($request->roleCmds)) {
            return (new HttpController())->notFound($connection, $request);
        }

        /**
         * 检查 role_row
         */
//        $request->roleRow = Helpers::getRoleRowByRoleId($request->roleId);
        if (empty($request->roleRow)) {
            return (new HttpController())->notFound($connection, $request);
        }

        /**
         * 检查 cmd & cmds 集
         *
         */
        //Helpers::log_message("roleCmds:".var_export($request->roleCmds,true));
        if (is_array($request->roleCmds) and array_key_exists($request->cmd, $request->roleCmds)) {
            $pipeline = cache()->pipeline();
            $pipeline->expire('role_id_' . $request->roleSid, FlushConfig::ROLE);
            $pipeline->sAdd('ip_' . $connection->getRemoteIp(), $request->roleId);
            $pipeline->set('role_ip_' . $request->roleId, $connection->getRemoteIp());
            // for ($i = 0; $i < 20; $i++) {
            //     $pipeline->get('requests');
            // }
            $pipeline->incr('requests');
            $pipeline->exec();
            if ($flush_weight) {
                cache()->set('role_flush_weight_' . $request->roleId, false);
                FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);
                FlushRoleAttrs::fromRoleEquipmentByRoleId($request->roleId);
                FlushRoleAttrs::fromRoleXinfaByRoleId($request->roleId);
            }
            $response = $next($connection, $request);

            /**
             * 更新 cmds 、更新 role_row、更新 role_id 过期时间
             *
             */
//            $request->roleRow->click_times++;
//            $request->roleRow->ip = $connection->getRemoteIp();
//            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
            cache()->set('role_cmds_' . $request->roleId, $request->roleCmds);
            return $response;
        } else {
            /**
             * 检查视图
             *
             */
            $view = cache()->get('role_view_' . $request->roleId);
            if (empty($view)) {
                return (new HttpController())->notFound($connection, $request);
            } else {
                return $connection->send(\response($view));
            }
        }
    }
}
