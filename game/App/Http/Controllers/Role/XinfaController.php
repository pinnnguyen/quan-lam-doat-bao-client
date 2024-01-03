<?php

namespace App\Http\Controllers\Role;

use App\Libs\Attrs\FlushRoleAttrs;
use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 心法
 */
class XinfaController
{
    /**
     * 心法列表页
     *
     * @param TcpConnection $connection
     * @param Request $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        /**
         * 获取未上架的心法
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `role_id` = $request->roleId AND `is_sell` = 0 LIMIT 10;
SQL;

        $role_xinfas = Helpers::queryFetchAll($sql);

        foreach ($role_xinfas as $role_xinfa) {
            $role_xinfa->row = Helpers::getXinfaRowByXinfaId($role_xinfa->xinfa_id);
            $role_xinfa->viewUrl = 'Role/Xinfa/view/' . $role_xinfa->id;
        }

        return $connection->send(\cache_response($request, \view('Role/Xinfa/index.twig', [
            'request' => $request,
            'role_xinfas' => $role_xinfas,
        ])));
    }


    public ?string $message = null;


    /**
     * Tâm Pháp
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function view(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        /**
         * 查询心法
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        $role_xinfa = Helpers::queryFetchObject($sql);

        $role_xinfa->row = Helpers::getXinfaRowByXinfaId($role_xinfa->xinfa_id);

        $description = mb_substr($role_xinfa->row->description, 0, 50);

        if (mb_strlen($role_xinfa->row->description) > 50) {
            $displayDescription = true;
        } else {
            $displayDescription = false;
        }

        if ($role_xinfa->row->skill_id) {
            $role_xinfa->skill = Helpers::getSkillRowBySkillId($role_xinfa->row->skill_id);
        }
        if ($role_xinfa->row->sect_id) {
            $role_xinfa->sect = Helpers::getSect($role_xinfa->row->sect_id);
        } else {
            $role_xinfa->sect = 'Bình thường bá tánh';
        }

        $role_xinfa->need_experience = $role_xinfa->lv * $role_xinfa->lv * $role_xinfa->base_experience;

        return $connection->send(\cache_response($request, \view('Role/Xinfa/view.twig', [
            'request' => $request,
            'role_xinfa' => $role_xinfa,
            'putOnUrl' => 'Role/Xinfa/putOn/' . $role_xinfa_id,
            'practiceUrl' => 'Role/Xinfa/practice/' . $role_xinfa_id,
            'unPutOnUrl' => 'Role/Xinfa/unPutOn/' . $role_xinfa_id,
            'unPracticeUrl' => 'Role/Xinfa/unPractice/' . $role_xinfa_id,
            'throwQuestionUrl' => 'Role/Xinfa/throwQuestion/' . $role_xinfa_id,
            'privateQuestionUrl' => 'Role/Xinfa/privateQuestion/' . $role_xinfa_id,
            'description' => $description,
            'displayDescription' => $displayDescription,
            'descriptionUrl' => 'Role/Xinfa/description/' . $role_xinfa_id,
            'message' => $this->message ?? null,
        ])));
    }


    /**
     * 装配心法
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function putOn(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        /**
         * 查询心法
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        $role_xinfa = Helpers::queryFetchObject($sql);

        if ($role_xinfa->role_id != $request->roleId) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }

        $role_xinfa->row = Helpers::getXinfaRowByXinfaId($role_xinfa->xinfa_id);

        /**
         * 检测门派
         *
         */
        if ($role_xinfa->row->sect_id != 0 and $role_xinfa->row->sect_id != $request->roleRow->sect_id) {
            return $connection->send(\cache_response($request, \view('Role/Xinfa/message.twig', [
                'request' => $request,
                'message' => 'Ngươi không thể lắp ráp ' . Helpers::getSect($role_xinfa->row->sect_id) . ' Tâm pháp!',
            ])));
        }

