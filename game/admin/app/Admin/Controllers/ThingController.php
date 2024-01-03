<?php

namespace App\Admin\Controllers;

use App\Models\EquipmentKind;
use App\Models\Thing;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ThingController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '普通物品管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Thing());

        $grid->quickSearch('name');

        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', __('名称'));
        $grid->column('description', __('描述'));
        $grid->column('kind', __('种类'))->editable('select', ['装备' => '装备', '药品' => '药品', '书籍' => '书籍', '其它' => '其它',])->sortable();

        $equipment_kinds = ['0' => '无',] + EquipmentKind::pluck('name', 'id')->toArray();
        $grid->column('equipment_kind', __('装备种类'))->editable('select', $equipment_kinds)->sortable();
        $grid->column('money', __('价值铜钱'));
        $grid->column('weight', __('重量'));
        $grid->column('unit', __('单位'));
        $grid->column('attack', __('攻击力'));
        $grid->column('defence', __('防御力'));
        $grid->column('dodge', __('闪避力'));
        $grid->column('is_no_drop', __('是否不掉落'))->switch([
            'on' => ['value' => '1', 'text' => '是', 'color' => 'success'],
            'off' => ['value' => '0', 'text' => '否', 'color' => 'danger'],
        ])->default('0');
        // $grid->column('is_no_depreciation', __('是否不磨损'))->switch([
        //     'on' => ['value' => '1', 'text' => '是', 'color' => 'success'],
        //     'off' => ['value' => '0', 'text' => '否', 'color' => 'danger'],
        // ])->default('0');
        // $grid->column('new_status', __('崭新度'));
        $grid->column('max_durability', __('最大耐久度'));
        $grid->column('hp', __('回复气血'));
        $grid->column('mp', __('回复内力'));
        $grid->column('jingshen', __('回复精神'));
         $grid->column('xiuwei', __('增加修为值'));
         $grid->column('qianneng', __('增加潜能值'));
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
        $show = new Show(Thing::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('名称'));
        $show->field('description', __('描述'));
        $show->field('kind', __('种类'));
        $show->field('equipment_kind', __('装备种类'));
        $show->field('money', __('价值铜钱'));
        $show->field('weight', __('重量'));
        $show->field('unit', __('单位'));
        $show->field('attack', __('攻击力'));
        $show->field('defence', __('防御力'));
        $show->field('dodge', __('闪避力'));
        $show->field('is_no_drop', __('是否不掉落'));
        // $show->field('is_no_depreciation', __('是否不磨损'));
        // $show->field('new_status', __('崭新度'));
        $show->field('max_durability', __('最大耐久度'));
        $show->field('hp', __('回复气血'));
        $show->field('mp', __('回复内力'));
        $show->field('jingshen', __('回复精神'));
        $show->field('xiuwei', __('增加修为值'));
        $show->field('qianneng', __('增加潜能值'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Thing());

        $form->text('name', __('名称'));
        $form->textarea('description', __('描述'));
        $form->select('kind', __('种类'))->options([
            '装备' => '装备', '药品' => '药品', '书籍' => '书籍', '其它' => '其它',
        ])->default('其它');
        $equipment_kinds = ['0' => '无',] + EquipmentKind::pluck('name', 'id')->toArray();
        $form->select('equipment_kind', __('装备种类'))->options($equipment_kinds)->default('无');
        $form->number('money', __('价值铜钱'))->default(0);
        $form->number('weight', __('重量'))->help('一文铜钱重量是1, 总负重是100000000.');
        $form->text('unit', __('单位'))->default('个');
        $form->number('attack', __('攻击力'))->default(0);
        $form->number('defence', __('防御力'))->default(0);
        $form->number('dodge', __('闪避力'))->default(0);
        $form->switch('is_no_drop', __('是否不掉落'))->states([
            'on' => ['value' => '1', 'text' => '是', 'color' => 'success'],
            'off' => ['value' => '0', 'text' => '否', 'color' => 'danger'],
        ])->default('0');;
        // $form->switch('is_no_depreciation', __('是否不磨损'))->states([
        //     'on' => ['value' => '1', 'text' => '是', 'color' => 'success'],
        //     'off' => ['value' => '0', 'text' => '否', 'color' => 'danger'],
        // ])->default('0');;
        // $form->number('new_status', __('崭新度'))->default(0);
        $form->number('max_durability', __('最大耐久度'))->default(0);
        $form->number('hp', __('回复气血'))->default(0);
        $form->number('mp', __('回复内力'))->default(0);
        $form->number('jingshen', __('回复精神'))->default(0);
        $form->number('xiuwei', __('增加修为值'))->default(0);
        $form->number('qianneng', __('增加潜能值'))->default(0);

        return $form;
    }
}
