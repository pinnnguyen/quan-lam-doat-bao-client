<?php

namespace App\Libs\Events\Initializers;

/**
 * 初始化数据库连接
 */
class DbConnectionInitializer
{
    public static function hook()
    {
        db();
    }
}