        /**
         * 检测Tu vi
         *
         */
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->experience / 1000 < $role_xinfa->row->experience or $role_attrs->experience / 1000 < pow($role_xinfa->lv / 20, 3)) {
            return $connection->send(\cache_response($request, \view('Role/Xinfa/message.twig', [
                'request' => $request,
                'message' => 'Ngươi tu vi còn chưa đủ lắp ráp ' . $role_xinfa->row->name . ',Nỗ lực tăng lên chính mình đi! ',
            ])));
        }

        /**
         * 检测技能等级
         *
         */
        if ($role_xinfa->row->skill_id > 0 and $role_xinfa->row->skill_lv > 0) {
            /**
             * 查询技能
             *
             */
            $sql = <<<SQL
SELECT `id` FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` = {$role_xinfa->row->skill_id} AND `lv` >= {$role_xinfa->row->skill_lv};
SQL;

            $role_skill = Helpers::queryFetchObject($sql);
            if (empty($role_skill)) {
                $skill = Helpers::getSkillRowBySkillId($role_xinfa->row->skill_id);
                return $connection->send(\cache_response($request, \view('Role/Xinfa/message.twig', [
                    'request' => $request,
                    'message' => 'Lắp ráp ' . $role_xinfa->row->name . ' Yêu cầu ' . $role_xinfa->row->skill_lv . ' Cấp ' . $skill->name . ' ,Ngươi chưa đạt tới yêu cầu,Nỗ lực tăng lên chính mình đi! ',
                ])));
            }
        }

        /**
         * 是否已有其他同类心法正在装配
         *
         */
        $sql = <<<SQL
SELECT `role_xinfas`.`id`, `xinfas`.`name` FROM `role_xinfas` INNER JOIN `xinfas` ON `role_xinfas`.`xinfa_id` = `xinfas`.`id` AND `xinfas`.`kind` = '{$role_xinfa->row->kind}' WHERE `role_id` = $request->roleId AND `equipped` = 1;
SQL;

        $role_equipped_xinfa = Helpers::queryFetchObject($sql);
        if ($role_equipped_xinfa) {
            $sql = <<<SQL
UPDATE `role_xinfas` SET `equipped` = 0 WHERE `id` = $role_equipped_xinfa->id;
SQL;

        } else {
            $sql = '';
        }
        $sql .= <<<SQL
UPDATE `role_xinfas` SET `equipped` = 1 WHERE `id` = $role_xinfa->id;
SQL;


        Helpers::execSql($sql);

        FlushRoleAttrs::fromRoleXinfaByRoleId($request->roleId);
        return $this->view($connection, $request, $role_xinfa_id);
    }


    /**
     * 修炼心法
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function practice(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        /**
         * 查询
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        $role_xinfa = Helpers::queryFetchObject($sql);
        if ($role_xinfa->role_id != $request->roleId) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $role_xinfa->row = Helpers::getXinfaRowByXinfaId($role_xinfa->xinfa_id);
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->experience / 1000 < $role_xinfa->row->experience or $role_attrs->experience / 1000 < $role_xinfa->lv * $role_xinfa->lv / 10) {
            return $connection->send(\cache_response($request, \view('Role/Xinfa/message.twig', [
                'request' => $request,
                'message' => 'Ngươi tu vi còn chưa đủ tu luyện' . $role_xinfa->row->name . ',Nỗ lực tăng lên chính mình đi! ',
            ])));
        }

        /**
         * 是否有其他正在修炼
         *
         */
        $sql = <<<SQL
SELECT `id`, `xinfa_id` FROM `role_xinfas` WHERE `role_id` = $request->roleId AND `practiced` = 1;
SQL;

        $role_practiced_xinfa = Helpers::queryFetchObject($sql);
        if ($role_practiced_xinfa) {
            $sql = <<<SQL
UPDATE `role_xinfas` SET `practiced` = 0 WHERE `id` = $role_practiced_xinfa->id;
SQL;

        } else {
            $sql = '';
        }
        $sql .= <<<SQL
UPDATE `role_xinfas` SET `practiced` = 1 WHERE `id` = $role_xinfa->id;
SQL;


        Helpers::execSql($sql);

        return $this->view($connection, $request, $role_xinfa_id);
    }


    /**
     * 卸下心法
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function unPutOn(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        $role_xinfa = Helpers::queryFetchObject($sql);
        if ($role_xinfa->role_id != $request->roleId) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }

        $sql = <<<SQL
UPDATE `role_xinfas` SET `equipped` = 0 WHERE `id` = $role_xinfa_id;
SQL;


        Helpers::execSql($sql);

        FlushRoleAttrs::fromRoleXinfaByRoleId($request->roleId);
        return $this->view($connection, $request, $role_xinfa_id);
    }


    /**
     * 修炼心法
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function unPractice(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        $role_xinfa = Helpers::queryFetchObject($sql);
        if ($role_xinfa->role_id != $request->roleId) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $sql = <<<SQL
UPDATE `role_xinfas` SET `practiced` = 0 WHERE `id` = $role_xinfa_id;
SQL;


        Helpers::execSql($sql);

        return $this->view($connection, $request, $role_xinfa_id);
    }


    /**
     * Xem xét描述
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function description(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        /**
         * 查询
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        $role_xinfa = Helpers::queryFetchObject($sql);

        $role_xinfa->row = Helpers::getXinfaRowByXinfaId($role_xinfa->xinfa_id);
        return $connection->send(\cache_response($request, \view('Role/Xinfa/description.twig', [
            'request' => $request,
            'role_xinfa' => $role_xinfa,
            'backUrl' => 'Role/Xinfa/view/' . $role_xinfa_id,
        ])));
    }


    /**
     * 丢弃询问
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function throwQuestion(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        /**
         * 查询
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        $role_xinfa = Helpers::queryFetchObject($sql);

        $role_xinfa->row = Helpers::getXinfaRowByXinfaId($role_xinfa->xinfa_id);


        return $connection->send(\cache_response($request, \view('Role/Xinfa/throwQuestion.twig', [
            'request' => $request,
            'role_xinfa' => $role_xinfa,
            'throwUrl' => 'Role/Xinfa/throw/' . $role_xinfa_id,
            'backUrl' => 'Role/Xinfa/view/' . $role_xinfa_id,
        ])));
    }


    /**
     * 私有化询问
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function privateQuestion(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        /**
         * 查询
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        $role_xinfa = Helpers::queryFetchObject($sql);

        if (!empty($role_xinfa->private_name)) {
            return $connection->send(\cache_response($request, \view('Role/Xinfa/message.twig', [
                'request' => $request,
                'message' => 'Mỗi bản tâm pháp chỉ có thể tư hữu hóa một lần.',
            ])));
        }

        $role_xinfa->row = Helpers::getXinfaRowByXinfaId($role_xinfa->xinfa_id);
        return $connection->send(\cache_response($request, \view('Role/Xinfa/privateQuestion.twig', [
            'request' => $request,
            'role_xinfa' => $role_xinfa,
            'privateUrl' => 'Role/Xinfa/private/' . $role_xinfa_id,
            'backUrl' => 'Role/Xinfa/view/' . $role_xinfa_id,
        ])));
    }


    /**
     * 丢弃Xác nhận
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function throw(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        /**
         * 查询
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        $role_xinfa = Helpers::queryFetchObject($sql);

        if ($role_xinfa->role_id != $request->roleId) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Đã lắp ráp, thỉnh trước dỡ xuống!',
            ])));
        }

        $role_xinfa->row = Helpers::getXinfaRowByXinfaId($role_xinfa->xinfa_id);

        if ($role_xinfa->equipped) {
            $this->message = $role_xinfa->row->name . 'Đã lắp ráp, thỉnh trước dỡ xuống!';
            return $this->view($connection, $request, $role_xinfa_id);
        }
        if ($role_xinfa->practiced) {
            $this->message = $role_xinfa->row->name . 'Đang ở tu luyện, thỉnh trước đình tu!';
            return $this->view($connection, $request, $role_xinfa_id);
        }

        $map_xinfas = cache()->hGet('map_things_' . $request->roleRow->map_id, 'xinfas');

        if ($map_xinfas) {
            $map_xinfas = unserialize($map_xinfas);
        } else {
            $map_xinfas = [];
        }
        $map_xinfas[md5(microtime(true))] = [
            'expire' => time() + 300,
            'protect_role_id' => 0,
            'id' => $role_xinfa->id,
            'xinfa_id' => $role_xinfa->xinfa_id,
            'base_experience' => $role_xinfa->base_experience,
            'experience' => $role_xinfa->experience,
            'lv' => $role_xinfa->lv,
            'max_lv' => $role_xinfa->max_lv,
            'private_name' => $role_xinfa->private_name,
        ];
        cache()->hSet('map_things_' . $request->roleRow->map_id, 'xinfas', serialize($map_xinfas));

        /**
         * 删除玩家心法
         */
        $sql = <<<SQL
DELETE FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;


        Helpers::execSql($sql);


        return $connection->send(\cache_response($request, \view('Role/Xinfa/throw.twig', [
            'request' => $request,
            'message' => 'Ngươi đem ' . $role_xinfa->row->name . ' Ném xuống đất!',
        ])));
    }


    /**
     * 私有化
     *
     * @param TcpConnection $connection
     * @param Request $request
     * @param int $role_xinfa_id
     *
     * @return bool|null
     */
    public function private(TcpConnection $connection, Request $request, int $role_xinfa_id)
    {
        /**
         * 查询
         *
         */
        $sql = <<<SQL
SELECT * FROM `role_xinfas` WHERE `id` = $role_xinfa_id;
SQL;

        $role_xinfa = Helpers::queryFetchObject($sql);

        $role_xinfa->row = Helpers::getXinfaRowByXinfaId($role_xinfa->xinfa_id);

        if ($role_xinfa->role_id != $request->roleId) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi đã đem ' . $role_xinfa->row->name . 'Tư hữu hóa.',
            ])));
        }

        /**
         * 修改玩家心法
         */
        $sql = <<<SQL
UPDATE `role_xinfas` SET `private_name` = '{$request->roleRow->name}' WHERE `id` = $role_xinfa_id;
SQL;


        Helpers::execSql($sql);


        return $connection->send(\cache_response($request, \view('Role/Xinfa/message.twig', [
            'request' => $request,
            'message' => 'Ngươi đã đem ' . $role_xinfa->row->name . 'Tư hữu hóa.',
        ])));
    }
}
