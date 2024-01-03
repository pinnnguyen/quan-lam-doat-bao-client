<?php

namespace App\Http\Controllers\User;

use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * Đăng ký
 *
 */
class RegController
{
    /**
     * @param \Workerman\Connection\TcpConnection $connection
     * @param \Workerman\Protocols\Http\Request   $request
     *
     * @return null|bool
     */
    public function tip(TcpConnection $connection, Request $request)
    {
        return $connection->send(\response(\view('User/Reg/tip.twig', [
            'title'   => 'Đăng ký',
            'request' => $request,
        ])));
    }


    /**
     * Đăng ký
     *
     * @param TcpConnection $connection
     * @param Request       $request
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
            return $connection->send(\response(\view('User/Reg/index.twig', [
                'title'   => 'Đăng ký',
                'request' => $request,
            ])));
        }

        /**
         * 检验 phone
         *
         */
        $phone = trim($request->post('phone'));
        if (empty($phone)) {
            return $connection->send(\response(\view('User/Reg/index.twig', [
                'title'   => 'Đăng ký',
                'request' => $request,
                'message' => 'Số điện thoại không được để trống',
            ])));
        }

        /**
         * 检验 username
         *
         */
        $username = trim($request->post('username'));
        if (empty($username)) {
            return $connection->send(\response(\view('User/Reg/index.twig', [
                'title'   => 'Đăng ký',
                'request' => $request,
                'message' => 'Tên người dùng không được để trống',
            ])));
        }

        /*$yanzhengma = trim($request->post('yanzhengma'));
        if (empty($yanzhengma)) {
            return $connection->send(\response(\view('User/Reg/index.twig', [
                'title'   => 'Đăng ký',
                'request' => $request,
                'message' => '验证码不能为空',
            ])));
        }*/
        /**
         * 检验 password
         *
         */
        $password = trim($request->post('password'));
        if (empty($password)) {
            return $connection->send(\response(\view('User/Reg/index.twig', [
                'title'   => 'Đăng ký',
                'request' => $request,
                'message' => 'Mật khẩu không thể để trống',
            ])));
        }

        /**
         * 检验 password_confirm
         *
         */
        $password_confirm = trim($request->post('password_confirm'));
        if (empty($password_confirm)) {
            return $connection->send(\response(\view('User/Reg/index.twig', [
                'title'   => 'Đăng ký',
                'request' => $request,
                'message' => 'Mật khẩu không thể để trống',
            ])));
        }

        /**
         * 检测用户名格式
         *
         */
        if (!preg_match('#^[\x{4e00}-\x{9fa5}\da-zA-Z]{6,16}$#u', $username)) {
            return $connection->send(\response(\view('User/Reg/index.twig', [
                'title'   => 'Đăng ký',
                'request' => $request,
                'message' => 'Định dạng tên người dùng không chính xác'. $username,
            ])));
        }

        /**
         * 检测密码格式
         *
         */
        if (!preg_match('#^[\da-zA-Z]{6,16}$#', $password)) {
            return $connection->send(\response(\view('User/Reg/index.twig', [
                'title'   => 'Đăng ký',
                'request' => $request,
                'message' => 'Định dạng mật khẩu không chính xác',
            ])));
        }

        /**
         * 检验手机号格式
         *
         */
        if (!preg_match('#^1\d{10}$#', $phone)) {
            return $connection->send(\response(\view('User/Reg/index.twig', [
                'title'   => 'Đăng ký',
                'request' => $request,
                'message' => 'Mật khẩu bắt đầu bằng 1 và theo sau 10 số(Tổng 11 số)',
            ])));
        }

        /**
         * 验证Xác nhận密码
         *
         */
        if ($password !== $password_confirm) {
            return $connection->send(\response(\view('User/Reg/index.twig', [
                'title'   => 'Đăng ký',
                'request' => $request,
                'message' => 'Xác nhận mật khẩu không khớp với mật khẩu',
            ])));
        }


        /**
         * 查询手机号是否存在
         *
         */
//        $sql = <<<SQL
//SELECT `id` FROM `users` WHERE `yanzhengma` = '$yanzhengma';
//SQL;

//        $result = Helpers::queryFetchObject($sql);

//        if ($result) {
//            return $connection->send(\response(\view('User/Reg/index.twig', [
//                'title'   => 'Đăng ký',
//                'request' => $request,
//                'message' => '手机号已经被Đăng ký',
//            ])));
//        }

        $sql = <<<SQL
SELECT `id` FROM `users` WHERE `phone_number` = '$phone';
SQL;

