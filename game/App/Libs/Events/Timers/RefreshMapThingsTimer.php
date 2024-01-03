<?php

namespace App\Libs\Events\Timers;

/**
 * 刷新地图物品
 *
 */
class RefreshMapThingsTimer{
    public static function hook(){
        /**
         * 获取所有地图物品 Keys
         *
         */
        $map_things_keys = cache() -> keys('map_things_*');
        if(!$map_things_keys){
            return;
        }

        /**
         * 单进程循环处理地图物品掉落
         *
         */
        foreach($map_things_keys as $map_things_key){
            $map_things = cache() -> hMGet($map_things_key, ['money', 'boxes', 'things', 'bodies', 'xinfas']);

            if(!empty($map_things['money'])){
                $money = unserialize($map_things['money']);
                if(!$money['is_no_expire'] and $money['expire'] < time()){
                    cache() -> hDel($map_things_key, 'money');
                }
            }

            if(!empty($map_things['xinfas'])){
                $xinfas = unserialize($map_things['xinfas']);
                foreach($xinfas as $key => $xinfa){
                    if($xinfa['expire'] < time()){
                        unset($xinfas[$key]);
                    }
                }
                cache() -> hSet($map_things_key, 'xinfas', serialize($xinfas));
            }

            if(!empty($map_things['boxes'])){
                $boxes = unserialize($map_things['boxes']);
                foreach($boxes as $key => $box){
                    if($box['expire'] < time()){
                        unset($boxes[$key]);
                    }
                }
                cache() -> hSet($map_things_key, 'boxes', serialize($boxes));
            }

            if(!empty($map_things['things'])){
                $things = unserialize($map_things['things']);
                foreach($things as $key => $thing){
                    if($thing['expire'] < time()){
                        unset($things[$key]);
                    }
                }
                cache() -> hSet($map_things_key, 'things', serialize($things));
            }

            if(!empty($map_things['bodies'])){
                $bodies = unserialize($map_things['bodies']);
                foreach($bodies as $key => $body){
                    if($body['expire'] < time()){
                        unset($bodies[$key]);
                    }
                }
                cache() -> hSet($map_things_key, 'bodies', serialize($bodies));
            }

        }
    }
}
