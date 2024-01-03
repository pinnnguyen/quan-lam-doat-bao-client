<?php

namespace App\Core\Components;

use App\Core\Configs\DBConfig;
use PDO;

/**
 * DB 数据库 单例
 */
class DB
{
    /**
     * PDO 数据库实例
     *
     * @var PDO|null
     */
    protected static ?PDO $instance = null;


    /**
     * 获取数据库实例
     *
     * @return PDO
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
//            self::$instance = new PDO(DBConfig::DRIVER .
//                ':dbname=' . DBConfig::DATABASE .
//                ';host=' . DBConfig::HOST .
//                ';port=' . DBConfig::PORT .
//                ';charset=' . DBConfig::CHARSET,
//                DBConfig::USERNAME,
//                DBConfig::PASSWORD,
//                [PDO::ATTR_PERSISTENT => true,]);
            self::$instance = new PDO(DBConfig::DRIVER .
                ':dbname=' . DBConfig::DATABASE .
                ';host=' . DBConfig::HOST .
                ';charset=' . DBConfig::CHARSET,
                DBConfig::USERNAME,
                DBConfig::PASSWORD,
                [PDO::ATTR_PERSISTENT => true]);
        }
        return self::$instance;
    }
}