        $result = Helpers::queryFetchObject($sql);

        if ($result) {
            return $connection->send(\response(\view('User/Reg/index.twig', [
                'title'   => 'Đăng ký',
                'request' => $request,
                'message' => 'Số điện thoại di động đã được Đăng ký',
            ])));
        }

        /**
        /**
         * 查询用户名是否存在
         *
         */
        $sql = <<<SQL
SELECT `id` FROM `users` WHERE `user_name` = '$username';
SQL;

        $result = Helpers::queryFetchObject($sql);
        if ($result) {
            return $connection->send(\response(\view('User/Reg/index.twig', [
                'title'   => 'Đăng ký',
                'request' => $request,
                'message' => 'Tên đăng nhập đã được thay đổi bởi Đăng ký',
            ])));
        }

        /**
         * 创建 User
         *
         */
        $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]);
        $register_timestamp = time();
        $register_ip = ip2long($connection->getRemoteIp());
        $sql = <<<SQL
INSERT INTO `users` (`user_name`, `password_hash`, `phone_number`, `register_timestamp`, `register_ip`)
VALUES ('$username', '$password_hash', '$phone', $register_timestamp, $register_ip);
SQL;

        $_st = db()->prepare($sql);
        $result = $_st->execute();
        $_st->closeCursor();
        if (!$result) {
            return $connection->send(\response(\view('User/Reg/index.twig', [
                'title'   => 'Đăng ký',
                'request' => $request,
                'message' => 'Đăng ký không thành công, vui lòng liên hệ quản trị viên',
            ])));
        }

        /**
         * 查询 user
         *
         */
        $sql = <<<SQL
SELECT `id` FROM `users` WHERE `user_name` = '$username';
SQL;

        $user = Helpers::queryFetchObject($sql);
        if (!$user) {
            return $connection->send(\response(\view('User/Reg/index.twig', [
                'title'   => 'Đăng ký',
                'request' => $request,
                'message' => 'Đăng ký không thành công, vui lòng liên hệ quản trị viên',
            ])));
        }

        /**
         * 创建 Role
         *
         */
        $sql = <<<SQL
INSERT INTO `roles` (`user_id`) VALUES ($user->id);
SQL;

        $_st = db()->prepare($sql);
        $result = $_st->execute();
        $_st->closeCursor();

        if (!$result) {
            return $connection->send(\response(\view('User/Reg/index.twig', [
                'title'   => 'Đăng ký',
                'request' => $request,
                'message' => 'Đăng ký không thành công, vui lòng liên hệ quản trị viên',
            ])));
        }

        /**
         * 查询 role
         */
        $sql = <<<SQL
SELECT `id` FROM `roles` WHERE `user_id` = $user->id;
SQL;

        $role = Helpers::queryFetchObject($sql);
        if (!$role) {
            return $connection->send(\response(\view('User/Reg/index.twig', [
                'title'   => 'Đăng ký',
                'request' => $request,
                'message' => 'Đăng ký không thành công, vui lòng liên hệ quản trị viên',
            ])));
        }

        /**
         * 新用户初始化操作
         */

        /**
         * 六个基础技能
         */
        $sql = <<<SQL
INSERT INTO `role_skills` (`role_id`, `skill_id`, `lv`)
VALUES ($role->id, 1, 1), ($role->id, 2, 1), ($role->id, 3, 1),
($role->id, 4, 1), ($role->id, 5, 1), ($role->id, 6, 1);
SQL;

        $_st = db()->prepare($sql);
        $result = $_st->execute();
        $_st->closeCursor();

        if (!$result) {
            return $connection->send(\response(\view('User/Reg/index.twig', [
                'title'   => 'Đăng ký',
                'request' => $request,
                'message' => 'Đăng ký không thành công, vui lòng liên hệ quản trị viên',
            ])));
        }

        /**
         * 增加一百文钱
         */
        $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($role->id, 213, 100);
SQL;

        $_st = db()->prepare($sql);
        $result = $_st->execute();
        $_st->closeCursor();

        if (!$result) {
            return $connection->send(\response(\view('User/Reg/index.twig', [
                'title'   => 'Đăng ký',
                'request' => $request,
                'message' => 'Đăng ký không thành công, vui lòng liên hệ quản trị viên',
            ])));
        }

        return $connection->send(\response(\view('User/Reg/index.twig', [
            'title'   => 'Đăng ký',
            'request' => $request,
            'message' => 'Đăng ký thành công',
        ])));
    }
}
