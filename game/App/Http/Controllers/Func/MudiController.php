<?php

namespace App\Http\Controllers\Func;

use App\Http\Controllers\Map\IndexController;
use App\Libs\Attrs\FlushRoleAttrs;
use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 墓地
 *
 */
class MudiController
{
    /**
     * 推石门
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function pushStone(TcpConnection $connection, Request $request)
    {
        /**
         * 获取队列
         *
         */
        $push_stone_state = cache()->get('push_stone_state');
        if ($push_stone_state) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Cửa đá đã bị đẩy ra, thỉnh đại hiệp nắm chặt thời gian tiến vào.',
            ])));
        }
        $pipeline = cache()->pipeline();
        $pipeline->lRem('push_stone_pushers', $request->roleId, 0);
        $pipeline->lPush('push_stone_pushers', $request->roleId);
        $pipeline->expire('push_stone_pushers', 2);
        $pipeline->set('role_push_stone_' . $request->roleId, microtime(true));
        $pipeline->expire('role_push_stone_' . $request->roleId, 2);
        $pipeline->exec();
        $pushers = cache()->lRange('push_stone_pushers', 0, 2);
        if (count($pushers) >= 3) {
            $timestamps = cache()->mget([
                'role_push_stone_' . $pushers[0],
                'role_push_stone_' . $pushers[1],
                'role_push_stone_' . $pushers[2],
            ]);
            if (empty($timestamps[0]) or empty($timestamps[1]) or empty($timestamps[2])) {
                return $connection->send(\cache_response($request, \view('Base/message.twig', [
                    'request' => $request,
                    'message' => 'Ngươi thử đẩy đẩy cự thạch, cự thạch không chút sứt mẻ, chỉ phải từ bỏ, xem ra sức của một người chung quy là không được, vẫn là đến nhiều tìm mấy cái giúp đỡ tới.',
                ])));
            }

            /**
             * 打开石门
             *
             */
            cache()->set('push_stone_state', true);
            cache()->expire('push_stone_state', 60);
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => '你站在石前，双掌发力推动巨石，只听得巨石吱吱连声，缓缓向后移去，现出一道门户来。',
            ])));
        }
        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => 'Ngươi thử đẩy đẩy cự thạch, cự thạch không chút sứt mẻ, chỉ phải từ bỏ, xem ra sức của một người chung quy là không được, vẫn là đến nhiều tìm mấy cái giúp đỡ tới.',
        ])));
    }


    /**
     * 躺进棺材
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function lie(TcpConnection $connection, Request $request)
    {
        $messages = [
            'Ngươi nằm tiến quan tài trung, đem quan bản khép lại, tức khắc một mảnh hắc ám, tựa hồ cùng hồng trần ngăn cách, sờ soạng trung ngươi phát giác quan bản vách trong đề có chữ viết,' .
            'Tinh tế sờ tới giống như một đầu viết “Ngọc nữ tâm kinh, kỹ áp Toàn Chân. Trùng dương cả đời, không thua cùng người” mười sáu cái chữ to,' .
            'Một khác đầu hình như là chút đồ hình loại ký hiệu. Bất quá chữ viết đã bị người cố ý cạo. Đột nhiên, sờ đến quan giác thượng dường như có một khối nhô lên cơ quan.',
            'Ngươi tay cầm cơ quan, nhẹ nhàng xuống phía dưới vặn vẹo, đột nhiên quan đế chi chi rung động, nứt ra rồi một cái động lớn, ngươi hướng trong động rơi xuống.',
        ];
        cache()->rPush('role_messages_' . $request->roleId, ...$messages);
        return (new IndexController())->delivery($connection, $request, 82);
    }


    /**
     * 睡寒玉床
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function sleep(TcpConnection $connection, Request $request)
    {
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->hp < 100) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi nằm thượng hàn giường ngọc, chỉ cảm thấy lạnh băng đến xương, “A” mà một tiếng chật vật bất kham mà nhảy dựng lên.',
            ])));
        }

        $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` = 3;
SQL;

        $role_skill = Helpers::queryFetchObject($sql);
        if (!is_object($role_skill)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $role_skill->row = Helpers::getSkillRowBySkillId(3);
        if ($role_skill->lv < 60) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi nằm thượng hàn giường ngọc, chỉ cảm thấy lạnh băng đến xương, “A” mà một tiếng chật vật bất kham mà nhảy dựng lên.',
            ])));
        }
        if ($role_skill->lv >= 160) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi nằm thượng hàn giường ngọc, chỉ cảm thấy không có gì đặc biệt, không hề tác dụng.',
            ])));
        }

        /**
         * 减少气血
         *
         */
        $role_attrs->hp -= 100;
        $role_attrs->hp = $role_attrs->hp < 0 ? 0 : $role_attrs->hp;
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);

        /**
         * 提升技能等级
         *
         */
        $role_skill->is_base = true;
        $experience = $role_skill->experience;
        $lv = $role_skill->lv;
        $up_exp = Helpers::getSkillExp($role_skill);
        $experience += intval($up_exp / 1000);
        $sql = '';
        $upgraded = false;
        if ($experience >= $up_exp) {
            $experience -= $up_exp;
            $lv += 1;
            $role_skill->lv = $lv;
            $up_exp = Helpers::getSkillExp($role_skill);
            $upgraded = true;
        }
        $sql .= <<<SQL
UPDATE `role_skills` SET `experience` = $experience, `lv` = $lv WHERE `id` = $role_skill->id;
SQL;

        Helpers::execSql($sql);
        if ($upgraded) {
            FlushRoleAttrs::fromRoleSkillByRoleId($request->roleId);
        }
        return $connection->send(\cache_response($request, \view('Func/Mudi/upgrade.twig', [
            'request'    => $request,
            'role_skill' => $role_skill,
            'lv'         => $lv,
            'percent'    => sprintf('%.1f', $experience / $up_exp * 100),
        ])));
    }
}
