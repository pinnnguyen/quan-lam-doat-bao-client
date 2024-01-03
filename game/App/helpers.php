<?php


use App\Libs\Helpers;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;


function response(string $body = '', int $status = 200, array $headers = []): Response
{
    return Helpers::response($body, $status, $headers);
}


function cache_response(Request $request, string $body): Response
{
    return Helpers::cacheResponse($request, $body);
}


function cache(): Redis
{
    return Helpers::cache();
}


function view($name, array $context = []): string
{
    return Helpers::view($name, $context);
}


function db(): PDO
{
    return Helpers::db();
}


function lock(): Redis
{
    return Helpers::lock();
}


const LOG_PICK_UP_THINGS = '拾取记录';
const LOG_TRANSACTIONS_GIVES = '交易赠与';
const LOG_BLACKMARKET_TRANSACTIONS = '心法黑市';
const LOG_EXPERIENCE = '修为记录';
const LOG_XINFA_UPGRADE = '升级记录';

function loglog(string $log_type, string $action, array $info): void
{
    $format = '#DATETIME# #ACTION# #INFO#' . PHP_EOL;
    $file = __DIR__ . '/Runtime/Logs/' . $log_type . '-' . date('Y-m-d') . '.txt';
    file_put_contents($file, str_replace([
        '#DATETIME#',
        '#ACTION#',
        '#INFO#',
    ], [
        (new DateTime())->format('Y-m-d H:i:s.u'),
        $action,
        json_encode($info, JSON_UNESCAPED_UNICODE),
    ], $format), FILE_APPEND);
}




