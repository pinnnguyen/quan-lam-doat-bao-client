<?php

namespace App\Admin\Controllers;

//use App\Admin\Actions\Tpl\bkyj;
use App\Models\Npc;
use App\Models\NpcRank;
use App\Models\NpcWugongTpl;
use App\Models\Region;
use App\Models\Sect;
use App\Models\Thing;
use Encore\Admin\Admin;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class NpcController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'NPC管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Npc());
        $grid->quickSearch('name');

        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', __('名称'));
        $grid->column('alias', __('别名'));
        $grid->column('description', __('描述'));
        $grid->column('appearance', __('外貌'));

        $regions = ['0' => '无'] + Region::pluck('name', 'id')->toArray();
        $grid->column('region_id', __('地区'))->editable('select', $regions)->sortable();

        $ranks = ['0' => '无'] + NpcRank::pluck('name', 'id')->toArray();
        $grid->column('rank_id', __('阶层'))->editable('select', $ranks)->sortable();

        $grid->column('gender', __('性别'));
        $grid->column('age', __('年龄'));


        $sects = ['0' => '无'] + Sect::pluck('name', 'id')->toArray();
        $grid->column('sect_id', __('门派'))->editable('select', $sects)->sortable();

        $grid->column('seniority', __('辈分'));
        $grid->column('base_hp', __('基础气血'));
        $weapons = ['0' => '无'] + DB::table('things')->whereIn('equipment_kind', [1, 2, 3])->pluck('name', 'id')->toArray();
        $grid->column('weapon', __('武器'))->editable('select', $weapons);
        $clothes = ['0' => '无'] + DB::table('things')->where('equipment_kind', 4)->pluck('name', 'id')->toArray();
        $grid->column('clothes', __('衣服'))->editable('select', $clothes);
        $armor = ['0' => '无'] + DB::table('things')->where('equipment_kind', 5)->pluck('name', 'id')->toArray();
        $grid->column('armor', __('内甲'))->editable('select', $armor);
        $shoes = ['0' => '无'] + DB::table('things')->where('equipment_kind', 6)->pluck('name', 'id')->toArray();
        $grid->column('shoes', __('鞋子'))->editable('select', $shoes);

        $wugong_tpls = ['0' => '无'] + NpcWugongTpl::pluck('name', 'id')->toArray();
        $grid->column('wugong_tpl_id', __('武功模板'))->editable('select', $wugong_tpls);


        // $grid->column('things',__('掉落物品'))->display(function ($things) {
        //
        //     $things = array_map(function ($thing) {
        //         return "<span class='label label-success'>{$thing['name']}</span>";
        //     }, $things);
        //
        //     return join('&nbsp;', $things);
        // });
