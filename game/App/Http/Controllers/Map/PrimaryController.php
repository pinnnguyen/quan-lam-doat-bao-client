<?php

namespace App\Http\Controllers\Map;

use App\Libs\Helpers;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * Tân thủ hướng dân
 */
class PrimaryController
{
    /**
     * 取名字
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function name(TcpConnection $connection, Request $request)
    {
        if ($request->roleRow->name != '无名氏') {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi đã có tên, đừng tới quấy rối!',
            ])));
        }

        if (strtoupper($request->method()) != 'POST') {
            return $connection->send(\cache_response($request, \view('Map/Primary/name.twig', [
                'request' => $request,
            ])));
        }

        $name = trim($request->post('name'));
        /*if (!preg_match('#^[\x{4e00}-\x{9fa5}]{2,6}$#u', $name)) {
            return $connection->send(\cache_response($request, \view('Map/Primary/name.twig', [
                'request' => $request,
                'message' => 'Tên chỉ có thể vì 2~6 vị tiếng Trung tạo thành!',
            ])));
        }*/

        $words = ['Phản hồi', 'Phản hồi trò chơi', 'Trạng thái', 'Tuyển hạng', 'Nhìn quanh tứ phương', 'Bảo rương', 'Ta có thể làm cái gì', 'Phản hồi trang đầu', 'Quỷ hồn',];
        foreach ($words as $word) {
            if (mb_strpos($name, $word) !== false) {
                return $connection->send(\cache_response($request, \view('Map/Primary/name.twig', [
                    'request' => $request,
                    'message' => 'Tên không thể có chứa lẫn lộn văn tự!',
                ])));
            }
        }

        // 查询重复

        $sql = <<<SQL
SELECT `id` FROM `npcs` WHERE `name` = '$name';
SQL;

        $id = Helpers::queryFetchObject($sql);
        if ($id) {
            return $connection->send(\cache_response($request, \view('Map/Primary/name.twig', [
                'request' => $request,
                'message' => 'Tên cùng NPC lặp lại!',
            ])));
        }

        $sql = <<<SQL
SELECT `id` FROM `things` WHERE `name` = '$name';
SQL;

        $id = Helpers::queryFetchObject($sql);
        if ($id) {
            return $connection->send(\cache_response($request, \view('Map/Primary/name.twig', [
                'request' => $request,
                'message' => 'Tên cùng vật phẩm lặp lại!',
            ])));
        }

        $sql = <<<SQL
SELECT `id` FROM `maps` WHERE `name` = '$name';
SQL;

        $id = Helpers::queryFetchObject($sql);
        if ($id) {
            return $connection->send(\cache_response($request, \view('Map/Primary/name.twig', [
                'request' => $request,
                'message' => 'Tên cùng bản đồ lặp lại!',
            ])));
        }

        $sql = <<<SQL
SELECT `id` FROM `xinfas` WHERE `name` = '$name';
SQL;

        $id = Helpers::queryFetchObject($sql);
        if ($id) {
            return $connection->send(\cache_response($request, \view('Map/Primary/name.twig', [
                'request' => $request,
                'message' => 'Tên cùng tâm pháp lặp lại!',
            ])));
        }

        $sql = <<<SQL
SELECT `id` FROM `roles` WHERE `name` = '$name';
SQL;

        $id = Helpers::queryFetchObject($sql);
        if ($id) {
            return $connection->send(\cache_response($request, \view('Map/Primary/name.twig', [
                'request' => $request,
                'message' => 'Tên cùng mặt khác người chơi lặp lại!',
            ])));
        }

        // 取名
        $sql = <<<SQL
UPDATE `roles` SET `name` = '$name' WHERE `id` = $request->roleId;
SQL;

        Helpers::execSql($sql);


        // 更新
        $request->roleRow->name = $name;
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);

        return $connection->send(\cache_response($request, \view('Map/Primary/gender.twig', [
            'request' => $request,
        ])));
    }


    /**
     * 选择性别
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $gender
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function gender(TcpConnection $connection, Request $request, int $gender = 0)
    {
        if ($gender == 0) {
            return $connection->send(\cache_response($request, \view('Map/Primary/gender.twig', [
                'request' => $request,
            ])));
        } elseif ($gender == 1) {
            $gender = 'Nam';
            $appearance = 'Ngươi sinh đến lưng hùm vai gấu, cường tráng hữu lực, phấn chấn oai hùng.';
        } else {
            $gender = 'Nữ';
            $appearance = 'Ngươi sinh đến mặt mày như họa, da thịt thắng tuyết, thật có thể nói là bế nguyệt tu hoa.';
        }
        // 更改性别
        $sql = <<<SQL
UPDATE `roles` SET `gender` = '$gender', `appearance` = '$appearance' WHERE `id` = $request->roleId;
SQL;

        Helpers::execSql($sql);


        // 更新
        $request->roleRow->gender = $gender;
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);

        return $connection->send(\cache_response($request, \view('Base/message.twig', [
            'request' => $request,
            'message' => 'Ngươi thân phận tin tức đã sáng tạo!' . $request->roleRow->name . '！' . $request->roleRow->gender . '！',
        ])));
    }


    /**
     * Tân thủ hướng dẫn
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function help(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Map/Primary/help.twig', [
            'request' => $request,
        ])));
    }
}
