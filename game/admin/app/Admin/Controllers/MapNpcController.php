<?php

namespace App\Admin\Controllers;

use App\Models\MapNpc;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class MapNpcController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '地图NPC放置管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new MapNpc());

        $grid->filter(function ($filter) {
            $filter->where(function ($query) {
                $query->whereHas('map', function ($query) {
                    $query->where('name', 'like', "%{$this->input}%");
                });
            }, '地图名称');
            $filter->where(function ($query) {
                $query->whereHas('npc', function ($query) {
                    $query->where('name', 'like', "%{$this->input}%");
                });
            }, 'NPC名称');
        });

        $regions = [0 => '无'] + DB::table('regions')->pluck('name', 'id')->toArray();
        $npcs = DB::table('npcs')->select(['id', 'region_id', 'name', 'alias'])->get()->toArray();
        $npcs = array_map(function ($npc) use ($regions) {
            return ['id' => $npc->id, 'name' => $regions[$npc->region_id] . ' - ' . $npc->name . ' - ' . $npc->alias];
        }, $npcs);
        $npcs = ['0' => '无'] + array_column($npcs, 'name', 'id');

        $maps = DB::table('maps')->select(['id', 'region_id', 'name', 'alias'])->get()->toArray();
        $maps = array_map(function ($map) use ($regions) {
            return ['id' => $map->id, 'name' => $regions[$map->region_id] . ' - ' . $map->name . ' - ' . $map->alias];
        }, $maps);
        $maps = ['0' => '无'] + array_column($maps, 'name', 'id');

        $grid->column('id', __('ID'))->width(200);
        $grid->column('map_id', __('地图'))->editable('select', $maps)->width(300);
        $grid->column('npc_id', __('NPC'))->editable('select', $npcs)->width(300);

        $grid->column('number', __('数量'))->editable();

        $grid->column('guard_north', __('守护北出口'))->switch([
            'on'  => ['value' => '1', 'text' => '是', 'color' => 'success'],
            'off' => ['value' => '0', 'text' => '否', 'color' => 'danger'],
        ]);
        $grid->column('guard_west', __('守护西出口'))->switch([
            'on'  => ['value' => '1', 'text' => '是', 'color' => 'success'],
            'off' => ['value' => '0', 'text' => '否', 'color' => 'danger'],
        ]);
        $grid->column('guard_east', __('守护东出口'))->switch([
            'on'  => ['value' => '1', 'text' => '是', 'color' => 'success'],
            'off' => ['value' => '0', 'text' => '否', 'color' => 'danger'],
        ]);
        $grid->column('guard_south', __('守护南出口'))->switch([
            'on'  => ['value' => '1', 'text' => '是', 'color' => 'success'],
            'off' => ['value' => '0', 'text' => '否', 'color' => 'danger'],
        ]);
        $grid->disableExport();
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(MapNpc::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('map_id', __('地图 ID'));
        $show->field('npc_id', __('NPC ID'));
        $show->field('number', __('Number'));
        $show->field('guard_north', __('守护北出口'));
        $show->field('guard_west', __('守护西出口'));
        $show->field('guard_east', __('守护东出口'));
        $show->field('guard_south', __('守护南出口'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new MapNpc());
        $regions = [0 => '无'] + DB::table('regions')->pluck('name', 'id')->toArray();
        $npcs = DB::table('npcs')->select(['id', 'region_id', 'name', 'alias'])->get()->toArray();
        $npcs = array_map(function ($npc) use ($regions) {
            return ['id' => $npc->id, 'name' => $regions[$npc->region_id] . ' - ' . $npc->name . ' - ' . $npc->alias];
        }, $npcs);
        $npcs = ['0' => '无'] + array_column($npcs, 'name', 'id');

        $maps = DB::table('maps')->select(['id', 'region_id', 'name', 'alias'])->get()->toArray();
        $maps = array_map(function ($map) use ($regions) {
            return ['id' => $map->id, 'name' => $regions[$map->region_id] . ' - ' . $map->name . ' - ' . $map->alias];
        }, $maps);
        $maps = ['0' => '无'] + array_column($maps, 'name', 'id');
        $form->select('map_id', __('地图'))->options($maps)->required();
        $form->select('npc_id', __('NPC'))->options($npcs)->required();
        $form->number('number', __('数量'))->default(0);
        $form->switch('guard_north', __('守护北出口'))->states([
            'on'  => ['value' => '1', 'text' => '是', 'color' => 'success'],
            'off' => ['value' => '0', 'text' => '否', 'color' => 'danger'],
        ])->default('0');
        $form->switch('guard_west', __('守护西出口'))->states([
            'on'  => ['value' => '1', 'text' => '是', 'color' => 'success'],
            'off' => ['value' => '0', 'text' => '否', 'color' => 'danger'],
        ])->default('0');
        $form->switch('guard_east', __('守护东出口'))->states([
            'on'  => ['value' => '1', 'text' => '是', 'color' => 'success'],
            'off' => ['value' => '0', 'text' => '否', 'color' => 'danger'],
        ])->default('0');
        $form->switch('guard_south', __('守护南出口'))->states([
            'on'  => ['value' => '1', 'text' => '是', 'color' => 'success'],
            'off' => ['value' => '0', 'text' => '否', 'color' => 'danger'],
        ])->default('0');

        return $form;
    }
}
