<?php

namespace App\Http\Controllers\Role;

use App\Core\Configs\GameConfig;
use App\Libs\Helpers;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 聊天系统
 */
class ChatController
{
    private ?string $message = null;


    /**
     * 聊天首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function index(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Role/Chat/index.twig', [
            'request' => $request,
        ])));
    }


    /**
     * Kênh Cộng Đồng
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $page
     *
     * @return bool|null
     */
    public function public(TcpConnection $connection, Request $request, int $page = 1)
    {
        if ($page < 1) $page = 1;
        if ($page > 5) $page = 5;
        $offset = ($page - 1) * 20;
        $sql = <<<SQL
SELECT chat_logs.id,`roles`.`name`, `chat_logs`.`sender_id`, `chat_logs`.`timestamp`, `chat_logs`.`content` FROM `chat_logs` INNER JOIN `roles` ON `chat_logs`.`sender_id` = `roles`.`id` WHERE `chat_logs`.`kind` = 1 ORDER BY `chat_logs`.`id` DESC LIMIT $offset, 20;
SQL;

        $chat_logs = Helpers::queryFetchAll($sql);

        foreach ($chat_logs as $chat_log) {
            $chat_log->date_time = date('H:i', $chat_log->timestamp);
            $chat_log->sender = $chat_log->name;
            $sender = Helpers::getRoleRowByRoleId($chat_log->sender_id);
            if (!empty($sender)) {
                $chat_log->online = true;
                if ($request->roleId == $chat_log->sender_id) {
                    $chat_log->viewUrl = 'Role/Info/index';
                } else {
                    $chat_log->viewUrl = 'Map/Role/view/' . $chat_log->sender_id;
                }
            }
            $chat_log->deleteUrl = 'Role/Chat/delete/'.$chat_log->id;
        }

        //判断是否是管理员
        $arrManageId = GameConfig::MANAGERS;
        $isManage = false;
        if (in_array($request->roleId,$arrManageId)){
            $isManage = true;
        }

        return $connection->send(\cache_response($request, \view('Role/Chat/public.twig', [
            'request'   => $request,
            'chat_logs' => $chat_logs,
            'message'   => $this->message,
            'last_page' => 'Role/Chat/public/' . ($page - 1),
            'next_page' => 'Role/Chat/public/' . ($page + 1),
            'isManage' => $isManage
        ])));
    }


