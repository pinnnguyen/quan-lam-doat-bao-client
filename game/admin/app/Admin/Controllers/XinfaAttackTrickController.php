<?php

namespace App\Admin\Controllers;

use App\Models\XinfaAttackTrick;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class XinfaAttackTrickController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '攻击心法招式';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new XinfaAttackTrick());

        $grid->column('id', __('ID'));

        $xinfas = DB::table('xinfas')->where('kind','攻击')->pluck('name', 'id')->toArray();
        $grid->column('xinfa_id', __('所属心法'))->editable('select', $xinfas);
        $grid->column('lv0_name', __('0级招式名称'));
        $grid->column('lv0_damage', __('0级招式额外伤害'));
        $grid->column('lv0_description', __('0级招式描述'));

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
        $show = new Show(XinfaAttackTrick::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('xinfa_id', __('所属心法'));
        $show->field('lv0_name', __('Lv0 name'));
        $show->field('lv0_damage', __('Lv0 damage'));
        $show->field('lv0_description', __('Lv0 description'));
        $show->field('lv40_name', __('Lv40 name'));
        $show->field('lv40_damage', __('Lv40 damage'));
        $show->field('lv40_description', __('Lv40 description'));
        $show->field('lv80_name', __('Lv80 name'));
        $show->field('lv80_damage', __('Lv80 damage'));
        $show->field('lv80_description', __('Lv80 description'));
        $show->field('lv160_name', __('Lv160 name'));
        $show->field('lv160_damage', __('Lv160 damage'));
        $show->field('lv160_description', __('Lv160 description'));
        $show->field('lv240_name', __('Lv240 name'));
        $show->field('lv240_damage', __('Lv240 damage'));
        $show->field('lv240_description', __('Lv240 description'));
        $show->field('lv400_name', __('Lv400 name'));
        $show->field('lv400_damage', __('Lv400 damage'));
        $show->field('lv400_description', __('Lv400 description'));
        $show->field('lv560_name', __('Lv560 name'));
        $show->field('lv560_damage', __('Lv560 damage'));
        $show->field('lv560_description', __('Lv560 description'));
        $show->field('lv720_name', __('Lv720 name'));
        $show->field('lv720_damage', __('Lv720 damage'));
        $show->field('lv720_description', __('Lv720 description'));
        $show->field('lv880_name', __('Lv880 name'));
        $show->field('lv880_damage', __('Lv880 damage'));
        $show->field('lv880_description', __('Lv880 description'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new XinfaAttackTrick());

        $attack_xinfas = DB::table('xinfas')->where('kind','攻击')->pluck('name', 'id')->toArray();
        $form->select('xinfa_id', __('所属心法'))->options($attack_xinfas);

        $form->text('lv0_name', __('0级招式名称'))->default('');
        $form->number('lv0_damage', __('0级招式额外伤害'))->default(0);
//        $form->textarea('lv0_description', __('0级招式描述'))->help('$M表示自己(Myself), $W表示武器(Weapon), $O表示对手(Opponent), $P表示部位(position).');

        $form->text('lv40_name', __('40级招式名称'))->default('');
        $form->number('lv40_damage', __('40级招式额外伤害'))->default(0);
//        $form->textarea('lv40_description', __('40级招式描述'));


        $form->text('lv80_name', __('80级招式名称'))->default('');
        $form->number('lv80_damage', __('80级招式额外伤害'))->default(0);
//        $form->textarea('lv80_description', __('80级招式描述'));


        $form->text('lv160_name', __('160级招式名称'))->default('');
        $form->number('lv160_damage', __('160级招式额外伤害'))->default(0);
//        $form->textarea('lv160_description', __('160级招式描述'));


        $form->text('lv240_name', __('240级招式名称'))->default('');
        $form->number('lv240_damage', __('240级招式额外伤害'))->default(0);
//        $form->textarea('lv240_description', __('240级招式描述'));


        $form->text('lv400_name', __('400级招式名称'))->default('');
        $form->number('lv400_damage', __('400级招式额外伤害'))->default(0);
//        $form->textarea('lv400_description', __('400级招式描述'));


        $form->text('lv560_name', __('560级招式名称'))->default('');
        $form->number('lv560_damage', __('560级招式额外伤害'))->default(0);
//        $form->textarea('lv560_description', __('560级招式描述'));


        $form->text('lv720_name', __('720级招式名称'))->default('');
        $form->number('lv720_damage', __('720级招式额外伤害'))->default(0);
//        $form->textarea('lv720_description', __('720级招式描述'));


        $form->text('lv880_name', __('880级招式名称'))->default('');
        $form->number('lv880_damage', __('880级招式额外伤害'))->default(0);
//        $form->textarea('lv880_description', __('880级招式描述'));



        return $form;
    }
}
