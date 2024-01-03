<?php

namespace App\Admin\Controllers;

use App\Models\NpcRank;
use App\Models\NpcWugongTpl;
use App\Models\Sect;
use App\Models\Skill;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class NpcWugongTplController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'NPC 武功模板';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new NpcWugongTpl());

        $grid->column('id', __('Id'));

        $grid->column('name', __('模板名称'));

        $ranks = ['0' => '无'] + NpcRank::pluck('name', 'id')->toArray();
        $grid->column('rank_id', __('阶层'))->editable('select', $ranks)->sortable();

        $grid->column('experience', __('修为'));

        $grid->column('base_jianfa_lv', __('基本剑法等级'));
        $grid->column('base_daofa_lv', __('基本刀法等级'));
        $grid->column('base_quanjiao_lv', __('基本拳脚等级'));
        $grid->column('base_neigong_lv', __('基本内功等级'));
        $grid->column('base_qinggong_lv', __('基本轻功等级'));
        $grid->column('base_zhaojia_lv', __('基本招架等级'));
        $grid->column('sect_qinggong_lv', __('门派轻功等级'));

        $sect_skills = ['0' => '无'] + Skill::where('is_base', 0)->pluck('name', 'id')->toArray();
        $grid->column('sect_skill', __('门派技能'))->editable('select', $sect_skills)->sortable();

        $grid->column('sect_skill_lv', __('门派技能等级'));

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
        $show = new Show(NpcWugongTpl::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('rank_id', __('Rank id'));
        $show->field('experience', __('Experience'));
        $show->field('base_jianfa_lv', __('Base jianfa lv'));
        $show->field('base_daofa_lv', __('Base daofa lv'));
        $show->field('base_quanjiao_lv', __('Base quanjiao lv'));
        $show->field('base_neigong_lv', __('Base neigong lv'));
        $show->field('base_qinggong_lv', __('Base qinggong lv'));
        $show->field('base_zhaojia_lv', __('Base zhaojia lv'));
        $show->field('sect_qinggong_lv', __('Sect qinggong lv'));
        $show->field('sect_skill', __('Sect skill'));
        $show->field('sect_skill_lv', __('Sect skill lv'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new NpcWugongTpl());

        $form->text('name', __('模板名称'));

        $ranks = ['0' => '无'] + NpcRank::pluck('name', 'id')->toArray();
        $form->select('rank_id', __('阶层'))->options($ranks)->default(0);

        $form->number('experience', __('修为（经验值）'))->default(0);

        $form->number('base_jianfa_lv', __('基本剑法等级'))->default(5);
        $form->number('base_daofa_lv', __('基本刀法等级'))->default(5);
        $form->number('base_quanjiao_lv', __('基本拳脚等级'))->default(5);
        $form->number('base_neigong_lv', __('基本内功等级'))->default(5);
        $form->number('base_qinggong_lv', __('基本轻功等级'))->default(5);
        $form->number('base_zhaojia_lv', __('基本招架等级'))->default(5);
        $form->number('sect_qinggong_lv', __('门派轻功等级'));

        $skills = ['0' => '无'] + DB::table('skills')->where('is_base', '0')->pluck('name', 'id')->toArray();
        $form->select('sect_skill', __('门派技能'))->default(0)->options($skills);
        $form->number('sect_skill_lv', __('门派技能等级'))->default(0);

        return $form;
    }
}