    /**
     * 发送公共聊天
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function publicSend(TcpConnection $connection, Request $request)
    {
        //查询是否禁言
        $sql = "select count(*) as roleCount from chat_banned where role_id = {$request->roleId}";
        $role = Helpers::queryFetchObject($sql);
        if (!empty($role->roleCount)){
            $this->message = 'Tài khoản này đã bị cấm!';
            return $this->public($connection, $request);
        }
        if ($request->roleId == 880){
            $this->message = 'Tài khoản này đã bị cấm!';
            return $this->public($connection, $request);
        }
        if (strtoupper($request->method() != 'POST')) {
            $this->message = 'Nội dung trò chuyện không được để trống!';
            return $this->public($connection, $request);
        }
        $content = $request->post('content');
        $content = trim($content);
        if (!preg_match('#^[\x{4e00}-\x{9fa5}，！。、；《》【】‘’：“”（）？+…—\da-zA-Z]{2,64}$#u', $content)) {
            $this->message = 'Nội dung trò chuyện không được chứa các ký tự đặc biệt! Chỉ cho phép ký tự tiếng Trung, dấu câu tiếng Trung, chữ cái và số và độ dài là 2~64。';
            return $this->public($connection, $request);
        }
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->experience / 1000 < 100) {
            $this->message = 'Những người có tu vi dưới 100 năm không thể nói chuyện trên các kênh trò chuyện công khai!';
            return $this->public($connection, $request);
        }

        $sql = <<<SQL
SELECT * FROM `chat_logs` WHERE `kind` = 1 AND `sender_id` = $request->roleId ORDER BY `id` DESC LIMIT 1;
SQL;

        $chat_log = Helpers::queryFetchObject($sql);

        if ($chat_log and $chat_log->timestamp + 30 > time()) {
            $this->message = 'Bạn không thể nói lại trong kênh trò chuyện công khai trong vòng ba mươi giây!';
            return $this->public($connection, $request);
        }

        $timestamp = time();

        $sql = <<<SQL
INSERT INTO `chat_logs` (`sender_id`, `content`, `timestamp`, `kind`) VALUES ($request->roleId, '$content', $timestamp, 1);
SQL;


        Helpers::execSql($sql);
        /**
         * 获取在线
         */
        $roles = cache()->keys('role_row_*');
        if (!empty($roles)) {
            $roles = cache()->mget($roles);
            if (!empty($roles)) {
                $cont = [
                    'kind'    => 1,
                    'content' => $content,
                    'name'    => $request->roleRow->name,
                    'url'     => 'Map/Role/view/' . $request->roleId,
                ];
                $broadcast = cache()->pipeline();
                foreach ($roles as $role) {
                    if (!empty($role)) {
                        if ($role->switch_public) {
                            if ($role->id != $request->roleId) {
                                $broadcast->rPush('role_broadcast_' . $role->id, $cont);
                            }
                        }
                    }
                }
                $broadcast->exec();
            }
        }
        return $this->public($connection, $request);
    }


    /**
     * Kênh Đồn Đại
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $page
     *
     * @return bool|null
     */
    public function rumour(TcpConnection $connection, Request $request, int $page = 1)
    {
        if ($page < 1) $page = 1;
        if ($page > 5) $page = 5;
        $offset = ($page - 1) * 20;
        $sql = <<<SQL
SELECT `chat_logs`.`timestamp`, `chat_logs`.`content` FROM `chat_logs` WHERE `chat_logs`.`kind` = 3 ORDER BY `chat_logs`.`id` DESC LIMIT $offset, 20;
SQL;

        $chat_logs = Helpers::queryFetchAll($sql);
        foreach ($chat_logs as $chat_log) {
            $chat_log->date_time = date('H:i', $chat_log->timestamp);
        }

        return $connection->send(\cache_response($request, \view('Role/Chat/rumour.twig', [
            'request'   => $request,
            'chat_logs' => $chat_logs,
            'last_page' => 'Role/Chat/rumour/' . ($page - 1),
            'next_page' => 'Role/Chat/rumour/' . ($page + 1),
        ])));
    }


    /**
     * Kênh Giang Hồ
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $page
     *
     * @return bool|null
     */
    public function jianghu(TcpConnection $connection, Request $request, int $page = 1)
    {
        if ($page < 1) $page = 1;
        if ($page > 5) $page = 5;
        $offset = ($page - 1) * 20;
        $sql = <<<SQL
SELECT `chat_logs`.`timestamp`, `chat_logs`.`content` FROM `chat_logs` WHERE `chat_logs`.`kind` = 4 ORDER BY `chat_logs`.`id` DESC LIMIT $offset, 20;
SQL;

        $chat_logs = Helpers::queryFetchAll($sql);
        foreach ($chat_logs as $chat_log) {
            $chat_log->date_time = date('H:i', $chat_log->timestamp);
        }

        return $connection->send(\cache_response($request, \view('Role/Chat/jianghu.twig', [
            'request'   => $request,
            'chat_logs' => $chat_logs,
            'last_page' => 'Role/Chat/jianghu/' . ($page - 1),
            'next_page' => 'Role/Chat/jianghu/' . ($page + 1),
        ])));
    }


    /**
     * Kênh Hệ Thống
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $page
     *
     * @return bool|null
     */
    public function system(TcpConnection $connection, Request $request, int $page = 1)
    {
        if ($page < 1) $page = 1;
        if ($page > 5) $page = 5;
        $offset = ($page - 1) * 20;
        $sql = <<<SQL
SELECT `chat_logs`.`timestamp`, `chat_logs`.`content` FROM `chat_logs` WHERE `chat_logs`.`kind` = 5 ORDER BY `chat_logs`.`id` DESC LIMIT $offset, 20;
SQL;

        $chat_logs = Helpers::queryFetchAll($sql);
        foreach ($chat_logs as $chat_log) {
            $chat_log->date_time = date('H:i', $chat_log->timestamp);
        }

        return $connection->send(\cache_response($request, \view('Role/Chat/system.twig', [
            'request'   => $request,
            'chat_logs' => $chat_logs,
            'last_page' => 'Role/Chat/system/' . ($page - 1),
            'next_page' => 'Role/Chat/system/' . ($page + 1),
        ])));
    }


    /**
     * 聊天历史记录
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $page
     *
     * @return bool|null
     */
    public function history(TcpConnection $connection, Request $request, int $page = 1)
    {
        if ($page < 1) $page = 1;
        if ($page > 5) $page = 5;
        $offset = ($page - 1) * 20;
        $sql = <<<SQL
SELECT `roles`.`name`, `chat_logs`.`sender_id`, `chat_logs`.`timestamp`, `chat_logs`.`content` FROM `chat_logs` INNER JOIN `roles` ON `chat_logs`.`sender_id` = `roles`.`id` WHERE `chat_logs`.`kind` = 1 ORDER BY `chat_logs`.`id` DESC LIMIT $offset, 20;
SQL;

        $chat_logs = Helpers::queryFetchAll($sql);

        foreach ($chat_logs as $chat_log) {
            $chat_log->date_time = date('H:i', $chat_log->timestamp);
            $chat_log->sender = $chat_log->name;
            $sender = Helpers::getRoleRowByRoleId($chat_log->sender_id);
            if (!empty($sender)) {
                $chat_log->online = true;
                if ($request->roleId == $chat_log->sender_id) {
                    $chat_log->viewUrl = 'Role/Info/index';
                } else {
                    $chat_log->viewUrl = 'Map/Role/view/' . $chat_log->sender_id;
                }
            }
        }

        return $connection->send(\cache_response($request, \view('Role/Chat/public.twig', [
            'request'   => $request,
            'chat_logs' => $chat_logs,
            'message'   => $this->message,
            'last_page' => 'Role/Chat/public/' . ($page - 1),
            'next_page' => 'Role/Chat/public/' . ($page + 1),
        ])));
    }
