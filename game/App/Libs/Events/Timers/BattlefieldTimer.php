<?php

namespace App\Libs\Events\Timers;

use App\Libs\Events\Timers\Battlefield\PlayerDuelNPC;
use App\Libs\Events\Timers\Battlefield\PlayerDuelPlayer;
use App\Libs\Events\Timers\Battlefield\PlayerKillNPC;
use App\Libs\Events\Timers\Battlefield\PlayerKillPlayer;
use App\Libs\Helpers;

/**
 * 战场
 *
 */
class BattlefieldTimer
{
    public static function hook()
    {

        // echo "BattlefieldTimer\r\n";

        /**
         * 获取所有战场 key
         *
         */
        $battlefield_keys = cache()->keys('role_battlefield_*');
        if (!is_array($battlefield_keys)) {
            return;
        }
        //Helpers::log_message(var_export($battlefield_keys,true));

        /**
         * 单进程循环处理战场
         *
         */
        foreach ($battlefield_keys as $battlefield_key) {

            /**
             * 获取玩家战场状态
             *
             */
            $battlefield = cache()->hMGet($battlefield_key, [
                'id', 'role_id',
                'b1_state', 'b1_object', 'b1_id', 'b1_kind', 'b1_form', 'b1_action',
                'b2_state', 'b2_object', 'b2_id', 'b2_kind', 'b2_form', 'b2_action',
                'b3_state', 'b3_object', 'b3_id', 'b3_kind', 'b3_form', 'b3_action',
            ]);
            //Helpers::log_message(var_export($battlefield,true));
            /**
             * 玩家 / NPC 杀戮 / 切磋 玩家 / NPC
             *
             */
            for ($i = 1; $i <= 3; $i++) {

                /**
                 * 判断战场是否存在
                 *
                 */
                if ($battlefield['b' . $i . '_state']) {

                    /**
                     * 判断对战目标
                     *
                     */
                    if ($battlefield['b' . $i . '_object'] === 1) {
                        if ($battlefield['b' . $i . '_kind'] === 1) {
                            PlayerKillPlayer::hook($battlefield, $i);
                        } else {
                            PlayerDuelPlayer::hook($battlefield, $i);
                        }
                    } else {
                        if ($battlefield['b' . $i . '_kind'] === 1) {
                            PlayerKillNPC::hook($battlefield, $i);
                        } else {
                            PlayerDuelNPC::hook($battlefield, $i);
                        }
                    }
                }
            }
        }
    }
}
