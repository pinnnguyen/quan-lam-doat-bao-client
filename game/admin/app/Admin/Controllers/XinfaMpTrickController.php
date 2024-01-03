<?php

namespace App\Admin\Controllers;

use App\Models\XinfaMpTrick;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class XinfaMpTrickController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '内功心法';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new XinfaMpTrick());

        $grid->column('id', __('Id'));
        $xinfas = DB::table('xinfas')->where('kind', '内功')->pluck('name', 'id')->toArray();
        $grid->column('xinfa_id', __('所属心法'))->editable('select', $xinfas);

        $grid->column('lv0_mp', __('0级额外内力'));
        $grid->column('lv40_mp', __('40级额外内力'));
        $grid->column('lv80_mp', __('80级额外内力'));
        $grid->column('lv160_mp', __('160级额外内力'));
        $grid->column('lv240_mp', __('240级额外内力'));
        $grid->column('lv400_mp', __('400级额外内力'));
        $grid->column('lv560_mp', __('560级额外内力'));
        $grid->column('lv720_mp', __('720级额外内力'));
        $grid->column('lv880_mp', __('880级额外内力'));
        $grid->column('lv1000_mp', __('1000级额外内力'));
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
        $show = new Show(XinfaMpTrick::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('xinfa_id', __('Xinfa id'));
        $show->field('lv0_hp', __('Lv0 hp'));
        $show->field('lv40_hp', __('Lv40 hp'));
        $show->field('lv80_hp', __('Lv80 hp'));
        $show->field('lv160_hp', __('Lv160 hp'));
        $show->field('lv240_hp', __('Lv240 hp'));
        $show->field('lv400_hp', __('Lv400 hp'));
        $show->field('lv560_hp', __('Lv560 hp'));
        $show->field('lv720_hp', __('Lv720 hp'));
        $show->field('lv880_hp', __('Lv880 hp'));
        $show->field('lv1000_hp', __('Lv1000 hp'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new XinfaMpTrick());

        $mp_xinfas = DB::table('xinfas')->where('kind', '内功')->pluck('name', 'id')->toArray();
        $form->select('xinfa_id', __('所属心法'))->options($mp_xinfas);

        $form->number('lv0_mp', __('0级额外内力'))->default(0);
        $form->number('lv40_mp', __('40级额外内力'))->default(0);
        $form->number('lv80_mp', __('80级额外内力'))->default(0);
        $form->number('lv160_mp', __('160级额外内力'))->default(0);
        $form->number('lv240_mp', __('240级额外内力'))->default(0);
        $form->number('lv400_mp', __('400级额外内力'))->default(0);
        $form->number('lv560_mp', __('560级额外内力'))->default(0);
        $form->number('lv720_mp', __('720级额外内力'))->default(0);
        $form->number('lv880_mp', __('880级额外内力'))->default(0);
        $form->number('lv1000_mp', __('1000级额外内力'))->default(0);

        return $form;
    }
}
