<?php

namespace App\Http\Controllers\Role;

use App\Http\Controllers\Map\QunFangLouController;
use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * Nhiệm Vụ
 */
class MissionController
{
    /**
     * Nhiệm Vụ首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        /**
         * 任务状态
         */
        $no_mission = true;

        /**
         * 获取当前连续任务
         */
        $consecutive_mission = json_decode($request->roleRow->mission);
        if ($consecutive_mission and $consecutive_mission->verified) {
            $display_consecutive_mission = true;
            $no_mission = false;
        }

        /**
         * 群芳楼任务
         */
        $sql = <<<SQL
SELECT * FROM `role_qunfanglou_missions` WHERE `role_id` = $request->roleId;
SQL;

        $role_qunfanglou_mission = Helpers::queryFetchObject($sql);
        if ($role_qunfanglou_mission) {
            $role_qunfanglou_mission_messages = [];
            if ($role_qunfanglou_mission->dao_status == 1) {
                $thing = Helpers::getThingRowByThingId(QunFangLouController::$qfls[746][$role_qunfanglou_mission->dao_number]);
                $role_qunfanglou_mission_messages[] = 'Đao Bạch Phượng tưởng thỉnh ngươi tìm một' . $thing->unit . $thing->name . '。';
                $no_mission = false;
            }
            if ($role_qunfanglou_mission->jian_status == 1) {
                $thing = Helpers::getThingRowByThingId(QunFangLouController::$qfls[743][$role_qunfanglou_mission->jian_number]);
                $role_qunfanglou_mission_messages[] = 'Khang mẫn tưởng thỉnh ngươi tìm một' . $thing->unit . $thing->name . '。';
                $no_mission = false;
            }
            if ($role_qunfanglou_mission->zhua_status == 1) {
                $thing = Helpers::getThingRowByThingId(QunFangLouController::$qfls[744][$role_qunfanglou_mission->zhua_number]);
                $role_qunfanglou_mission_messages[] = 'Mộng cô tưởng thỉnh ngươi tìm một' . $thing->unit . $thing->name . '。';
                $no_mission = false;
            }
            if ($role_qunfanglou_mission->shoes_status == 1) {
                $thing = Helpers::getThingRowByThingId(QunFangLouController::$qfls[745][$role_qunfanglou_mission->shoes_number]);
                $role_qunfanglou_mission_messages[] = 'A Bích tưởng thỉnh ngươi tìm một' . $thing->unit . $thing->name . '。';
                $no_mission = false;
            }
            if ($role_qunfanglou_mission->clothes_status == 1) {
                $thing = Helpers::getThingRowByThingId(QunFangLouController::$qfls[751][$role_qunfanglou_mission->clothes_number]);
                $role_qunfanglou_mission_messages[] = 'A Tử tưởng thỉnh ngươi tìm một' . $thing->unit . $thing->name . '。';
                $no_mission = false;
            }
            if ($role_qunfanglou_mission->armor_status == 1) {
                $thing = Helpers::getThingRowByThingId(QunFangLouController::$qfls[752][$role_qunfanglou_mission->armor_number]);
                $role_qunfanglou_mission_messages[] = 'A Chu tưởng thỉnh ngươi tìm một' . $thing->unit . $thing->name . '。';
                $no_mission = false;
            }
        }
        return $connection->send(\cache_response($request, \view('Role/Mission/index.twig', [
            'request'                     => $request,
            'display_consecutive_mission' => $display_consecutive_mission ?? false,
            'no_mission'                  => $no_mission,
            'qunfanglou_mission_messages' => $role_qunfanglou_mission_messages ?? null,
        ])));
    }
}
