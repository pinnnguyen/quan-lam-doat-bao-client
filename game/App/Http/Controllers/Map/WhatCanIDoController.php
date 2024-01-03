<?php

namespace App\Http\Controllers\Map;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * Ta có thể làm cái gì
 */
class WhatCanIDoController
{
    /**
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function index(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Map/WhatCanIDo/index.twig', [
            'request' => $request,
        ])));
    }


    /**
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function description(TcpConnection $connection, Request $request, int $number)
    {
        return $connection->send(\cache_response($request, \view('Map/WhatCanIDo/description/' . $number . '.twig', [
            'request' => $request,
        ])));
    }
}