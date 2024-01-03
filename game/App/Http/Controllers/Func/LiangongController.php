<?php

namespace App\Http\Controllers\Func;

use App\Libs\Attrs\FlushRoleAttrs;
use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 练功房
 *
 */
class LiangongController
{
    /**
     * 贪
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function tan(TcpConnection $connection, Request $request)
    {
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        // if ($role_attrs->weaponRoleThingId === 0) {
        //     return $connection->send(\cache_response($request, \view('Base/message.twig', [
        //         'request' => $request,
        //         'message' => '没有武器怎么能练好Cơ bản chống đỡ呢？',
        //     ])));
        // }

        if ($role_attrs->hp < 100) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi khí huyết không đủ, sắc mặt tái nhợt, như thế nào tiếp tục luyện công?',
            ])));
        }

        $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` = 4;
SQL;

        $role_skill = Helpers::queryFetchObject($sql);
        if (!is_object($role_skill)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $role_skill->row = Helpers::getSkillRowBySkillId(4);
        if ($role_skill->lv < 60) {
            return $connection->send(\cache_response($request, \view('Base/messages.twig', [
                'request'  => $request,
                'messages' => [
                    'Trong bóng đêm, đột nhiên bên tai truyền đến “Xuy xuy” tiếng xé gió!',
                    'Nguyên lai là phòng luyện công trung cơ quan phóng ra ra ám khí.',
                    'Ngươi căn bản không kịp phản ứng đã bị đánh trúng, xem ra ngươi cơ bản chống đỡ võ công còn chưa đủ hỏa hậu, không thích hợp ở chỗ này tu luyện.',
                ],
            ])));
        }
        if ($role_skill->lv >= 75) {
            return $connection->send(\cache_response($request, \view('Base/messages.twig', [
                'request'  => $request,
                'messages' => [
                    'Trong bóng đêm, đột nhiên bên tai truyền đến “Xuy xuy” tiếng xé gió!',
                    'Ngươi tay mắt lanh lẹ, không chút hoang mang mà tránh thoát sở hữu ám khí.',
                    'Giây lát gian, lại là một vòng ám khí bắn ra, ngươi như cũ không chút để ý mà tránh thoát sở hữu ám khí……',
                    'Xem ra ngươi chống đỡ đã có chút thành tựu, nơi này đã không thích hợp ngươi tu luyện.',
                ],
            ])));
        }

        if (Helpers::getProbability(1, 3)) {
            return $connection->send(\cache_response($request, \view('Func/Liangong/tanFailed.twig', [
                'request' => $request,
            ])));
        }

        /**
         * 减少气血
         *
         */
        $role_attrs->hp -= 100;
        $role_attrs->hp = $role_attrs->hp < 0 ? 0 : $role_attrs->hp;
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);

        /**
         * 提升技能等级
         *
         */
        $role_skill->is_base = true;
        $experience = $role_skill->experience;
        $lv = $role_skill->lv;
        $up_exp = Helpers::getSkillExp($role_skill);
        $experience += intval($up_exp / 1000);
        $sql = '';
        $upgraded = false;
        if ($experience >= $up_exp) {
            $experience -= $up_exp;
            $lv += 1;
            $role_skill->lv = $lv;
            $up_exp = Helpers::getSkillExp($role_skill);
            $upgraded = true;
        }
        $sql .= <<<SQL