//    public function history(TcpConnection $connection, Request $request, int $page = 1)
//    {
//        if ($page < 1) $page = 1;
//        if ($page > 5) $page = 5;
//        $offset = ($page - 1) * 20;
//        $sql = <<<SQL
//SELECT `timestamp`, `content`, `receiver_id`, `sender_id` FROM `chat_logs`
//WHERE `chat_logs`.`kind` = 6 AND (`sender_id` = $request->roleId OR `receiver_id` = $request->roleId) ORDER BY `chat_logs`.`id` DESC LIMIT $offset, 20;
//SQL;
//
//        $chat_logs_st = db()->query($sql);
//        $chat_logs = $chat_logs_st->fetchAll(\PDO::FETCH_OBJ);
//        $chat_logs_st->closeCursor();
//        if (empty($request->roleRow->follows)) {
//            $follows = [];
//        } else {
//            $follows = json_decode($request->roleRow->follows);
//        }
//        foreach ($chat_logs as $chat_log) {
//            $chat_log->date_time = date('H:i', $chat_log->timestamp);
//            if ($chat_log->receiver_id == $request->roleId) {
//                if (in_array($chat_log->sender_id, $follows)) {
//                    $chat_log->kind = 1;
//                } else {
//                    $chat_log->kind = 3;
//                }
//            } else {
//                if (in_array($chat_log->receiver_id, $follows)) {
//                    $chat_log->kind = 2;
//                } else {
//                    $chat_log->kind = 4;
//                }
//            }
//            $sender = Helpers::getRoleRowByRoleId($chat_log->sender_id);
//            if (!empty($sender)) {
//                $chat_log->online = true;
//                if ($request->roleId == $chat_log->sender_id) {
//                    $chat_log->viewUrl = 'Role/Info/index';
//                } else {
//                    $chat_log->viewUrl = 'Map/Role/view/' . $chat_log->sender_id;
//                }
//            }
//        }
//
//        return $connection->send(\cache_response($request, \view('Role/Chat/system.twig', [
//            'request'   => $request,
//            'chat_logs' => $chat_logs,
//            'last_page' => 'Role/Chat/history/' . ($page - 1),
//            'next_page' => 'Role/Chat/history/' . ($page + 1),
//        ])));
//    }

    public function delete(TcpConnection $connection,Request $request,int $chatLogId){
        $sql = "delete from chat_logs where id = {$chatLogId}";
        Helpers::execSql($sql);
        $this->message = "Đã xóa thành công";
        return $connection->send(\cache_response($request, \view('Role/Chat/delete.twig', [
            'request'   => $request,
            'message'   => $this->message
        ])));
    }
}
