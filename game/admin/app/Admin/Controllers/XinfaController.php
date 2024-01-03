<?php

namespace App\Admin\Controllers;

use App\Models\Sect;
use App\Models\Xinfa;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class XinfaController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '心法管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Xinfa());

        $grid->quickSearch('name');

        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', __('名称'));
        $grid->column('description', __('描述'));
        $grid->column('kind', __('种类'))->editable('select', ['生命' => '生命', '攻击' => '攻击', '内功' => '内功',]);
        $sects = ['0' => '无'] + Sect::pluck('name', 'id')->toArray();
        $grid->column('sect_id', __('门派'))->editable('select', $sects)->sortable();
        $grid->column('experience', __('修为限制'))->help('单位：年')->sortable();
        $skills = ['0' => '无'] + DB::table('skills')->pluck('name', 'id')->toArray();
        $grid->column('skill_id', __('技能限制'))->editable('select', $skills);
        $grid->column('skill_lv', __('技能等级限制'));
        // $grid->column('default_max_lv', __('默认最大等级'));
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
        $show = new Show(Xinfa::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('名称'));
        $show->field('description', __('描述'));
        $show->field('kind', __('种类'));
        $show->field('sect_id', __('门派'));
        $show->field('experience', __('修为限制'));
        $show->field('skill_id', __('技能限制 ID'));
        $show->field('skill_lv', __('技能限制等级'));
        // $show->field('default_max_lv', __('默认最大等级'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Xinfa());

        $form->text('name', __('名称'));
        $form->textarea('description', __('描述'));
        $form->select('kind', __('种类'))->options([
            '生命' => '生命', '攻击' => '攻击', '内功' => '内功',
        ])->default('生命');
        $sects = ['0' => '无'] + Sect::pluck('name', 'id')->toArray();
        $form->select('sect_id', __('门派'))->options($sects)->default(0);
        $form->number('experience', __('修为限制'))->default(0)->help('单位：年');
        $skills = ['0' => '无'] + DB::table('skills')->pluck('name', 'id')->toArray();
        $form->select('skill_id', __('技能限制'))->default('0')->options($skills);
        $form->number('skill_lv', __('技能限制等级'))->default(0);
        // $form->number('default_max_lv', __('默认最大等级'))->default(0);

        return $form;
    }
}