//        $grid->column('equipments', __('装备'));
//        $grid->column('skills', __('Skills'));
//        $grid->column('actions', __('Actions'));
//        $grid->column('drops', __('Drops'));
//        $grid->actions(function ($actions) {
//            $tpls = DB::table('npc_wugong_tpls')->pluck('id', 'id');
//            foreach ($tpls as $tpl) {
//                $actions->add(new bkyj($tpl));
//            }
//        });
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
        $show = new Show(Npc::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('名称'));
        $show->field('description', __('描述'));
        $show->field('appearance', __('外貌'));
        $show->field('gender', __('性别'));
        $show->field('age', __('年龄'));
        $show->field('sect_id', __('门派'));
        $show->field('seniority', __('辈分'));
        $show->field('base_hp', __('基础气血'));
        $show->field('weapon', __('武器'));
        $show->field('clothes', __('衣服'));
        $show->field('armor', __('内甲'));
        $show->field('shoes', __('鞋子'));

        $show->field('base_jianfa_lv', __('基本剑法等级'));
        $show->field('base_daofa_lv', __('基本刀法等级'));
        $show->field('base_quanjiao_lv', __('基本拳脚等级'));
        $show->field('base_neigong_lv', __('基本内功等级'));
        $show->field('base_qinggong_lv', __('基本轻功等级'));
        $show->field('base_zhaojia_lv', __('基本招架等级'));
        $show->field('sect_qinggong_lv', __('门派轻功等级'));
        $show->field('sect_skill', __('门派技能'));
        $show->field('sect_skill_lv', __('门派技能等级'));

        $show->field('random_trick', __('招式是否随机'));

        $show->field('tech_skills', __('传授技能'));
        $show->field('actions', __('事件'));
        $show->field('drops', __('掉落'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Npc());

        $form->text('name', __('名称'));
        $form->text('alias', __('别名'));
        $form->textarea('description', __('描述'));
        $form->textarea('appearance', __('外貌'));

        $regions = ['0' => '无'] + Region::pluck('name', 'id')->toArray();
        $form->select('region_id', __('地区'))->options($regions);

        $ranks = ['0' => '无'] + NpcRank::pluck('name', 'id')->toArray();
        $form->select('rank_id', __('阶层'))->options($ranks);

        $form->select('gender', __('性别'))->options(['男' => '男', '女' => '女'])->default('男');
        $form->number('age', __('年龄'))->default(0);

        $sects = ['0' => '无'] + Sect::pluck('name', 'id')->toArray();
        $form->select('sect_id', __('门派'))->default(0)->options($sects);

        $form->number('seniority', __('辈分'))->default(0);
        $form->number('experience', __('修为'))->default(0);
        $form->number('base_hp', __('基础血量'))->default(480);
//        $form->number('drop_money', __('掉落铜钱数量'))->default(0);

//        Admin::script('console.log("hello world' . $form->name . '");');
//        $things = Thing::all()->pluck('name', 'id');
//        $form->select('drop1', __('掉落位1'))->options($things)->default(0);
//        $form->number('drop1_probability', __('掉落位1爆率'))->default(0)->help('填入概率的分子，整数，分母是固定的10000，填入1000则表示掉落概率为十分之一。');
//        $form->select('drop2', __('掉落位2'))->options($things)->default(0);
//        $form->number('drop2_probability', __('掉落位2爆率'))->default(0);
//        $form->select('drop3', __('掉落位3'))->options($things)->default(0);
//        $form->number('drop3_probability', __('掉落位3爆率'))->default(0);
//        $form->select('drop4', __('掉落位4'))->options($things)->default(0);
//        $form->number('drop4_probability', __('掉落位4爆率'))->default(0);
//        $form->select('drop5', __('掉落位5'))->options($things)->default(0);
//        $form->number('drop5_probability', __('掉落位5爆率'))->default(0);

        $weapons = ['0' => '无'] + DB::table('things')->whereIn('equipment_kind', [1, 2, 3])->pluck('name', 'id')->toArray();
        $form->select('weapon', __('武器'))->default(0)->options($weapons);
        $clothes = ['0' => '无'] + DB::table('things')->where('equipment_kind', 4)->pluck('name', 'id')->toArray();
        $form->select('clothes', __('衣服'))->default(0)->options($clothes);
        $armor = ['0' => '无'] + DB::table('things')->where('equipment_kind', 5)->pluck('name', 'id')->toArray();
        $form->select('armor', __('内甲'))->default(0)->options($armor);
        $shoes = ['0' => '无'] + DB::table('things')->where('equipment_kind', 6)->pluck('name', 'id')->toArray();
        $form->select('shoes', __('鞋子'))->default(0)->options($shoes);

        $wugong_tpls = ['0' => '无'] + NpcWugongTpl::pluck('name', 'id')->toArray();
        $form->select('wugong_tpl_id', __('武功模板'))->default(0)->options($wugong_tpls);

        $form->saved(function (Form $form) {
            if ($form->model()->wugong_tpl_id > 0) {
                $tpl = DB::table('npc_wugong_tpls')->where('id', $form->model()->wugong_tpl_id)->first();
                $model = Npc::where('id', $form->model()->id)->first();
                $model->rank_id = $tpl->rank_id;
                $model->experience = $tpl->experience;
                $model->base_jianfa_lv = $tpl->base_jianfa_lv;
                $model->base_daofa_lv = $tpl->base_daofa_lv;
                $model->base_quanjiao_lv = $tpl->base_quanjiao_lv;
                $model->base_neigong_lv = $tpl->base_neigong_lv;
                $model->base_qinggong_lv = $tpl->base_qinggong_lv;
                $model->base_zhaojia_lv = $tpl->base_zhaojia_lv;
                $model->sect_qinggong_lv = $tpl->sect_qinggong_lv;
                $model->sect_skill = $tpl->sect_skill;
                $model->sect_skill_lv = $tpl->sect_skill_lv;

                $model->save();
            }
        });
        $form->number('base_jianfa_lv', __('基本剑法等级'))->default(5);
        $form->number('base_daofa_lv', __('基本刀法等级'))->default(5);
        $form->number('base_quanjiao_lv', __('基本拳脚等级'))->default(5);
        $form->number('base_neigong_lv', __('基本内功等级'))->default(5);
        $form->number('base_qinggong_lv', __('基本轻功等级'))->default(5);
        $form->number('base_zhaojia_lv', __('基本招架等级'))->default(5);
        $form->number('sect_qinggong_lv', __('门派轻功等级'))->default(0);
        $skills = ['0' => '无'] + DB::table('skills')->where('is_base', '0')->pluck('name', 'id')->toArray();
        $form->select('sect_skill', __('门派技能'))->default(0)->options($skills);
        $form->number('sect_skill_lv', __('门派技能等级'))->default(0);
       

        $form->number('search_money', __('搜身铜钱数量'))->default(5);
        $things = ['0' => '无'] + DB::table('things')->pluck('name', 'id')->toArray();
        $form->select('search_thing', __('搜身物品'))->default(0)->options($things);

//        $form->switch('random_trick', __('招式是否随机'))->default(0)->states([
//            'on'  => ['value' => '1', 'text' => '是', 'color' => 'success'],
//            'off' => ['value' => '0', 'text' => '否', 'color' => 'danger'],
//        ])->help('随机使用已经解锁的最强的五个招式，否则使用解锁的最后一个招式。');


        $form->textarea('master_skills', __('传授技能'))->rules('nullable');
        $form->textarea('actions', __('事件'))->rules('nullable');
        $form->textarea('dialogues', __('对话'))->rules('nullable');

        return $form;
    }
}
