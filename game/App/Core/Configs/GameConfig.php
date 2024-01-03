<?php

namespace App\Core\Configs;


/**
 * 游戏配置
 */
class GameConfig
{
    /**
     * 游戏名称
     */
    const NAME = 'Quân lâm đoạt bảo';

    /**
     * 进入游戏后的 path 可随意更改 不能包含 "/" "?" "&" 等特殊字符和中文
     */
    const PATH = 'gCmd.do';

    /**
     * logo 地址
     */
    const LOGO = 'http://127.0.0.1:8000/uploads/images/logo.jpg';

    /**
     * QQ 群号
     */
    const QQ_GROUP = '157661208';

    /**
     * 加群链接
     */
    const QQ_GROUP_LINK = 'https://jq.qq.com/?_wv=1027&k=XivObYkl';

    /*
     * 游戏网址
     */
    const ADDRESS = 'http://127.0.0.1:9999/';

    /**
     * 版主 ID
     */
    const FORUMS = [1042, 20];

    /**
     * 管理员 ID
     */
    const MANAGERS = [2, 20, 151,1];


    const VIP1 = 1;
    const VIP2 = 2;
    const VIP3 = 4;
    const VIP4 = 8;
    const VIP5 = 16;
    const VIP6 = 32;
    const VIP7 = 64;
    const VIP8 = 128;
    const VIP9 = 256;
    const VIP10 = 512;

    const VIP1_SCORE = 1500;
    const VIP2_SCORE = 3000;
    const VIP3_SCORE = 6000;
    const VIP4_SCORE = 12000;
    const VIP5_SCORE = 24000;
    const VIP6_SCORE = 48000;
    const VIP7_SCORE = 96000;
    const VIP8_SCORE = 192000;
    const VIP9_SCORE = 384000;
    const VIP10_SCORE = 768000;
}
