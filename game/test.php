<?php


use App\Core\Configs\DBConfig;


require_once __DIR__ . '/vendor/autoload.php';

date_default_timezone_set('Asia/Hong_kong');


 \App\Libs\Events\Initializers\SettingInitializer::hook();
 \App\Libs\Events\Initializers\MapInitializer::hook();




 $pdo = new PDO(DBConfig::DRIVER .
     ':dbname=' . DBConfig::DATABASE .
   ';unix_socket=/tmp/mysql.sock' .
     ';charset=' . DBConfig::CHARSET,
     DBConfig::USERNAME,
    DBConfig::PASSWORD,
     [PDO::ATTR_PERSISTENT => true,]);
 $ip_names = cache()->keys('role_ip_*');
 $roles = [];
 foreach ($ip_names as $ip_name) {
     $role_id = substr($ip_name, 8);
     $ip = cache()->get($ip_name);
    $role = cache()->get('role_row_' . $role_id);
     if ($role) {
         if (empty($roles[$ip])) {
             $roles[$ip][] = $ip;
         }
         $user = $pdo->query("SELECT user_name,phone_number FROM users WHERE id = $role_id LIMIT 1;")->fetchObject();
         $roles[$ip][$role->id] = $role->name . '(' . $user->user_name . ', ' . $user->phone_number . ')';
     }
 }
 $result = var_export($roles, true);

 file_put_contents(__DIR__ . '/ip.txt', $result);



// $roles = cache()->keys('role_row_*');
// foreach ($roles as $role){
//     $row = cache()->get($role);
//     $attrs = cache()->get('role_attrs_'.$row->id);
    
//     file_put_contents(__DIR__ . '/role.txt', $row->name.' 生命：'.$attrs->maxHp.'，内力：'.$attrs->maxMp.PHP_EOL,FILE_APPEND);
// }




$roles = cache()->keys('role_id_*');
cache()->del(...$roles);



// $npcs = cache()->get('map_npcs_attrs');
// $npcss = array_column($npcs, 'maxHp', 'npcId');
// $npcs = array_column($npcs, null, 'npcId');

// foreach ($npcs as $key=>$npc){
//     $npcs[$key]=['名字'=>$npc->name,'血量'=>$npc->maxHp];
// }

// array_multisort($npcss,$npcs);
// $result = var_export($npcs, true);
// file_put_contents(__DIR__ . '/npcs.txt', $result);



