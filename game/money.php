<?php

use App\Core\Configs\DBConfig;


require_once __DIR__ . '/vendor/autoload.php';

date_default_timezone_set('Asia/Hong_kong');


// \App\Libs\Events\Initializers\SettingInitializer::hook();
// \App\Libs\Events\Initializers\MapInitializer::hook();




$pdo = new PDO(DBConfig::DRIVER .
    ':dbname=' . DBConfig::DATABASE .
    ';unix_socket=/tmp/mysql.sock' .
    ';charset=' . DBConfig::CHARSET,
    DBConfig::USERNAME,
    DBConfig::PASSWORD,
    [PDO::ATTR_PERSISTENT => true,]);


for($i=0;;$i++){
    $res = $pdo->query("
    SELECT SUM(num) as num FROM  (
(    SELECT SUM(number) AS num FROM role_things INNER JOIN roles ON roles.id = role_id INNER JOIN users ON users.id = user_id AND is_ban = 0 WHERE thing_id = 213)
    UNION
    (SELECT SUM(roles.bank_balance) AS num FROM roles INNER JOIN users ON users.id = user_id AND is_ban = 0) 
) t;
    ")->fetchObject();
    
    if ($i%3600===0) {
        // code...
        $hour_num=$res->num;
    }
    if($i%60===0){
        
        $min_num=$res->num;
    }

file_put_contents(__DIR__ . '/money.txt', date('Y-m-d H:i:s').' '. $res->num.' '.($res->num-$min_num).' '.($res->num-$hour_num).PHP_EOL, FILE_APPEND );
sleep(1);
}
