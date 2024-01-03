<?php

namespace App\Http\Controllers\User;

use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 初始引导
 *
 */
class PlotController
{
    /**
     * 初始步骤
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $step
     *
     * @return bool|null
     */
    public function step(TcpConnection $connection, Request $request, int $step)
    {
        return $connection->send(\cache_response($request, \view('User/Plot/step/' . $step . '.twig', [
            'request' => $request,
        ])));
    }


    /**
     * 步骤结束
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function over(TcpConnection $connection, Request $request)
    {
        /**
         * 设置完成初始引导
         *
         */
        $sql = <<<SQL
UPDATE `roles` SET `plot` = 1 WHERE `id` = $request->roleId;
SQL;


        Helpers::execSql($sql);


        $request->roleRow->plot = 1;
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);

        return $connection->send(\cache_response($request, \view('User/Plot/over.twig', [
            'request' => $request,
        ])));
    }
}
