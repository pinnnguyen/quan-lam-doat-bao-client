<?php

namespace App\Http\Controllers\Manage;

use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 管理面板首页
 *
 */
class IndexController
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
        return $connection->send(\cache_response($request, \view('Manage/Index/index.twig', [
            'request' => $request,
        ])));
    }

    public function roleList(TcpConnection $connection, Request $request,int $page=1){
        $keyword = $request->post("keyword");
        $sql = "select id,`name` from roles ";
        if (!empty($keyword)){
            $sql.=" where `name` like '".$keyword."'";
        }
        $pageSize = 20;
        if (empty($page)) $page = 1;
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $pageSize;
        $sql.=" limit {$offset},{$pageSize}";
        //echo $sql;
        $roleList = Helpers::queryFetchAll($sql);
        $arrRole = [];
        foreach ($roleList as $role){
            $role->bannedUrl = 'Manage/Index/chatBanned/'.$role->id;
            $role->unbanUrl = 'Manage/Index/chatUnBan/'.$role->id;
            $arrRole[] = $role;
        }

        return $connection->send(\cache_response($request, \view('Manage/Index/roleList.twig', [
            'request' => $request,
            'roleList' => $arrRole,
            'last_page' => 'Manage/Index/roleList/' . ($page - 1),
            'next_page' => 'Manage/Index/roleList/' . ($page + 1),
        ])));
    }

    public function chatBanned(TcpConnection $connection, Request $request,int $roleId){
        $selectSql = "select count(*) as roleCount from chat_banned where role_id = ".$roleId;
        $role = Helpers::queryFetchObject($selectSql);
        if ($role->roleCount == 0){
            $time = time();
            $sql = "insert into chat_banned (role_id,create_time) values ({$roleId},{$time});";
            Helpers::execSql($sql);
        }
        $message = 'Cấm ngôn thành công';
        return $connection->send(\cache_response($request, \view('Manage/Index/chatBanned.twig', [
            'request' => $request,
            'message' => $message
        ])));
    }

    public function chatUnban(TcpConnection $connection, Request $request,int $roleId){
        $selectSql = "select count(*) as roleCount from chat_banned where role_id = ".$roleId;
        $role = Helpers::queryFetchObject($selectSql);
        if ($role->roleCount > 0){
            $time = time();
            $sql = "delete from chat_banned where role_id = {$roleId}";
            Helpers::execSql($sql);
        }
        $message = 'Giải trừ cấm ngôn thành công';
        return $connection->send(\cache_response($request, \view('Manage/Index/chatBanned.twig', [
            'request' => $request,
            'message' => $message
        ])));
    }

}
