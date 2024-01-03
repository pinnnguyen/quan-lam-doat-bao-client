<?php

namespace App\Http\Controllers\Map;

use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * Đối thoại
 */
class DialogueController
{
    /**
     * NPC Đối thoại
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $npc_id
     *
     * @return bool|null
     */
    public function npc(TcpConnection $connection, Request $request, int $npc_id)
    {
        /**
         * 获取 NPC
         *
         */
        $npc_row = Helpers::getNpcRowByNpcId($npc_id);

        /**
         * 获取台词
         *
         */
        $sentences = json_decode($npc_row->dialogues, true);
        if (empty($sentences)) {
            $sentence = Helpers::randomSentence();
        } else {
            $sentence = $sentences[array_rand($sentences)];
        }

        /**
         * 判断是否有任务
         *
         */
        if (!empty($request->roleRow->mission)) {
            $mission = json_decode($request->roleRow->mission);
            if ($mission->circle == 3) {
                /**
                 * NPC Đối thoại任务、判断是否完成
                 *
                 */
                if (!$mission->status and $mission->npcId == $npc_id) {
                    /**
                     * 完成任务
                     *
                     */
                    $mission->status = true;
                    $request->roleRow->mission = json_encode($mission, JSON_UNESCAPED_UNICODE);
                    $consecutive_mission_message = 'Ngươi hoàn thành lần này liên tục nhiệm vụ, mau đi lãnh thưởng đi!';
                    Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                }
            }
        }

        return $connection->send(\cache_response($request, \view('Map/Dialogue/npc.twig', [
            'request'                     => $request,
            'sentence'                    => $sentence,
            'consecutive_mission_message' => $consecutive_mission_message ?? null,
        ])));
    }
}
