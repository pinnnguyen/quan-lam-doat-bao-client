<?php

namespace App\Http\Controllers\Role;

use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 个人状态
 */
class StatusController
{
    /**
     * 技能列表
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        if ($request->roleRow->double_experience > time()) {
            $double_experience_time = $request->roleRow->double_experience - time();
        }
        if ($request->roleRow->triple_experience > time()) {
            $triple_experience_time = $request->roleRow->triple_experience - time();
        }
        if ($request->roleRow->double_qianneng > time()) {
            $double_qianneng_time = $request->roleRow->double_qianneng - time();
        }
        if ($request->roleRow->triple_qianneng > time()) {
            $triple_qianneng_time = $request->roleRow->triple_qianneng - time();
        }
        if ($request->roleRow->double_xinfa > time()) {
            $double_xinfa_time = $request->roleRow->double_xinfa - time();
        }
        if ($request->roleRow->triple_xinfa > time()) {
            $triple_xinfa_time = $request->roleRow->triple_xinfa - time();
        }
        if ($request->roleRow->vip_double_time > time()) {
            $vip_double_time = $request->roleRow->vip_double_time - time();
        }
        if ($request->roleRow->no_kill > time()) {
            $no_kill_time = $request->roleRow->no_kill - time();
        }
        return $connection->send(\cache_response($request, \view('Role/Status/index.twig', [
            'request'                => $request,
            'role_attrs'             => Helpers::getRoleAttrsByRoleId($request->roleId),
            'double_qianneng_time'   => $double_qianneng_time ?? 0,
            'triple_qianneng_time'   => $triple_qianneng_time ?? 0,
            'double_experience_time' => $double_experience_time ?? 0,
            'triple_experience_time' => $triple_experience_time ?? 0,
            'double_xinfa_time'      => $double_xinfa_time ?? 0,
            'triple_xinfa_time'      => $triple_xinfa_time ?? 0,
            'vip_double_time'        => $vip_double_time ?? 0,
            'no_kill_time'           => $no_kill_time ?? 0,
        ])));
    }


    /**
     * Tu luyện Nội lực
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function mp(TcpConnection $connection, Request $request)
    {
        return $connection->send(\cache_response($request, \view('Role/Status/mp.twig', [
            'request' => $request,
        ])));
    }


    /**
     * 提交
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $continue
     *
     * @return bool|null
     */
    public function mpPost(TcpConnection $connection, Request $request, int $continue = 0)
    {
        if ($continue === 0) {
            if (strtoupper($request->method()) !== 'POST') {
                return $connection->send(\cache_response($request, \view('Base/message.twig', [
                    'request' => $request,
                    'message' => Helpers::randomSentence(),
                ])));
            }
            $hp = trim($request->post('hp'));
            if (empty($hp) or !is_numeric($hp)) {
                return $connection->send(\cache_response($request, \view('Base/message.twig', [
                    'request' => $request,
                    'message' => Helpers::randomSentence(),
                ])));
            }

            $hp = intval($hp);
            if ($hp < 20) {
                return $connection->send(\cache_response($request, \view('Role/Status/mpMessage.twig', [
                    'request' => $request,
                    'message' => 'Ngươi nội công còn không có đạt tới cái kia cảnh giới, ít nhất phải tốn 20 điểm “Khí” mới có thể luyện công.',
                ])));
            }
        } else {
            $hp = 20;
        }
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->hp < $hp) {
            return $connection->send(\cache_response($request, \view('Role/Status/mpMessage.twig', [
                'request' => $request,
                'message' => 'Ngươi hiện tại khí huyết không đủ, vô pháp sinh ra nội tức vận hành toàn thân kinh mạch.',
            ])));
        }
        $role_attrs->hp -= $hp;

        if (($role_attrs->maxMp - $role_attrs->mp) / $role_attrs->maxMp > 0.2) {
            $role_attrs->mp += ceil($role_attrs->maxMp * 0.2);
        } elseif ($role_attrs->mp < $role_attrs->maxMp) {
            $role_attrs->mp = $role_attrs->maxMp;
        } else {
            $role_attrs->mp = $role_attrs->maxMp * 2;
        }

        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);

        if ($role_attrs->mp >= $role_attrs->maxMp * 2) {
            return $connection->send(\cache_response($request, \view('Role/Status/mpOver.twig', [
                'request' => $request,
            ])));
        }

        return $connection->send(\cache_response($request, \view('Role/Status/mpPost.twig', [
            'request'    => $request,
            'role_attrs' => $role_attrs,
        ])));
    }
}
