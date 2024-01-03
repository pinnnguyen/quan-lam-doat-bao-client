<?php

namespace App\Admin\Controllers;

use App\Models\Sect;
use App\Models\Skill;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class SkillController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '技能管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Skill());

        $grid->quickSearch('name');

        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', __('名称'));
        $grid->column('description', __('描述'));

        $sects = ['0' => '无'] + Sect::pluck('name', 'id')->toArray();
        $grid->column('sect_id', __('门派'))->editable('select', $sects)->sortable();
        $grid->column('kind', __('种类'))->editable('select', [
            '剑法' => '剑法', '刀法' => '刀法', '内功' => '内功', '招架' => '招架', '拳脚' => '拳脚', '轻功' => '轻功',
            '杖法' => '杖法', '斧法' => '斧法', '扇法' => '扇法', '棒法' => '棒法',
        ]);
        $grid->column('is_base', __('基础技能'))->switch([
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
        $show = new Show(Skill::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('名称'));
        $show->field('description', __('描述'));
        $show->field('sect_id', __('门派'));
        $show->field('kind', __('种类'));
        $show->field('is_base', __('基础技能'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Skill());

        $form->text('name', __('名称'));
        $form->textarea('description', __('描述'));
        $sects = ['0' => '无'] + Sect::pluck('name', 'id')->toArray();
        $form->select('sect_id', __('门派'))->options($sects)->default(0);
        $form->select('kind', __('种类'))->options([
            '剑法' => '剑法', '刀法' => '刀法', '内功' => '内功', '招架' => '招架', '拳脚' => '拳脚', '轻功' => '轻功',
            '杖法' => '杖法', '斧法' => '斧法', '扇法' => '扇法', '棒法' => '棒法',
        ]);
        $form->select('is_base', __('基础技能'))->options(['0' => '否', '1' => '是'])->default('0');

        $form->text('lv5_name', __('第1招名称'));
        $form->number('lv5_damage', __('第1招额外伤害'))->default(0);
        $form->textarea('lv5_action_description', __('第1招动作描述'))->help('$M表示自己(Myself), $W表示武器(Weapon), $O表示对手(Opponent), $P表示部位(position).');
        $form->textarea('lv5_result_description', __('第1招结果描述'))->help('$M表示自己(Myself), $W表示武器(Weapon), $O表示对手(Opponent), $P表示部位(position).');

        $form->text('lv10_name', __('第2招名称'));
        $form->number('lv10_damage', __('第2招额外伤害'))->default(0);
        $form->textarea('lv10_action_description', __('第2招动作描述'));
        $form->textarea('lv10_result_description', __('第2招结果描述'));
        
        $form->text('lv20_name', __('第3招名称'));
        $form->number('lv20_damage', __('第3招额外伤害'))->default(0);
        $form->textarea('lv20_action_description', __('第3招动作描述'));
        $form->textarea('lv20_result_description', __('第3招结果描述'));

        $form->text('lv40_name', __('第4招名称'));
        $form->number('lv40_damage', __('第4招额外伤害'))->default(0);
        $form->textarea('lv40_action_description', __('第4招动作描述'));
        $form->textarea('lv40_result_description', __('第4招结果描述'));


        $form->text('lv80_name', __('第5招名称'));
        $form->number('lv80_damage', __('第5招额外伤害'))->default(0);
        $form->textarea('lv80_action_description', __('第5招动作描述'));
        $form->textarea('lv80_result_description', __('第5招结果描述'));


        $form->text('lv120_name', __('第6招名称'));
        $form->number('lv120_damage', __('第6招额外伤害'))->default(0);
        $form->textarea('lv120_action_description', __('第6招动作描述'));
        $form->textarea('lv120_result_description', __('第6招结果描述'));


        $form->text('lv160_name', __('第7招名称'));
        $form->number('lv160_damage', __('第7招额外伤害'))->default(0);
        $form->textarea('lv160_action_description', __('第7招动作描述'));
        $form->textarea('lv160_result_description', __('第7招结果描述'));


        $form->text('lv180_name', __('第8招名称'));
        $form->number('lv180_damage', __('第8招额外伤害'))->default(0);
        $form->textarea('lv180_action_description', __('第8招动作描述'));
        $form->textarea('lv180_result_description', __('第8招结果描述'));


        $form->text('lv240_name', __('第9招名称'));
        $form->number('lv240_damage', __('第9招额外伤害'))->default(0);
        $form->textarea('lv240_action_description', __('第9招动作描述'));
        $form->textarea('lv240_result_description', __('第9招结果描述'));


        $form->text('lv300_name', __('第10招名称'));
        $form->number('lv300_damage', __('第10招额外伤害'))->default(0);
        $form->textarea('lv300_action_description', __('第10招动作描述'));
        $form->textarea('lv300_result_description', __('第10招结果描述'));


        $form->text('lv360_name', __('第11招名称'));
        $form->number('lv360_damage', __('第11招额外伤害'))->default(0);
        $form->textarea('lv360_action_description', __('第11招动作描述'));
        $form->textarea('lv360_result_description', __('第11招结果描述'));


        $form->text('lv420_name', __('第12招名称'));
        $form->number('lv420_damage', __('第12招额外伤害'))->default(0);
        $form->textarea('lv420_action_description', __('第12招动作描述'));
        $form->textarea('lv420_result_description', __('第12招结果描述'));


        $form->text('lv480_name', __('第13招名称'));
        $form->number('lv480_damage', __('第13招额外伤害'))->default(0);
        $form->textarea('lv480_action_description', __('第13招动作描述'));
        $form->textarea('lv480_result_description', __('第13招结果描述'));
        
        $form->text('lv700_name', __('第14招名称'));
        $form->number('lv700_damage', __('第14招额外伤害'))->default(0);
        $form->textarea('lv700_action_description', __('第14招动作描述'));
        $form->textarea('lv700_result_description', __('第14招结果描述'));
        
        $form->text('lv1000_name', __('第15招名称'));
        $form->number('lv1000_damage', __('第15招额外伤害'))->default(0);
        $form->textarea('lv1000_action_description', __('第15招动作描述'));
        $form->textarea('lv1000_result_description', __('第15招结果描述'));


        return $form;
    }
}
