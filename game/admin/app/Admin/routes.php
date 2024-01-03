<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'     => config('admin.route.prefix'),
    'namespace'  => config('admin.route.namespace'),
    'middleware' => config('admin.route.middleware'),
    'as'         => config('admin.route.prefix') . '.',
], function (Router $router) {

    $router->any('settings', Setting::class);
    $router->get('/', 'HomeController@index')->name('home');
    $router->resource('maps', MapController::class);
    $router->resource('npcs', NpcController::class);
    $router->resource('map-npcs', MapNpcController::class);
    $router->resource('skills', SkillController::class);
    $router->resource('skill-tricks', SkillTrickController::class);
    $router->resource('xinfas', XinfaController::class);
    $router->resource('xinfa-tricks', XinfaTrickController::class);
    $router->resource('things', ThingController::class);
    $router->resource('xinfa-attack-tricks', XinfaAttackTrickController::class);

    $router->resource('xinfa-hp-tricks', XinfaHpTrickController::class);
    $router->resource('xinfa-mp-tricks', XinfaMpTrickController::class);
    $router->resource('regions', RegionController::class);
    $router->resource('sects', SectController::class);
    $router->resource('equipment-kinds', EquipmentKindController::class);
    $router->resource('npc-ranks', NpcRankController::class);

    $router->resource('shops', ShopController::class);
    $router->resource('npc-wugong-tpls', NpcWugongTplController::class);

});