UPDATE `role_skills` SET `experience` = $experience, `lv` = $lv WHERE `id` = $role_skill->id;
SQL;

        Helpers::execSql($sql);
        if ($upgraded) {
            FlushRoleAttrs::fromRoleSkillByRoleId($request->roleId);
        }
        return $connection->send(\cache_response($request, \view('Func/Liangong/tan.twig', [
            'request'    => $request,
            'role_skill' => $role_skill,
            'lv'         => $lv,
            'percent'    => sprintf('%.1f', $experience / $up_exp * 100),
        ])));
    }


    /**
     * 色
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function se(TcpConnection $connection, Request $request)
    {
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        // if ($role_attrs->weaponRoleThingId === 0) {
        //     return $connection->send(\cache_response($request, \view('Base/message.twig', [
        //         'request' => $request,
        //         'message' => '没有武器怎么能练好Cơ bản chống đỡ呢？',
        //     ])));
        // }

        if ($role_attrs->hp < 100) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi khí huyết không đủ, sắc mặt tái nhợt, như thế nào tiếp tục luyện công?',
            ])));
        }

        $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` = 4;
SQL;

        $role_skill = Helpers::queryFetchObject($sql);
        if (!is_object($role_skill)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $role_skill->row = Helpers::getSkillRowBySkillId(4);
        if ($role_skill->lv < 75) {
            return $connection->send(\cache_response($request, \view('Base/messages.twig', [
                'request'  => $request,
                'messages' => [
                    'Trong bóng đêm, đột nhiên bên tai truyền đến “Xuy xuy” tiếng xé gió!',
                    'Nguyên lai là phòng luyện công trung cơ quan phóng ra ra ám khí.',
                    'Ngươi căn bản không kịp phản ứng đã bị đánh trúng, xem ra ngươi cơ bản chống đỡ võ công còn chưa đủ hỏa hậu, không thích hợp ở chỗ này tu luyện.',
                ],
            ])));
        }
        if ($role_skill->lv >= 90) {
            return $connection->send(\cache_response($request, \view('Base/messages.twig', [
                'request'  => $request,
                'messages' => [
                    'Trong bóng đêm, đột nhiên bên tai truyền đến “Xuy xuy” tiếng xé gió!',
                    'Ngươi tay mắt lanh lẹ, không chút hoang mang mà tránh thoát sở hữu ám khí.',
                    'Giây lát gian, lại là một vòng ám khí bắn ra, ngươi như cũ không chút để ý mà tránh thoát sở hữu ám khí……',
                    'Xem ra ngươi chống đỡ đã có chút thành tựu, nơi này đã không thích hợp ngươi tu luyện.',
                ],
            ])));
        }

        if (Helpers::getProbability(1, 3)) {
            return $connection->send(\cache_response($request, \view('Func/Liangong/seFailed.twig', [
                'request' => $request,
            ])));
        }

        /**
         * 减少气血
         *
         */
        $role_attrs->hp -= 100;
        $role_attrs->hp = $role_attrs->hp < 0 ? 0 : $role_attrs->hp;
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);

        /**
         * 提升技能等级
         *
         */
        $role_skill->is_base = true;
        $experience = $role_skill->experience;
        $lv = $role_skill->lv;
        $up_exp = Helpers::getSkillExp($role_skill);
        $experience += intval($up_exp / 1000);
        $sql = '';
        $upgraded = false;
        if ($experience >= $up_exp) {
            $experience -= $up_exp;
            $lv += 1;
            $role_skill->lv = $lv;
            $up_exp = Helpers::getSkillExp($role_skill);
            $upgraded = true;
        }
        $sql .= <<<SQL
UPDATE `role_skills` SET `experience` = $experience, `lv` = $lv WHERE `id` = $role_skill->id;
SQL;

        Helpers::execSql($sql);
        if ($upgraded) {
            FlushRoleAttrs::fromRoleSkillByRoleId($request->roleId);
        }
        return $connection->send(\cache_response($request, \view('Func/Liangong/se.twig', [
            'request'    => $request,
            'role_skill' => $role_skill,
            'lv'         => $lv,
            'percent'    => sprintf('%.1f', $experience / $up_exp * 100),
        ])));
    }


    /**
     * 妒
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function du(TcpConnection $connection, Request $request)
    {
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        // if ($role_attrs->weaponRoleThingId === 0) {
        //     return $connection->send(\cache_response($request, \view('Base/message.twig', [
        //         'request' => $request,
        //         'message' => '没有武器怎么能练好Cơ bản chống đỡ呢？',
        //     ])));
        // }

        if ($role_attrs->hp < 100) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi khí huyết không đủ, sắc mặt tái nhợt, như thế nào tiếp tục luyện công?',
            ])));
        }

        $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` = 4;
SQL;

        $role_skill = Helpers::queryFetchObject($sql);
        if (!is_object($role_skill)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $role_skill->row = Helpers::getSkillRowBySkillId(4);
        if ($role_skill->lv < 90) {
            return $connection->send(\cache_response($request, \view('Base/messages.twig', [
                'request'  => $request,
                'messages' => [
                    'Trong bóng đêm, đột nhiên bên tai truyền đến “Xuy xuy” tiếng xé gió!',
                    'Nguyên lai là phòng luyện công trung cơ quan phóng ra ra ám khí.',
                    'Ngươi căn bản không kịp phản ứng đã bị đánh trúng, xem ra ngươi cơ bản chống đỡ võ công còn chưa đủ hỏa hậu, không thích hợp ở chỗ này tu luyện.',
                ],
            ])));
        }
        if ($role_skill->lv >= 105) {
            return $connection->send(\cache_response($request, \view('Base/messages.twig', [
                'request'  => $request,
                'messages' => [
                    'Trong bóng đêm, đột nhiên bên tai truyền đến “Xuy xuy” tiếng xé gió!',
                    'Ngươi tay mắt lanh lẹ, không chút hoang mang mà tránh thoát sở hữu ám khí.',
                    'Giây lát gian, lại là một vòng ám khí bắn ra, ngươi như cũ không chút để ý mà tránh thoát sở hữu ám khí……',
                    'Xem ra ngươi chống đỡ đã có chút thành tựu, nơi này đã không thích hợp ngươi tu luyện.',
                ],
            ])));
        }

        if (Helpers::getProbability(1, 3)) {
            return $connection->send(\cache_response($request, \view('Func/Liangong/duFailed.twig', [
                'request' => $request,
            ])));
        }

        /**
         * 减少气血
         *
         */
        $role_attrs->hp -= 100;
        $role_attrs->hp = $role_attrs->hp < 0 ? 0 : $role_attrs->hp;
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);

        /**
         * 提升技能等级
         *
         */
        $role_skill->is_base = true;
        $experience = $role_skill->experience;
        $lv = $role_skill->lv;
        $up_exp = Helpers::getSkillExp($role_skill);
        $experience += intval($up_exp / 1000);
        $sql = '';
        $upgraded = false;
        if ($experience >= $up_exp) {
            $experience -= $up_exp;
            $lv += 1;
            $role_skill->lv = $lv;
            $up_exp = Helpers::getSkillExp($role_skill);
            $upgraded = true;
        }
        $sql .= <<<SQL
