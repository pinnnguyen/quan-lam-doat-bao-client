<?php

namespace App\Core\Components;

use App\Core\Configs\ViewConfig;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

/**
 * View 视图 单例
 */
class View
{
    /**
     * 视图实例 / Twig 实例
     *
     * @var Environment|null
     */
    protected static ?Environment $instance = null;


    /**
     * 获取视图实例
     *
     * @return Environment
     */
    public static function getInstance(): Environment
    {
        if (static::$instance === null) {
            static::$instance = new Environment(new FilesystemLoader(ViewConfig::TEMPLATE_DIR), [
                'cache'       => ViewConfig::CACHE_DIR,
                // 'debug'       => ViewConfig::DEBUG,
                'auto_reload' => ViewConfig::AUTO_RELOAD,
                'autoescape'  => false,
            ]);
            if (!empty(ViewConfig::TEMPLATE_FUNCTIONS)) {
                foreach (ViewConfig::TEMPLATE_FUNCTIONS as $name => $function) {
                    static::$instance->addFunction(new TwigFunction($name, $function));
                }
            }
        }
        return static::$instance;
    }
}
