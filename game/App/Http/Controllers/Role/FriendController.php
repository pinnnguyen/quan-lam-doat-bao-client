<?php

namespace App\Http\Controllers\Role;

use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * Danh sách bạn bè
 *
 */
class FriendController
{
    /**
     * Danh sách bạn bè
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        if (empty($request->roleRow->follows)) {
            $follows = [];
        } else {
            $follows = json_decode($request->roleRow->follows, true);
        }
        $follows = array_unique($follows);
        $friends = [];
        $query_role = '';
        foreach ($follows as $follow) {
            $role = Helpers::getRoleRowByRoleId($follow);
            if (empty($role)) {
                $query_role .= $follow . ',';
            } else {
                $friends[] = [
                    'name'       => $role->name,
                    'viewUrl'    => 'Map/Role/view/' . $follow,
                    'mailUrl'    => 'Role/Mail/send/' . $follow,
                    'messageUrl' => 'Map/Role/message/' . $follow,
                ];
            }
        }
        if ($query_role != '') {
            $query_role = substr($query_role, 0, -1);
            $sql = <<<SQL
SELECT `name`, `id` FROM `roles` WHERE `id` IN ($query_role);
SQL;

            $roles = Helpers::queryFetchAll($sql);
            if (!empty($roles)) {
                foreach ($roles as $role) {
                    $friends[] = [
                        'name'    => $role->name,
                        'mailUrl' => 'Role/Mail/send/' . $role->id,
                    ];
                }
            }
        }
        return $connection->send(\cache_response($request, \view('Role/Friend/index.twig', [
            'request' => $request,
            'friends' => $friends,
        ])));
    }


    /**
     * 黑名单
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function black(TcpConnection $connection, Request $request)
    {
        if (empty($request->roleRow->blocks)) {
            $blocks = [];
        } else {
            $blocks = json_decode($request->roleRow->blocks, true);
        }
        $blocks = array_unique($blocks);
        $friends = [];
        $query_role = '';
        foreach ($blocks as $block) {
            $role = Helpers::getRoleRowByRoleId($block);
            if (empty($role)) {
                $query_role .= $block . ',';
            } else {
                $friends[] = [
                    'name'       => $role->name,
                    'viewUrl'    => 'Map/Role/view/' . $block,
                    'unblockUrl' => 'Map/Role/unblock/' . $block,
                ];
            }
        }
        if ($query_role != '') {
            $query_role = substr($query_role, 0, -1);
            $sql = <<<SQL
SELECT `name`, `id` FROM `roles` WHERE `id` IN ($query_role);
SQL;

            $roles = Helpers::queryFetchAll($sql);
            if (!empty($roles)) {
                foreach ($roles as $role) {
                    $friends[] = [
                        'name'       => $role->name,
                        'unblockUrl' => 'Map/Role/unblock/' . $role->id,
                    ];
                }
            }
        }
        return $connection->send(\cache_response($request, \view('Role/Friend/black.twig', [
            'request' => $request,
            'friends' => $friends,
        ])));
    }


    /**
     * 管理好友
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function manage(TcpConnection $connection, Request $request)
    {
        if (empty($request->roleRow->follows)) {
            $follows = [];
        } else {
            $follows = json_decode($request->roleRow->follows, true);
        }
        $follows = array_unique($follows);
        $friends = [];
        $query_role = '';
        foreach ($follows as $follow) {
            $role = Helpers::getRoleRowByRoleId($follow);
            if (empty($role)) {
                $query_role .= $follow . ',';
            } else {
                $friends[] = [
                    'name'        => $role->name,
                    'viewUrl'     => 'Map/Role/view/' . $follow,
                    'blockUrl'    => 'Map/Role/block/' . $follow,
                    'unfollowUrl' => 'Map/Role/unfollow/' . $follow,
                ];
            }
        }
        if ($query_role != '') {
            $query_role = substr($query_role, 0, -1);
            $sql = <<<SQL
SELECT `name`, `id` FROM `roles` WHERE `id` IN ($query_role);
SQL;

            $roles = Helpers::queryFetchAll($sql);
            if (!empty($roles)) {
                foreach ($roles as $role) {
                    $friends[] = [
                        'name'        => $role->name,
                        'blockUrl'    => 'Map/Role/block/' . $role->id,
                        'unfollowUrl' => 'Map/Role/unfollow/' . $role->id,
                    ];
                }
            }
        }
        return $connection->send(\cache_response($request, \view('Role/Friend/manage.twig', [
            'request' => $request,
            'friends' => $friends,
        ])));
    }
}
