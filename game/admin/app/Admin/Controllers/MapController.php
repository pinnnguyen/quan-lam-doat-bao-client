<?php

namespace App\Admin\Controllers;

use App\Models\Map;
use App\Models\Region;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class MapController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '地图管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Map());
        $grid->quickSearch('name');

        $grid->filter(function ($filter) {
            $filter->where(function ($query) {
                $query->where('name', 'like', "%{$this->input}%");
            }, '地图名称');
        });

//        $grid->quickSearch(function ($model, $query) {
//            $region_id = Region::where('name',$query)->orWhere('name', 'like', "%{$query}%")->value('id');
//            $model->where('region_id', $region_id);
//        });

        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', __('名称'))->text();
        $grid->column('alias', __('别名'))->text();
        $grid->column('description', __('描述'))->editable('textarea');
        $regions = ['0' => '无'] + Region::pluck('name', 'id')->toArray();
        $grid->column('region_id', __('地区'))->editable('select', $regions)->sortable();
        $maps = DB::table('maps')->select(['id', 'region_id', 'name', 'alias'])->get()->toArray();
        $maps = array_map(function ($map) use ($regions) {
            return ['id' => $map->id, 'name' => $regions[$map->region_id] . ' - ' . $map->name . ' - ' . $map->alias];
        }, $maps);
        $maps = ['0' => '无'] + array_column($maps, 'name', 'id');
        $grid->column('north_map_id', __('北出口地图'))->editable('select', $maps);
        $grid->column('west_map_id', __('西出口地图'))->editable('select', $maps);
        $grid->column('east_map_id', __('东出口地图'))->editable('select', $maps);
        $grid->column('south_map_id', __('南出口地图'))->editable('select', $maps);
        $grid->column('is_allow_fight', __('是否允许战斗'))->switch([
            'on'  => ['value' => '1', 'text' => '允许', 'color' => 'success'],
            'off' => ['value' => '0', 'text' => '禁止', 'color' => 'danger'],
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
        $show = new Show(Map::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('名称'));
        $show->field('description', __('描述'));
        $show->field('region_id', __('地区'));
        $show->field('north_map_id', __('北'));
        $show->field('west_map_id', __('西'));
        $show->field('east_map_id', __('东'));
        $show->field('south_map_id', __('南'));
        $show->field('is_allow_fight', __('战斗'));
        $show->field('actions', __('事件'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Map());

        $form->text('name', __('名称'));
        $form->text('alias', __('别名'));
        $form->textarea('description', __('描述'));

        $regions = ['0' => '无'] + Region::pluck('name', 'id')->toArray();
        $form->select('region_id', __('地区'))->options($regions);
        $maps = DB::table('maps')->select(['id', 'region_id', 'name', 'alias'])->get()->toArray();
        $maps = array_map(function ($map) use ($regions) {
            return ['id' => $map->id, 'name' => $regions[$map->region_id] . ' - ' . $map->name . ' - ' . $map->alias];
        }, $maps);
        $maps = ['0' => '无'] + array_column($maps, 'name', 'id');
        $form->select('north_map_id', __('北'))->options($maps);
        $form->select('west_map_id', __('西'))->options($maps);
        $form->select('east_map_id', __('东'))->options($maps);
        $form->select('south_map_id', __('南'))->options($maps);
        $form->switch('is_allow_fight', __('是否允许战斗'))->states([
            'on'  => ['value' => '1', 'text' => '允许', 'color' => 'success'],
            'off' => ['value' => '0', 'text' => '禁止', 'color' => 'danger'],
        ])->default('0');
        $form->textarea('actions', __('事件'))->rules('nullable');

        return $form;
    }
}
