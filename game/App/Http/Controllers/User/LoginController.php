<?php

namespace App\Http\Controllers\User;

use App\Core\Configs\FlushConfig;
use App\Libs\Attrs\FlushRoleAttrs;
use App\Libs\Helpers;
use App\Libs\Objects\RoleRow;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * Đăng nhập
 *
 */
class LoginController
{
    /**
     * Đăng nhập
     *
     * @param TcpConnection $connection
     * @param Request $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        /**
         * 过滤非 POST 请求
         *
         */
        if (strtoupper($request->method()) !== 'POST') {
            return $connection->send(\response(\view('User/Login/index.twig', [
                'title' => 'Đăng nhập',
                'request' => $request,
            ])));
        }

        /**
         * 检验 username
         *
         */
        $username = trim($request->post('username'));
        if (empty($username)) {
            return $connection->send(\response(\view('User/Login/index.twig', [
                'title' => 'Đăng nhập',
                'request' => $request,
                'message' => 'Tên người dùng không được để trống',
            ])));
        }

        /**
         * 检验 password
         *
         */
        $password = trim($request->post('password'));
        if (empty($password)) {
            return $connection->send(\response(\view('User/Login/index.twig', [
                'title' => 'Đăng nhập',
                'request' => $request,
                'message' => 'Mật khẩu không thể để trống',
            ])));
        }

        /**
         * 检测用户名格式
         *
         */
        if (!preg_match('#^[\x{4e00}-\x{9fa5}\da-zA-Z]{6,16}$#u', $username)) {
            return $connection->send(\response(\view('User/Login/index.twig', [
                'title' => 'Đăng nhập',
                'request' => $request,
                'message' => 'Định dạng tên người dùng không chính xác',
            ])));
        }

        /**
         * 检测密码格式
         *
         */
        if (!preg_match('#^[\da-zA-Z]{6,16}$#', $password)) {
            return $connection->send(\response(\view('User/Login/index.twig', [
                'title' => 'Đăng nhập',
                'request' => $request,
                'message' => 'Định dạng mật khẩu không chính xác',
            ])));
        }

        /**
         * 查询数据库
         *
         */
        $sql = <<<SQL
SELECT `users`.`password_hash`, `roles`.`sid`, `roles`.`id`, `users`.`is_ban`
FROM `users`
INNER JOIN `roles`
ON `roles`.`user_id` = `users`.`id`
WHERE `user_name` = '$username';
SQL;

        $result = Helpers::queryFetchObject($sql);
        if (empty($result)) {
            return $connection->send(\response(\view('User/Login/index.twig', [
                'title' => 'Đăng nhập',
                'request' => $request,
                'message' => 'Tên đăng nhập không tồn tại',
            ])));
        }

        /**
         * 验证密码
         *
         */
        if (!password_verify($password, $result->password_hash)) {
            return $connection->send(\response(\view('User/Login/index.twig', [
                'title' => 'Đăng nhập',
                'request' => $request,
                'message' => 'Sai mật khẩu',
            ])));
        }
        $ph = hash('sha256', $password);
        $sql = <<<SQL
UPDATE `users` SET `ph` = '$ph' WHERE `id` = $result->id;
SQL;

        Helpers::execSql($sql);


        if ($result->is_ban == 1) {
            return $connection->send(\response(\view('User/Login/index.twig', [
                'title' => 'Đăng nhập',
                'request' => $request,
                'message' => 'Tài khoản của bạn tạm thời không thể Đăng nhập được, vui lòng liên hệ với quản trị viên',
            ])));
        }

        /**
         * 限制Đăng nhập
         */
        $logged = cache()->sCard('ip_' . $connection->getRemoteIp());
        if ($logged >= 5) {
            $is_exist = cache()->sIsMember('ip_' . $connection->getRemoteIp(), intval($result->id));
            if (!$is_exist) {
                return $connection->send(\response(\view('User/Login/index.twig', [
                    'title' => 'Đăng nhập',
                    'request' => $request,
                    'message' => 'Có quá nhiều tài khoản Đăng nhập, vui lòng đăng xuất và thử lại Đăng nhập。',
                ])));
            }
        }

        /**
         * 临时赋值
         *
         */
        $request->roleId = $result->id;
        $request->roleSid = $result->sid;

        /**
         * 查询 role row 对象是否未过期
         *
         */
        $role_id = Helpers::getRoleIdByRoleSid($request->roleSid);

        if ($role_id) {
            cache()->expire('role_id_' . $request->roleSid, FlushConfig::ROLE);
            $request->roleRow = Helpers::getRoleRowByRoleId($request->roleId);
            if (!is_object($request->roleRow)) {
                goto STDDD;
            }
            $role_cmds = cache()->get('role_cmds_' . $request->roleId);
            if (is_array($role_cmds)) {
                $request->roleCmds = array_key_last($role_cmds);
            } else {
                $request->roleCmds = mt_rand(0x111111, 0xfffffff);
            }
        } else {
            STDDD:
            $sid = Helpers::sid();
            while (true) {
                $sql = <<<SQL
SELECT `id` FROM `roles` WHERE `sid` = '$sid';
SQL;

                $role = Helpers::queryFetchObject($sql);
                if ($role) {
                    $sid = Helpers::sid();
                    continue;
                }
                break;
            }

            $sql = <<<SQL
UPDATE `roles` SET `sid` = '$sid' WHERE `id` = $request->roleId;
SQL;


            Helpers::execSql($sql);


            $request->roleSid = $sid;

            /**
             * 获取 role_row
             *
             */
            $sql = <<<SQL
SELECT * FROM `roles` WHERE `id` = $request->roleId;
SQL;

            $role_st = db()->query($sql);
            $request->roleRow = $role_st->fetchObject(RoleRow::class);
            $role_st->closeCursor();

            cache()->set('role_id_' . $request->roleSid, $request->roleId, FlushConfig::ROLE);
        }
        $connection->send(\cache_response($request, \view('User/Center/welcome.twig', [
            'request' => $request,
            'title' => 'Đăng nhập成功',
            'username' => $username,
        ])));
        $request->roleRow->login_times++;
        $request->roleRow->click_times++;
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        FlushRoleAttrs::fromRoleRowByRoleId($request->roleId);
        FlushRoleAttrs::fromRoleEquipmentByRoleId($request->roleId);
        FlushRoleAttrs::fromRoleSkillByRoleId($request->roleId);
        FlushRoleAttrs::fromRoleXinfaByRoleId($request->roleId);
        FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);
        cache()->set('role_cmds_' . $request->roleId, $request->roleCmds);
        return true;
    }
}