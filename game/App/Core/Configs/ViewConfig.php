<?php

namespace App\Core\Configs;

use App\Libs\Helpers;

/**
 * Twig 视图配置
 */
class ViewConfig
{
    /**
     * 模板目录
     *
     */
    const TEMPLATE_DIR = __DIR__ . '/../../Http/Views';

    /**
     * 模板缓存目录
     *
     */
    const CACHE_DIR = __DIR__ . '/../../Runtime/Views/Caches';

    /**
     * 是否Mở ra调试
     *
     */
    const DEBUG = true;

    /**
     * 是否Mở ra自动重新加载
     *
     */
    const AUTO_RELOAD = true;

    /**
     * 注册模板函数
     *
     */
    const TEMPLATE_FUNCTIONS = [
        'config'                       => [Helpers::class, 'config'],
        'url'                          => [Helpers::class, 'createUrl'],
        'get_execute_millisecond_time' => [Helpers::class, 'getExecuteMillisecondTime'],
        'get_current_datetime'         => [Helpers::class, 'getCurrentDatetime'],
        'get_ua_info'                  => [Helpers::class, 'getUAInfo'],
        'get_hans_number'              => [Helpers::class, 'getHansNumber'],
        'get_hans_money'               => [Helpers::class, 'getHansMoney'],
        'get_hans_experience'          => [Helpers::class, 'getHansExperience'],
        'get_skill_exp'                => [Helpers::class, 'getSkillExp'],
        'get_percent'                  => [Helpers::class, 'getPercent'],
    ];
}
