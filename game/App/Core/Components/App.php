<?php

namespace App\Core\Components;

use App\Http\Controllers\Map\BattlefieldController;
use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * App 应用
 */
class App
{
    /**
     * 启动 App
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function run(TcpConnection $connection, Request $request)
    {
        /**
         * 获取路由参数
         *
         */
        $actions = explode('/', $request->roleCmds[$request->cmd]);
        //Helpers::log_message("actions:".var_export($actions,true));
        $request->roleCmds = array_key_last($request->roleCmds);
        //Helpers::log_message("roleCmds:".var_export($request->roleCmds,true));
        /**
         * 检查是否在战斗中
         *
         */
        if (!($actions[0] === 'Map' && $actions[1] === 'Battlefield')) {
            $battlefield = cache()->hMGet('role_battlefield_' . $request->roleId, ['b1_state', 'b2_state', 'b3_state', 'b4_state', 'b5_state', 'b6_state', 'b7_state', 'b8_state', 'b9_state', 'b10_state']);
            if ($battlefield['b1_state'] or $battlefield['b2_state'] or $battlefield['b3_state'] or $battlefield['b4_state'] or $battlefield['b5_state'] or $battlefield['b6_state'] or $battlefield['b7_state'] or $battlefield['b8_state'] or $battlefield['b9_state'] or $battlefield['b10_state']) {
                if (!($actions[0] === 'Func' && ($actions[1] === 'Transaction' or $actions[1] === 'Give'))) {
                    return (new BattlefieldController())->state($connection, $request);
                }
            }
        }

        /**
         * 分析获取控制器参数
         *
         */
        $controller_class = '\App\Http\Controllers\\' . $actions[0] . '\\' . $actions[1] . 'Controller';

        $arguments = [$connection, $request];

        if (count($actions) > 3) {
            $key = 3;
            do {
                $arguments[] = $actions[$key];
                $key++;
            } while (isset($actions[$key]));
        }

        /**
         * 执行控制器
         *
         */
        return call_user_func_array([new $controller_class(), $actions[2]], $arguments);
    }
}
