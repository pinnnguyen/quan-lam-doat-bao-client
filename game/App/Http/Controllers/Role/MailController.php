<?php

namespace App\Http\Controllers\Role;

use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 邮箱
 *
 */
class MailController
{
    /**
     * 首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $page
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request, int $page = 1)
    {
        if ($page < 1) $page = 1;
        if ($page > 5) $page = 5;
        $offset = ($page - 1) * 20;
        $sql = <<<SQL
SELECT * FROM `mails` WHERE `receiver_id` = $request->roleId ORDER BY `state` DESC , `id` DESC LIMIT $offset, 20;
SQL;

        $mails = Helpers::queryFetchAll($sql);

        if (is_array($mails) and count($mails) > 0) {
            foreach ($mails as $mail) {
                $mail->time = date('m/d H:i', $mail->timestamp);
                $mail->title = mb_substr($mail->content, 0, 16);
                if (empty($mail->title)) {
                    $mail->title = 'Không có nội dung';
                }
                if ($mail->state == 1) {
                    if ($mail->kind == '无') {
                        $mail->title .= '[Chưa đọc]';
                    } else {
                        $mail->title .= '[Không nhận được tệp đính kèm]';
                    }
                }
                $mail->viewUrl = 'Role/Mail/view/' . $mail->id;
            }
        }

        return $connection->send(\cache_response($request, \view('Role/Mail/index.twig', [
            'request'   => $request,
            'mails'     => $mails,
            'last_page' => 'Role/Mail/index/' . ($page - 1),
            'next_page' => 'Role/Mail/index/' . ($page + 1),
        ])));
    }


    /**
     * Xem xét邮件
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $mail_id
     *
     * @return bool|null
     */
    public function view(TcpConnection $connection, Request $request, int $mail_id)
    {
        $sql = <<<SQL
SELECT * FROM `mails` WHERE `id` = $mail_id AND `receiver_id` = $request->roleId;
SQL;

        $mail = Helpers::queryFetchObject($sql);
        if (!is_object($mail)) {
            return $connection->send(\cache_response($request, \view('Role/Mail/message.twig', [
                'request' => $request,
                'message' => 'Email không tồn tại',
            ])));
        }

        $mail->time = date('Y/m/d H:i:s', $mail->timestamp);
        if (empty($mail->content)) {
            $mail->content = 'Không có nội dung';
        }

        if ($mail->kind == '物品') {
            $mail->thing = Helpers::getThingRowByThingId($mail->e_id);
            $mail->enclosure = Helpers::getHansNumber($mail->number) . $mail->thing->unit . $mail->thing->name;
        } elseif ($mail->kind == '心法') {
            $mail->xinfa = Helpers::getXinfaRowByXinfaId($mail->e_id);
            $mail->enclosure = 'Một quyển' . $mail->xinfa->name;
        } elseif ($mail->kind == '道具') {
            $mail->dj = ShopController::$djs[$mail->e_id];
            $mail->enclosure = Helpers::getHansNumber($mail->number) . $mail->dj['unit'] . $mail->dj['name'];
        } elseif ($mail->kind == '元宝') {
            $mail->enclosure = Helpers::getHansNumber($mail->number) . 'Nguyên bảo';
        } else {
            if ($mail->state == 1) {
                $sql = <<<SQL
UPDATE `mails` SET `state` = 0 WHERE `id` = $mail_id;
SQL;

                Helpers::execSql($sql);
            }
        }

        return $connection->send(\cache_response($request, \view('Role/Mail/view.twig', [
            'request'     => $request,
            'mail'        => $mail,
            'receive_url' => 'Role/Mail/receive/' . $mail_id,
        ])));
    }


