<?php

namespace App\Http\Controllers\Error;

use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * HTTP 自定义错误
 */
class HttpController
{
    /**
     * 404 Not Found
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function notFound(TcpConnection $connection, Request $request)
    {
        return $connection->send(\response(\view('Error/HttpError/notFound.twig', [
            'request' => $request,
            'title'   => '404 Not Found',
        ]), 404));
    }


    /**
     * 400 Bad Request
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function badRequest(TcpConnection $connection, Request $request)
    {
        return $connection->send(\response(\view('Error/HttpError/badRequest.twig', [
            'request' => $request,
            'title'   => '400 Bad Request',
        ]), 400));
    }


    /**
     * 401 Unauthorized
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function unauthorized(TcpConnection $connection, Request $request)
    {
        return $connection->send(\response(\view('Error/HttpError/unauthorized.twig', [
            'request' => $request,
            'title'   => '401 Unauthorized',
        ]), 401));
    }
}