UPDATE `role_skills` SET `experience` = $experience, `lv` = $lv WHERE `id` = $role_skill->id;
SQL;

        Helpers::execSql($sql);
        if ($upgraded) {
            FlushRoleAttrs::fromRoleSkillByRoleId($request->roleId);
        }
        return $connection->send(\cache_response($request, \view('Func/Liangong/du.twig', [
            'request'    => $request,
            'role_skill' => $role_skill,
            'lv'         => $lv,
            'percent'    => sprintf('%.1f', $experience / $up_exp * 100),
        ])));
    }


    /**
     * 嗔
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function chen(TcpConnection $connection, Request $request)
    {
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        // if ($role_attrs->weaponRoleThingId === 0) {
        //     return $connection->send(\cache_response($request, \view('Base/message.twig', [
        //         'request' => $request,
        //         'message' => '没有武器怎么能练好Cơ bản chống đỡ呢？',
        //     ])));
        // }

        if ($role_attrs->hp < 100) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi khí huyết không đủ, sắc mặt tái nhợt, như thế nào tiếp tục luyện công?',
            ])));
        }

        $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` = 4;
SQL;

        $role_skill = Helpers::queryFetchObject($sql);
        if (!is_object($role_skill)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $role_skill->row = Helpers::getSkillRowBySkillId(4);
        if ($role_skill->lv < 130) {
            return $connection->send(\cache_response($request, \view('Base/messages.twig', [
                'request'  => $request,
                'messages' => [
                    'Trong bóng đêm, đột nhiên bên tai truyền đến “Xuy xuy” tiếng xé gió!',
                    'Nguyên lai là phòng luyện công trung cơ quan phóng ra ra ám khí.',
                    'Ngươi căn bản không kịp phản ứng đã bị đánh trúng, xem ra ngươi cơ bản chống đỡ võ công còn chưa đủ hỏa hậu, không thích hợp ở chỗ này tu luyện.',
                ],
            ])));
        }
        if ($role_skill->lv >= 150) {
            return $connection->send(\cache_response($request, \view('Base/messages.twig', [
                'request'  => $request,
                'messages' => [
                    'Trong bóng đêm, đột nhiên bên tai truyền đến “Xuy xuy” tiếng xé gió!',
                    'Ngươi tay mắt lanh lẹ, không chút hoang mang mà tránh thoát sở hữu ám khí.',
                    'Giây lát gian, lại là một vòng ám khí bắn ra, ngươi như cũ không chút để ý mà tránh thoát sở hữu ám khí……',
                    'Xem ra ngươi chống đỡ đã có chút thành tựu, nơi này đã không thích hợp ngươi tu luyện.',
                ],
            ])));
        }

        if (Helpers::getProbability(1, 3)) {
            return $connection->send(\cache_response($request, \view('Func/Liangong/chenFailed.twig', [
                'request' => $request,
            ])));
        }

        /**
         * 减少气血
         *
         */
        $role_attrs->hp -= 100;
        $role_attrs->hp = $role_attrs->hp < 0 ? 0 : $role_attrs->hp;
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);

        /**
         * 提升技能等级
         *
         */
        $role_skill->is_base = true;
        $experience = $role_skill->experience;
        $lv = $role_skill->lv;
        $up_exp = Helpers::getSkillExp($role_skill);
        $experience += intval($up_exp / 1000);
        $sql = '';
        $upgraded = false;
        if ($experience >= $up_exp) {
            $experience -= $up_exp;
            $lv += 1;
            $role_skill->lv = $lv;
            $up_exp = Helpers::getSkillExp($role_skill);
            $upgraded = true;
        }
        $sql .= <<<SQL