    /**
     * 领取附件
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $mail_id
     *
     * @return bool|null
     */
    public function receive(TcpConnection $connection, Request $request, int $mail_id)
    {
        $sql = <<<SQL
SELECT * FROM `mails` WHERE `id` = $mail_id AND `receiver_id` = $request->roleId;
SQL;

        $mail = Helpers::queryFetchObject($sql);
        if (!is_object($mail)) {
            return $connection->send(\cache_response($request, \view('Role/Mail/message.twig', [
                'request' => $request,
                'message' => 'Email không tồn tại',
            ])));
        }

        $mail->time = date('Y/m/d H:i:s', $mail->timestamp);
        if (empty($mail->content)) {
            $mail->content = 'Không có nội dung';
        }

        if ($mail->kind == '物品') {
            $mail->thing = Helpers::getThingRowByThingId($mail->e_id);
            if ($mail->thing->kind == '药品') {
                $sql = <<<SQL
SELECT * FROM `role_drugs` WHERE `role_id` = $request->roleId AND `thing_id` = $mail->e_id;
SQL;

                $role_drug = Helpers::queryFetchObject($sql);
                if (is_object($role_drug)) {
                    $sql = <<<SQL
UPDATE `role_drugs` SET `number` = `number` + $mail->number WHERE `id` = $role_drug->id;
SQL;

                } else {
                    $sql = <<<SQL
INSERT INTO `role_drugs` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, $mail->e_id, $mail->number);
SQL;

                }
            } elseif ($mail->thing->kind == '书籍' or $mail->thing->kind == '装备') {
                $status = 0;
                if ($mail->thing->kind == '装备') {
                    $status = 4;
                }
                $sql = str_repeat(<<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`, `status`, `durability`) VALUES ($request->roleId, $mail->e_id, 1, $status, {$mail->thing->max_durability});
SQL,

                    $mail->number);
            } else {
                $sql = <<<SQL
SELECT * FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = $mail->e_id;
SQL;

                $role_thing = Helpers::queryFetchObject($sql);
                if (is_object($role_thing)) {
                    $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` + $mail->number WHERE `id` = $role_thing->id;
SQL;

                } else {
                    $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, $mail->e_id, $mail->number);
SQL;

                }
            }

            $sql .= <<<SQL
UPDATE `mails` SET `state` = 0 WHERE `id` = $mail_id;
SQL;
            Helpers::execSql($sql);
            $message = 'Ngươi đã lĩnh' . Helpers::getHansNumber($mail->number) . $mail->thing->unit . $mail->thing->name;
        } elseif ($mail->kind == '心法') {
            $mail->xinfa = Helpers::getXinfaRowByXinfaId($mail->e_id);
            if ($mail->xinfa->experience === 0 or $mail->xinfa->experience === 8) {
                $base_experience = 2;
            } elseif ($mail->xinfa->experience === 64 or $mail->xinfa->experience === 128) {
                $base_experience = 3;
            } elseif ($mail->xinfa->experience === 216) {
                $base_experience = 5;
            } else {
                $base_experience = 6;
            }
            $sql = <<<SQL
INSERT INTO `role_xinfas` (`role_id`, `xinfa_id`, `base_experience`, `lv`, `max_lv`) VALUES ($request->roleId, $mail->e_id, $base_experience, $mail->lv, $mail->max_lv);
UPDATE `mails` SET `state` = 0 WHERE `id` = $mail_id;
SQL;

            Helpers::execSql($sql);
            $message = 'Ngươi đã lĩnh một quyển' . $mail->xinfa->name;
        } elseif ($mail->kind == '道具') {
            $mail->dj = ShopController::$djs[$mail->e_id];
            if ($mail->e_id == 6 or $mail->e_id == 7) {
                if ($mail->e_id == 6) {
                    $times = 10;
                } elseif ($mail->e_id == 7) {
                    $times = 200;
                }
                $sql = str_repeat(<<<SQL
INSERT INTO `role_djs` (`role_id`, `dj_id`, `number`, `times`) VALUES ($request->roleId, $mail->e_id, 1, $times);
SQL,

                    $mail->number);
            } else {
                /**
                 * 传是否存在道具
                 */
                $sql = <<<SQL
SELECT * FROM `role_djs` WHERE `role_id` = $request->roleId AND `dj_id` = $mail->e_id;
SQL;

                $role_dj = Helpers::queryFetchObject($sql);
                if (is_object($role_dj)) {
                    $sql = <<<SQL
UPDATE `role_djs` SET `number` = `number` +  $mail->number WHERE `id` = $role_dj->id;
SQL;

                } else {
                    $sql = <<<SQL
INSERT INTO `role_djs` (`role_id`, `dj_id`, `number`) VALUES ($request->roleId, $mail->e_id, $mail->number);
SQL;

                }
            }
            $sql .= <<<SQL
UPDATE `mails` SET `state` = 0 WHERE `id` = $mail_id;
SQL;

            Helpers::execSql($sql);
            $message = 'Ngươi đã lĩnh' . Helpers::getHansNumber($mail->number) . $mail->dj['unit'] . $mail->dj['name'];
        } elseif ($mail->kind == '元宝') {
            $sql = <<<SQL
UPDATE `roles` SET `yuanbao` = `yuanbao` + $mail->number WHERE `id` = $request->roleId;
UPDATE `mails` SET `state` = 0 WHERE `id` = $mail_id;
SQL;

            Helpers::execSql($sql);
            $message = 'Ngươi đã lĩnh' . Helpers::getHansNumber($mail->number) . 'Nguyên bảo';
        } else {
            $message = 'Chưa định nghĩa';
        }

        return $connection->send(\cache_response($request, \view('Role/Mail/message.twig', [
            'request' => $request,
            'message' => $message,
        ])));
    }
}
