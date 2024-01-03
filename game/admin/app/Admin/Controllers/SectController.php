<?php

namespace App\Admin\Controllers;

use App\Models\Sect;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class SectController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '门派管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Sect());

        $grid->column('id', __('ID'));
        $grid->column('name', __('名称'));

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
        $show = new Show(Sect::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Sect());

        $form->text('name', __('名称'));

        return $form;
    }
}
