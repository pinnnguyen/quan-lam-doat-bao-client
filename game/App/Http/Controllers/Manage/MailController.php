<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Role\ShopController;
use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 发送邮件
 *
 */
class MailController
{
    /**
     * 道具
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function dj(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Manage/Mail/dj.twig', [
            'request'  => $request,
            'djs'      => ShopController::$djs,
            'post_url' => 'Manage/Mail/djPost',
        ])));
    }


    /**
     * 发送道具
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function djPost(TcpConnection $connection, Request $request)
    {
        if (strtoupper($request->method()) !== 'POST') {
            return $connection->send(\cache_response($request, \view('Manage/Index/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }


        $content = trim($request->post('content'));
        if (empty($content)) {
            $content = '';
        }

        $id = trim($request->post('id'));

        $number = trim($request->post('number'));
        if (empty($number)) {
            return $connection->send(\cache_response($request, \view('Manage/Index/message.twig', [
                'request' => $request,
                'message' => 'Số lượng không thể vì không',
            ])));
        }
        $number = intval($number);
        if ($number < 1 or $number > 99999999) {
            return $connection->send(\cache_response($request, \view('Manage/Index/message.twig', [
                'request' => $request,
                'message' => 'Số lượng phạm vi vì 1-99999999',
            ])));
        }


        /**
         * 查询玩家
         */
        $name = $request->post('name');
        $name = trim($name);
        if (empty($name)) {
            /**
             * 全服玩家
             *
             */
            $sql = <<<SQL
SELECT `id` FROM `roles`;
SQL;

        } else {
            /**
             * 查询玩家是否存在
             *
             */
            $sql = <<<SQL
SELECT `id` FROM `roles` WHERE `name` = '$name';
SQL;

        }
        $roles = Helpers::queryFetchAll($sql);
        if (!is_array($roles) or count($roles) < 1) {
            return $connection->send(\cache_response($request, \view('Manage/Index/message.twig', [
                'request' => $request,
                'message' => 'Người chơi【' . $name . '】Không tồn tại',
            ])));
        }
        $time = time();
        $sql = '';
        foreach ($roles as $role) {
            $sql .= <<<SQL
INSERT INTO `mails` (`receiver_id`, `content`, `kind`, `e_id`, `number`, `timestamp`) VALUES ($role->id, '$content', '道具', $id, $number, $time);
SQL;

        }
        Helpers::execSql($sql);
        return $connection->send(\cache_response($request, \view('Manage/Index/message.twig', [
            'request' => $request,
            'message' => 'Thư đã được gửi',
        ])));
    }


    /**
     * 物品
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function thing(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Manage/Mail/thing.twig', [
            'request'  => $request,
            'things'   => Helpers::$things,
            'post_url' => 'Manage/Mail/thingPost',
        ])));
    }


    /**
     * 物品
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function thingPost(TcpConnection $connection, Request $request)
    {
        if (strtoupper($request->method()) !== 'POST') {
            return $connection->send(\cache_response($request, \view('Manage/Index/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }


        $content = trim($request->post('content'));
        if (empty($content)) {
            $content = '';
        }

        $id = trim($request->post('id'));

        $number = trim($request->post('number'));
        if (empty($number)) {
            return $connection->send(\cache_response($request, \view('Manage/Index/message.twig', [
                'request' => $request,
                'message' => 'Số lượng không thể trống',
            ])));
        }
        $number = intval($number);
        if ($number < 1 or $number > 99999999) {
            return $connection->send(\cache_response($request, \view('Manage/Index/message.twig', [
                'request' => $request,
                'message' => 'Phạm vi số lượng là 1-99999999',
            ])));
        }


        /**
         * 查询玩家
         */
        $name = $request->post('name');
        $name = trim($name);
        if (empty($name)) {
            /**
             * 全服玩家
             *
             */
            $sql = <<<SQL
SELECT `id` FROM `roles`;
SQL;

        } else {
            /**
             * 查询玩家是否存在
             *
             */
            $sql = <<<SQL
SELECT `id` FROM `roles` WHERE `name` = '$name';
SQL;

        }
        $roles = Helpers::queryFetchAll($sql);
        if (!is_array($roles) or count($roles) < 1) {
            return $connection->send(\cache_response($request, \view('Manage/Index/message.twig', [
                'request' => $request,
                'message' => 'Người chơi 【' . $name . '】Không tồn tại',
            ])));
        }
        $time = time();
        $sql = '';
        foreach ($roles as $role) {
            $sql .= <<<SQL
INSERT INTO `mails` (`receiver_id`, `content`, `kind`, `e_id`, `number`, `timestamp`) VALUES ($role->id, '$content', '物品', $id, $number, $time);
SQL;

        }
        Helpers::execSql($sql);
        return $connection->send(\cache_response($request, \view('Manage/Index/message.twig', [
            'request' => $request,
            'message' => 'Thư đã được gửi',
        ])));
    }


    /**
     * 心法
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function xinfa(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Manage/Mail/xinfa.twig', [
            'request'  => $request,
            'xinfas'   => Helpers::$xinfas,
            'post_url' => 'Manage/Mail/xinfaPost',
        ])));
    }


    /**
     * 心法
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function xinfaPost(TcpConnection $connection, Request $request)
    {
        if (strtoupper($request->method()) !== 'POST') {
            return $connection->send(\cache_response($request, \view('Manage/Index/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }


        $content = trim($request->post('content'));
        if (empty($content)) {
            $content = '';
        }

        $id = trim($request->post('id'));

        $lv = trim($request->post('lv'));
        $max_lv = trim($request->post('max_lv'));
        if (empty($lv) or empty($max_lv)) {
            return $connection->send(\cache_response($request, \view('Manage/Index/message.twig', [
                'request' => $request,
                'message' => 'Cấp độ và cấp độ tối đa không được để trống',
            ])));
        }
        $lv = intval($lv);
        $max_lv = intval($max_lv);
        if ($lv < 1 or $lv > 999 or $max_lv < 1 or $max_lv > 999) {
            return $connection->send(\cache_response($request, \view('Manage/Index/message.twig', [
                'request' => $request,
                'message' => 'Cấp độ, phạm vi cấp độ tối đa là 1-999',
            ])));
        }


        /**
         * 查询玩家
         */
        $name = $request->post('name');
        $name = trim($name);
        if (empty($name)) {
            /**
             * 全服玩家
             *
             */
            $sql = <<<SQL
SELECT `id` FROM `roles`;
SQL;

        } else {
            /**
             * 查询玩家是否存在
             *
             */
            $sql = <<<SQL
SELECT `id` FROM `roles` WHERE `name` = '$name';
SQL;

        }
        $roles = Helpers::queryFetchAll($sql);
        if (!is_array($roles) or count($roles) < 1) {
            return $connection->send(\cache_response($request, \view('Manage/Index/message.twig', [
                'request' => $request,
                'message' => 'Người chơi 【' . $name . '】Không tồn tại',
            ])));
        }
        $time = time();
        $sql = '';
        foreach ($roles as $role) {
            $sql .= <<<SQL
INSERT INTO `mails` (`receiver_id`, `content`, `kind`, `e_id`, `number`, `timestamp`, `lv`, `max_lv`) VALUES ($role->id, '$content', '心法', $id, 1, $time, $lv, $max_lv);
SQL;

        }
        Helpers::execSql($sql);
        return $connection->send(\cache_response($request, \view('Manage/Index/message.twig', [
            'request' => $request,
            'message' => 'Thư đã được gửi',
        ])));
    }


    /**
     * 元宝
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function yuanbao(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Manage/Mail/yuanbao.twig', [
            'request'  => $request,
            'post_url' => 'Manage/Mail/yuanbaoPost',
        ])));
    }


    /**
     * 元宝
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function yuanbaoPost(TcpConnection $connection, Request $request)
    {
        if (strtoupper($request->method()) !== 'POST') {
            return $connection->send(\cache_response($request, \view('Manage/Index/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }


        $content = trim($request->post('content'));
        if (empty($content)) {
            $content = '';
        }

        $number = trim($request->post('number'));
        if (empty($number)) {
            return $connection->send(\cache_response($request, \view('Manage/Index/message.twig', [
                'request' => $request,
                'message' => 'Số lượng không thể trống',
            ])));
        }
        $number = intval($number);
        if ($number < 1 or $number > 99999999) {
            return $connection->send(\cache_response($request, \view('Manage/Index/message.twig', [
                'request' => $request,
                'message' => 'Phạm vi số lượng là 1-99999999',
            ])));
        }


        /**
         * 查询玩家
         */
        $name = $request->post('name');
        $name = trim($name);
        if (empty($name)) {
            /**
             * 全服玩家
             *
             */
            $sql = <<<SQL
SELECT `id` FROM `roles`;
SQL;

        } else {
            /**
             * 查询玩家是否存在
             *
             */
            $sql = <<<SQL
SELECT `id` FROM `roles` WHERE `name` = '$name';
SQL;

        }
        $roles = Helpers::queryFetchAll($sql);
        if (!is_array($roles) or count($roles) < 1) {
            return $connection->send(\cache_response($request, \view('Manage/Index/message.twig', [
                'request' => $request,
                'message' => 'Người chơi 【' . $name . '】Không tồn tại',
            ])));
        }
        $time = time();
        $sql = '';
        foreach ($roles as $role) {
            $sql .= <<<SQL
INSERT INTO `mails` (`receiver_id`, `content`, `kind`, `number`, `timestamp`) VALUES ($role->id, '$content', '元宝', $number, $time);
SQL;

        }
        Helpers::execSql($sql);
        return $connection->send(\cache_response($request, \view('Manage/Index/message.twig', [
            'request' => $request,
            'message' => 'Thư đã được gửi',
        ])));
    }
}
