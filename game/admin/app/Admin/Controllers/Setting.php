<?php

namespace App\Admin\Controllers;

use Encore\Admin\Admin;
use Encore\Admin\Widgets\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Setting extends Form
{
    /**
     * The form title.
     *
     * @var string
     */
    public $title = '游戏设置';

    /**
     * Handle the form request.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request)
    {
//        dump($request->all());
        DB::table('settings')->where('item', 'thing_drop_probability')
            ->update(['value' => $request->post('thing_drop_probability')]);
        DB::table('settings')->where('item', 'xinfa_drop_probability')
            ->update(['value' => $request->post('xinfa_drop_probability')]);
        DB::table('settings')->where('item', 'box_drop_probability')
            ->update(['value' => $request->post('box_drop_probability')]);
        DB::table('settings')->where('item', 'money_ratio')
            ->update(['value' => $request->post('money_ratio')]);
        DB::table('settings')->where('item', 'qianneng_ratio')
            ->update(['value' => $request->post('qianneng_ratio')]);
        DB::table('settings')->where('item', 'experience_ratio')
            ->update(['value' => $request->post('experience_ratio')]);
        DB::table('settings')->where('item', 'xinfa_experience_ratio')
            ->update(['value' => $request->post('xinfa_experience_ratio')]);
        DB::table('settings')->where('item', 'index_notice')
            ->update(['value' => $request->post('index_notice')]);
        DB::table('settings')->where('item', 'game_notice')
            ->update(['value' => $request->post('game_notice')]);
        admin_success('修改成功！');

        return back();
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->number('thing_drop_probability', __('普通物品爆率'))->rules('required')->default(80000)->help('1000000为分母，1%则填10000，50%则填500000.');
        $this->number('xinfa_drop_probability', __('心法爆率'))->rules('required')->default(10)->help('1000000为分母，1%则填10000，50%则填500000.');
        $this->number('box_drop_probability', __('宝箱爆率'))->rules('required')->default(1000)->help('1000000为分母，1%则填10000，50%则填500000.');
        $this->number('money_ratio', __('金钱掉落倍率'))->rules('required')->default(1000000)->help('1000000为分母，1倍则填1000000，1.5倍则填1500000，2倍则填2000000.');
        $this->number('qianneng_ratio', __('潜能掉落倍率'))->rules('required')->default(1000000)->help('1000000为分母，1倍则填1000000，1.5倍则填1500000，2倍则填2000000.');
        $this->number('experience_ratio', __('修为掉落倍率'))->rules('required')->default(1000000)->help('1000000为分母，1倍则填1000000，1.5倍则填1500000，2倍则填2000000.');
        $this->number('xinfa_experience_ratio', __('修炼心法经验倍率'))->rules('required')->default(1000000)->help('1000000为分母，1倍则填1000000，1.5倍则填1500000，2倍则填2000000.');
        $this->textarea('index_notice', __('游戏首页公告'))->default('')->help('首页显示公告');
        $this->textarea('game_notice', __('游戏内公告'))->default('')->help('游戏内显示公告');
    }

    /**
     * The data of the form.
     *
     * @return array $data
     */
    public function data()
    {
        $settings = DB::table('settings')->pluck('value', 'item')->toArray();
        return [
            'thing_drop_probability' => $settings['thing_drop_probability'],
            'xinfa_drop_probability' => $settings['xinfa_drop_probability'],
            'box_drop_probability'   => $settings['box_drop_probability'],
            'money_ratio'            => $settings['money_ratio'],
            'qianneng_ratio'         => $settings['qianneng_ratio'],
            'experience_ratio'       => $settings['experience_ratio'],
            'xinfa_experience_ratio' => $settings['xinfa_experience_ratio'],
            'index_notice'           => $settings['index_notice'],
            'game_notice'            => $settings['game_notice'],
        ];
    }
}
