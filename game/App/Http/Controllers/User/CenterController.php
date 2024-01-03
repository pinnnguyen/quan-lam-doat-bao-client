<?php

namespace App\Http\Controllers\User;

use App\Core\Configs\GameConfig;
use App\Libs\Events\Timers\ClearOfflineRoleTimer;
use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 个人中心
 */
class CenterController
{
    /**
     * 首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        /**
         * 从地图消失
         *
         */
        cache()->sRem('map_roles_' . $request->roleRow->map_id, $request->roleId);
        
        if (in_array($request->roleId, GameConfig::MANAGERS)) {
            $is_manager = true;
        } else {
            $is_manager = false;
        }

        return $connection->send(\cache_response($request, \view('User/Center/index.twig', [
            'title'        => 'Cá nhân trung tâm',
            'request'      => $request,
            'enterGameUrl' => 'Map/Index/delivery/' . $request->roleRow->map_id,
            'is_manager'   => $is_manager,
        ])));
         $pdo = new PDO(DBConfig::DRIVER .
     ':dbname=' . DBConfig::DATABASE .
     ';unix_socket=/tmp/mysql.sock' .
     ';charset=' . DBConfig::CHARSET,
     DBConfig::USERNAME,
     DBConfig::PASSWORD,
     [PDO::ATTR_PERSISTENT => true,]);
 $ip_names = cache()->keys('role_ip_*');
 $roles = [];
 foreach ($ip_names as $ip_name) {
     $role_id = substr($ip_name, 8);
     $ip = cache()->get($ip_name);
     $role = cache()->get('role_row_' . $role_id);
     if ($role) {
        if (empty($roles[$ip])) {
             $roles[$ip][] = $ip;
         }
         $user = $pdo->query("SELECT user_name,phone_number FROM users WHERE id = $role_id LIMIT 1;")->fetchObject();
         $roles[$ip][$role->id] = $role->name . '(' . $user->user_name . ', ' . $user->phone_number . ')';
     }
 }
 $result = var_export($roles, true);

 file_put_contents(__DIR__ . '/ip.txt', $result);
    }


    /**
     * Sửa chữa mật mã
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function alterPassword(TcpConnection $connection, Request $request)
    {
        /**
         * 过滤非 POST 请求
         *
         */
        if ($request->method() !== 'POST') {
            return $connection->send(\cache_response($request, \view('User/Center/alterPassword.twig', [
                'title'   => 'Sửa chữa mật mã',
                'request' => $request,
            ])));
        }

        /**
         * 检验 username 和 password
         *
         */
        $password = trim($request->post('password'));
        $password_confirm = trim($request->post('password_confirm'));
        if (empty($password) or empty($password_confirm)) {
            return $connection->send(\cache_response($request, \view('User/Center/alterPassword.twig', [
                'title'   => 'Sửa chữa mật mã',
                'request' => $request,
                'message' => 'Sở hữu hạng không thể vì không',
            ])));
        }

        /**
         * 检测密码格式
         *
         */
        if (!preg_match('#^[\da-zA-Z]{6,16}$#', $password)) {
            return $connection->send(\cache_response($request, \view('User/Center/alterPassword.twig', [
                'title'   => 'Sửa chữa mật mã',
                'request' => $request,
                'message' => 'Mật mã cách thức không chính xác',
            ])));
        }

        /**
         * 验证Xác nhận密码
         *
         */
        if ($password !== $password_confirm) {
            return $connection->send(\cache_response($request, \view('User/Center/alterPassword.twig', [
                'title'   => 'Sửa chữa mật mã',
                'request' => $request,
                'message' => 'Xác nhận tân mật mã cùng tân mật mã không nhất trí',
            ])));
        }

        /**
         * Sửa chữa mật mã
         *
         */
        $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]);
        $sql = <<<SQL
UPDATE `users` SET `password_hash` = '$password_hash' WHERE `id` = {$request->roleRow->user_id};
SQL;


        Helpers::execSql($sql);


        return $connection->send(\cache_response($request, \view('User/Center/alterPassword.twig', [
            'title'   => 'Sửa chữa mật mã',
            'request' => $request,
            'message' => 'Mật mã sửa đổi thành công',
        ])));
    }


    /**
     * 退出
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     *
     * @return bool|null
     */
    public function logout(TcpConnection $connection, Request $request)
    {
        /**
         * 删除 cmds & id
         *
         */
       $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);

        
          if ($role_attrs->reviveTimestamp > time()) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Quỷ hồn trạng thái tạm thời vô pháp rời khỏi.',
            ])));
        }
        else
        cache()->del('role_id_' . $request->roleSid);
        ClearOfflineRoleTimer::sync($request->roleRow);
        cache()->del('ip_' . $connection->getRemoteIp());
        return $connection->send(\cache_response($request, \view('User/Center/logout.twig', [
            'request' => $request,
        ])));
    }
}
