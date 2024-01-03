<?php

namespace App\Admin\Controllers;

use App\Models\XinfaTrick;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class XinfaTrickController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '心法招式管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new XinfaTrick());

        $grid->quickSearch('name');

        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', __('名称'));
        $xinfas = DB::table('xinfas')->pluck('name', 'id')->toArray();
        $grid->column('xinfa_id', __('所属心法'))->editable('select', $xinfas);
        $grid->column('lv', __('解锁等级'));
        $grid->column('mp', __('消耗内力'));
        $grid->column('damage', __('伤害'));
        $grid->column('damage_kind', __('伤害类型'))->editable('select', [
            '默认'  => '默认', '擦伤' => '擦伤', '割伤' => '割伤', '刺伤' => '刺伤', '瘀伤' => '瘀伤', '震伤' => '震伤',
            '内伤'  => '内伤', '点穴' => '点穴', '抽伤' => '抽伤', '反震伤' => '反震伤', '砸伤' => '砸伤',
            '劈砍伤' => '劈砍伤', '抓伤' => '抓伤', '洞穿伤' => '洞穿伤']);
        $grid->column('description', __('出招描述'));
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
        $show = new Show(XinfaTrick::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('名称'));
        $show->field('xinfa_id', __('所属心法 ID'));
        $show->field('lv', __('解锁等级'));
        $show->field('mp', __('消耗内力'));
        $show->field('damage', __('伤害'));
        $show->field('damage_kind', __('伤害类型'));
        $show->field('description', __('出招描述'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new XinfaTrick());

        $form->text('name', __('名称'));
        $xinfas = DB::table('xinfas')->pluck('name', 'id')->toArray();
        $form->select('xinfa_id', __('心法'))->options($xinfas);
        $form->number('lv', __('解锁等级'))->default(0);
        $form->number('mp', __('消耗内力'))->default(0);
        $form->number('damage', __('伤害'))->default(0);
        $form->select('damage_kind', __('伤害类型'))->default('默认')->options([
            '默认'  => '默认', '擦伤' => '擦伤', '割伤' => '割伤', '刺伤' => '刺伤', '瘀伤' => '瘀伤', '震伤' => '震伤',
            '内伤'  => '内伤', '点穴' => '点穴', '抽伤' => '抽伤', '反震伤' => '反震伤', '砸伤' => '砸伤',
            '劈砍伤' => '劈砍伤', '抓伤' => '抓伤', '洞穿伤' => '洞穿伤']);
        $form->textarea('description', __('出招描述'))->help('$M 自己 myself, $W 武器 weapon, $O 对手 opponent, $P 部位 position.');

        return $form;
    }
}