UPDATE `role_skills` SET `experience` = $experience, `lv` = $lv WHERE `id` = $role_skill->id;
SQL;

        Helpers::execSql($sql);
        if ($upgraded) {
            FlushRoleAttrs::fromRoleSkillByRoleId($request->roleId);
        }
        return $connection->send(\cache_response($request, \view('Func/Liangong/chen.twig', [
            'request'    => $request,
            'role_skill' => $role_skill,
            'lv'         => $lv,
            'percent'    => sprintf('%.1f', $experience / $up_exp * 100),
        ])));
    }


    /**
     * 欲
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function yu(TcpConnection $connection, Request $request)
    {
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        // if ($role_attrs->weaponRoleThingId === 0) {
        //     return $connection->send(\cache_response($request, \view('Base/message.twig', [
        //         'request' => $request,
        //         'message' => '没有武器怎么能练好Cơ bản chống đỡ呢？',
        //     ])));
        // }

        if ($role_attrs->hp < 100) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi khí huyết không đủ, sắc mặt tái nhợt, như thế nào tiếp tục luyện công?',
            ])));
        }

        $sql = <<<SQL
SELECT * FROM `role_skills` WHERE `role_id` = $request->roleId AND `skill_id` = 4;
SQL;

        $role_skill = Helpers::queryFetchObject($sql);
        if (!is_object($role_skill)) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => Helpers::randomSentence(),
            ])));
        }
        $role_skill->row = Helpers::getSkillRowBySkillId(4);
        if ($role_skill->lv < 105) {
            return $connection->send(\cache_response($request, \view('Base/messages.twig', [
                'request'  => $request,
                'messages' => [
                    'Trong bóng đêm, đột nhiên bên tai truyền đến “Xuy xuy” tiếng xé gió!',
                    'Nguyên lai là phòng luyện công trung cơ quan phóng ra ra ám khí.',
                    'Ngươi căn bản không kịp phản ứng đã bị đánh trúng, xem ra ngươi cơ bản chống đỡ võ công còn chưa đủ hỏa hậu, không thích hợp ở chỗ này tu luyện.',
                ],
            ])));
        }
        if ($role_skill->lv >= 130) {
            return $connection->send(\cache_response($request, \view('Base/messages.twig', [
                'request'  => $request,
                'messages' => [
                    'Trong bóng đêm, đột nhiên bên tai truyền đến “Xuy xuy” tiếng xé gió!',
                    'Ngươi tay mắt lanh lẹ, không chút hoang mang mà tránh thoát sở hữu ám khí.',
                    'Giây lát gian, lại là một vòng ám khí bắn ra, ngươi như cũ không chút để ý mà tránh thoát sở hữu ám khí……',
                    'Xem ra ngươi chống đỡ đã có chút thành tựu, nơi này đã không thích hợp ngươi tu luyện.',
                ],
            ])));
        }

        if (Helpers::getProbability(1, 3)) {
            return $connection->send(\cache_response($request, \view('Func/Liangong/yuFailed.twig', [
                'request' => $request,
            ])));
        }

        /**
         * 减少气血
         *
         */
        $role_attrs->hp -= 100;
        $role_attrs->hp = $role_attrs->hp < 0 ? 0 : $role_attrs->hp;
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);

        /**
         * 提升技能等级
         *
         */
        $role_skill->is_base = true;
        $experience = $role_skill->experience;
        $lv = $role_skill->lv;
        $up_exp = Helpers::getSkillExp($role_skill);
        $experience += intval($up_exp / 1000);
        $sql = '';
        $upgraded = false;
        if ($experience >= $up_exp) {
            $experience -= $up_exp;
            $lv += 1;
            $role_skill->lv = $lv;
            $up_exp = Helpers::getSkillExp($role_skill);
            $upgraded = true;
        }
        $sql .= <<<SQL
UPDATE `role_skills` SET `experience` = $experience, `lv` = $lv WHERE `id` = $role_skill->id;
SQL;

        Helpers::execSql($sql);
        if ($upgraded) {
            FlushRoleAttrs::fromRoleSkillByRoleId($request->roleId);
        }
        return $connection->send(\cache_response($request, \view('Func/Liangong/yu.twig', [
            'request'    => $request,
            'role_skill' => $role_skill,
            'lv'         => $lv,
            'percent'    => sprintf('%.1f', $experience / $up_exp * 100),
        ])));
    }
}
