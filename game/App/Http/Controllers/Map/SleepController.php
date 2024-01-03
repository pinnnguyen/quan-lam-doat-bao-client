<?php

namespace App\Http\Controllers\Map;

use App\Libs\Helpers;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 睡觉
 */
class SleepController
{
    /**
     * Bắt đầu睡觉
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function start(TcpConnection $connection, Request $request)
    {
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        $role_attrs->startSleepTimestamp = time();
        $role_attrs->hp = $role_attrs->maxHp;
        $role_attrs->mp = $role_attrs->maxMp;
        $role_attrs->jingshen = $role_attrs->maxJingshen;
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
        return $connection->send(\cache_response($request, \view('Map/Sleep/start.twig', [
            'request' => $request,
        ])));
    }


    /**
     * Chờ đợi
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function waiting(TcpConnection $connection, Request $request)
    {
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if (time() - $role_attrs->startSleepTimestamp > 5) {
            cache()->rPush('role_messages_' . $request->roleId, 'Ngươi một giấc ngủ dậy, chỉ cảm thấy tinh lực dư thừa. Nên hoạt động một chút.');
            return (new IndexController())->index($connection, $request);
        }
        return $connection->send(\cache_response($request, \view('Map/Sleep/waiting.twig', [
            'request' => $request,
        ])));
    }
}
