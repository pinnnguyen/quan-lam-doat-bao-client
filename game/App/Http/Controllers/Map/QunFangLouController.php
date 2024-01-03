<?php

namespace App\Http\Controllers\Map;

use App\Libs\Attrs\FlushRoleAttrs;
use App\Libs\Helpers;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 群芳楼
 */
class QunFangLouController
{
    /**
     * 接受甲任务 询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function armorReceiveQuestion(TcpConnection $connection, Request $request, int $number)
    {
        return $connection->send(\cache_response($request, \view('Map/QunFangLou/armorReceiveQuestion.twig', [
            'request'     => $request,
            'receive_url' => 'Map/QunFangLou/armorReceive/' . $number,
        ])));
    }


    /**
     * 接受甲任务 Xác nhận
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function armorReceive(TcpConnection $connection, Request $request, int $number)
    {
        $thing = Helpers::getThingRowByThingId(self::$qfls[752][$number]);
        /**
         * 修改记录
         */
        $sql = <<<SQL
UPDATE `role_qunfanglou_missions` SET `armor_status` = 1 WHERE `role_id` = $request->roleId;
SQL;


        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Map/QunFangLou/armorReceive.twig', [
            'request' => $request,
            'thing'   => $thing,
        ])));
    }


    /**
     * 取消甲任务 询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function armorCancelQuestion(TcpConnection $connection, Request $request, int $number)
    {
        return $connection->send(\cache_response($request, \view('Map/QunFangLou/armorCancelQuestion.twig', [
            'request'    => $request,
            'cancel_url' => 'Map/QunFangLou/armorCancel/' . $number,
        ])));
    }


    /**
     * 取消甲任务 Xác nhận
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function armorCancel(TcpConnection $connection, Request $request, int $number)
    {
        $thing = Helpers::getThingRowByThingId(self::$qfls[752][$number]);
        /**
         * 修改记录
         */
        $sql = <<<SQL
UPDATE `role_qunfanglou_missions` SET `armor_status` = 0 WHERE `role_id` = $request->roleId;
SQL;


        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Map/QunFangLou/armorCancel.twig', [
            'request' => $request,
            'thing'   => $thing,
        ])));
    }


    /**
     * 提交甲任务
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function armorSubmit(TcpConnection $connection, Request $request, int $number, int $role_thing_id)
    {
        $thing = Helpers::getThingRowByThingId(self::$qfls[752][$number]);
        /**
         * 修改记录
         */
        $sql = <<<SQL
UPDATE `role_qunfanglou_missions` SET `armor_status` = 0, `armor_number` = `armor_number` + 1 WHERE `role_id` = $request->roleId;
DELETE FROM `role_things` WHERE `id` = $role_thing_id;
SQL;


        Helpers::execSql($sql);

        FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);

        /**
         * 奖励Nội lực
         */
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        $role_attrs->qianneng += self::$rewards[752][$number];
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
        $qianneng = Helpers::getHansNumber(self::$rewards[752][$number]);

        if ($number < 23) {
            $continue_url = 'Map/QunFangLou/armorReceiveQuestion/' . ($number + 1);
        } else {
            if (Helpers::getProbability(5, 100)) {
                /**
                 * Cho空中神匣
                 */
                $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 245;
SQL;

                $role_thing = Helpers::queryFetchObject($sql);
                if ($role_thing) {
                    $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` + 1 WHERE `id` = $role_thing->id;
SQL;

                } else {
                    $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES  ($request->roleId, 245, 1);
SQL;

                }

                Helpers::execSql($sql);

                FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);
                $reward = ',Một cái không trung thần hộp';
            }
            $continue_url = 'Map/Index/index';
        }
        return $connection->send(\cache_response($request, \view('Map/QunFangLou/armorSubmit.twig', [
            'request'      => $request,
            'thing'        => $thing,
            'continue_url' => $continue_url,
            'reward'       => $reward ?? null,
            'qianneng'     => $qianneng,
        ])));
    }


    /**
     * 接受衣任务 询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function clothesReceiveQuestion(TcpConnection $connection, Request $request, int $number)
    {
        return $connection->send(\cache_response($request, \view('Map/QunFangLou/clothesReceiveQuestion.twig', [
            'request'     => $request,
            'receive_url' => 'Map/QunFangLou/clothesReceive/' . $number,
        ])));
    }


    /**
     * 接受衣任务 Xác nhận
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function clothesReceive(TcpConnection $connection, Request $request, int $number)
    {
        $thing = Helpers::getThingRowByThingId(self::$qfls[751][$number]);
        /**
         * 修改记录
         */
        $sql = <<<SQL
UPDATE `role_qunfanglou_missions` SET `clothes_status` = 1 WHERE `role_id` = $request->roleId;
SQL;


        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Map/QunFangLou/clothesReceive.twig', [
            'request' => $request,
            'thing'   => $thing,
        ])));
    }


    /**
     * 取消衣任务 询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function clothesCancelQuestion(TcpConnection $connection, Request $request, int $number)
    {
        return $connection->send(\cache_response($request, \view('Map/QunFangLou/clothesCancelQuestion.twig', [
            'request'    => $request,
            'cancel_url' => 'Map/QunFangLou/clothesCancel/' . $number,
        ])));
    }


    /**
     * 取消衣任务 Xác nhận
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function clothesCancel(TcpConnection $connection, Request $request, int $number)
    {
        $thing = Helpers::getThingRowByThingId(self::$qfls[751][$number]);
        /**
         * 修改记录
         */
        $sql = <<<SQL
UPDATE `role_qunfanglou_missions` SET `clothes_status` = 0 WHERE `role_id` = $request->roleId;
SQL;


        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Map/QunFangLou/clothesCancel.twig', [
            'request' => $request,
            'thing'   => $thing,
        ])));
    }


    /**
     * 提交衣任务
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function clothesSubmit(TcpConnection $connection, Request $request, int $number, int $role_thing_id)
    {
        $thing = Helpers::getThingRowByThingId(self::$qfls[751][$number]);
        /**
         * 修改记录
         */
        $sql = <<<SQL
UPDATE `role_qunfanglou_missions` SET `clothes_status` = 0, `clothes_number` = `clothes_number` + 1 WHERE `role_id` = $request->roleId;
DELETE FROM `role_things` WHERE `id` = $role_thing_id;
SQL;


        Helpers::execSql($sql);


        FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);
        /**
         * 奖励Nội lực
         */
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        $role_attrs->experience += self::$rewards[751][$number];
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
        $experience = Helpers::getHansExperience(self::$rewards[751][$number]);

        if ($number < 18) {
            $continue_url = 'Map/QunFangLou/clothesReceiveQuestion/' . ($number + 1);
        } else {
            /**
             * Cho踏雪无痕外篇
             */
            $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES  ($request->roleId, 209, 1);
SQL;


            Helpers::execSql($sql);

            $reward = ',Một quyển đạp tuyết vô ngân ngoại thiên';
            $continue_url = 'Map/Index/index';
            FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);
        }
        return $connection->send(\cache_response($request, \view('Map/QunFangLou/clothesSubmit.twig', [
            'request'      => $request,
            'thing'        => $thing,
            'continue_url' => $continue_url,
            'reward'       => $reward ?? null,
            'experience'   => $experience,
        ])));
    }


    /**
     * 接受鞋任务 询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function shoesReceiveQuestion(TcpConnection $connection, Request $request, int $number)
    {
        return $connection->send(\cache_response($request, \view('Map/QunFangLou/shoesReceiveQuestion.twig', [
            'request'     => $request,
            'receive_url' => 'Map/QunFangLou/shoesReceive/' . $number,
        ])));
    }


    /**
     * 接受鞋任务 Xác nhận
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function shoesReceive(TcpConnection $connection, Request $request, int $number)
    {
        $thing = Helpers::getThingRowByThingId(self::$qfls[745][$number]);
        /**
         * 修改记录
         */
        $sql = <<<SQL
UPDATE `role_qunfanglou_missions` SET `shoes_status` = 1 WHERE `role_id` = $request->roleId;
SQL;


        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Map/QunFangLou/shoesReceive.twig', [
            'request' => $request,
            'thing'   => $thing,
        ])));
    }


    /**
     * 取消鞋任务 询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function shoesCancelQuestion(TcpConnection $connection, Request $request, int $number)
    {
        return $connection->send(\cache_response($request, \view('Map/QunFangLou/shoesCancelQuestion.twig', [
            'request'    => $request,
            'cancel_url' => 'Map/QunFangLou/shoesCancel/' . $number,
        ])));
    }


    /**
     * 取消鞋任务 Xác nhận
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function shoesCancel(TcpConnection $connection, Request $request, int $number)
    {
        $thing = Helpers::getThingRowByThingId(self::$qfls[745][$number]);
        /**
         * 修改记录
         */
        $sql = <<<SQL
UPDATE `role_qunfanglou_missions` SET `shoes_status` = 0 WHERE `role_id` = $request->roleId;
SQL;


        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Map/QunFangLou/shoesCancel.twig', [
            'request' => $request,
            'thing'   => $thing,
        ])));
    }


    /**
     * 提交鞋任务
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function shoesSubmit(TcpConnection $connection, Request $request, int $number, int $role_thing_id)
    {
        $thing = Helpers::getThingRowByThingId(self::$qfls[745][$number]);
        /**
         * 修改记录
         */
        $sql = <<<SQL
UPDATE `role_qunfanglou_missions` SET `shoes_status` = 0, `shoes_number` = `shoes_number` + 1 WHERE `role_id` = $request->roleId;
DELETE FROM `role_things` WHERE `id` = $role_thing_id;
SQL;


        Helpers::execSql($sql);


        FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);
        /**
         * 奖励Nội lực
         */
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        $role_attrs->experience += self::$rewards[745][$number];
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
        $experience = Helpers::getHansExperience(self::$rewards[745][$number]);

        if ($number < 19) {
            $continue_url = 'Map/QunFangLou/shoesReceiveQuestion/' . ($number + 1);
        } else {
            /**
             * Cho武穆遗书外篇
             */
            $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES  ($request->roleId, 212, 1);
SQL;


            Helpers::execSql($sql);

            $reward = ',Một quyển Võ Mục Di Thư ngoại thiên';
            $continue_url = 'Map/Index/index';
            FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);
        }
        return $connection->send(\cache_response($request, \view('Map/QunFangLou/shoesSubmit.twig', [
            'request'      => $request,
            'thing'        => $thing,
            'continue_url' => $continue_url,
            'reward'       => $reward ?? null,
            'experience'   => $experience,
        ])));
    }


    /**
     * 接受爪任务 询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function zhuaReceiveQuestion(TcpConnection $connection, Request $request, int $number)
    {
        return $connection->send(\cache_response($request, \view('Map/QunFangLou/zhuaReceiveQuestion.twig', [
            'request'     => $request,
            'receive_url' => 'Map/QunFangLou/zhuaReceive/' . $number,
        ])));
    }


    /**
     * 接受爪任务 Xác nhận
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function zhuaReceive(TcpConnection $connection, Request $request, int $number)
    {
        $thing = Helpers::getThingRowByThingId(self::$qfls[744][$number]);
        /**
         * 修改记录
         */
        $sql = <<<SQL
UPDATE `role_qunfanglou_missions` SET `zhua_status` = 1 WHERE `role_id` = $request->roleId;
SQL;


        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Map/QunFangLou/zhuaReceive.twig', [
            'request' => $request,
            'thing'   => $thing,
        ])));
    }


    /**
     * 取消爪任务 询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function zhuaCancelQuestion(TcpConnection $connection, Request $request, int $number)
    {
        return $connection->send(\cache_response($request, \view('Map/QunFangLou/zhuaCancelQuestion.twig', [
            'request'    => $request,
            'cancel_url' => 'Map/QunFangLou/zhuaCancel/' . $number,
        ])));
    }


    /**
     * 取消爪任务 Xác nhận
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function zhuaCancel(TcpConnection $connection, Request $request, int $number)
    {
        $thing = Helpers::getThingRowByThingId(self::$qfls[744][$number]);
        /**
         * 修改记录
         */
        $sql = <<<SQL
UPDATE `role_qunfanglou_missions` SET `zhua_status` = 0 WHERE `role_id` = $request->roleId;
SQL;


        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Map/QunFangLou/zhuaCancel.twig', [
            'request' => $request,
            'thing'   => $thing,
        ])));
    }


    /**
     * 提交爪任务
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function zhuaSubmit(TcpConnection $connection, Request $request, int $number, int $role_thing_id)
    {
        $thing = Helpers::getThingRowByThingId(self::$qfls[744][$number]);
        /**
         * 修改记录
         */
        $sql = <<<SQL
UPDATE `role_qunfanglou_missions` SET `zhua_status` = 0, `zhua_number` = `zhua_number` + 1 WHERE `role_id` = $request->roleId;
DELETE FROM `role_things` WHERE `id` = $role_thing_id;
SQL;


        Helpers::execSql($sql);


        FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);
        /**
         * 奖励Nội lực
         */
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        $role_attrs->qianneng += self::$rewards[744][$number];
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
        $qianneng = Helpers::getHansNumber(self::$rewards[744][$number]);

        if ($number < 14) {
            $continue_url = 'Map/QunFangLou/zhuaReceiveQuestion/' . ($number + 1);
        } else {
            /**
             * Cho拆招秘典
             */
            $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES  ($request->roleId, 196, 1);
SQL;


            Helpers::execSql($sql);

            $reward = ',Một quyển hủy đi chiêu bí điển';
            $continue_url = 'Map/Index/index';
            FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);
        }
        return $connection->send(\cache_response($request, \view('Map/QunFangLou/zhuaSubmit.twig', [
            'request'      => $request,
            'thing'        => $thing,
            'continue_url' => $continue_url,
            'reward'       => $reward ?? null,
            'qianneng'     => $qianneng,
        ])));
    }


    /**
     * 接受剑任务 询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function jianReceiveQuestion(TcpConnection $connection, Request $request, int $number)
    {
        return $connection->send(\cache_response($request, \view('Map/QunFangLou/jianReceiveQuestion.twig', [
            'request'     => $request,
            'receive_url' => 'Map/QunFangLou/jianReceive/' . $number,
        ])));
    }


    /**
     * 接受剑任务 Xác nhận
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function jianReceive(TcpConnection $connection, Request $request, int $number)
    {
        $thing = Helpers::getThingRowByThingId(self::$qfls[743][$number]);
        /**
         * 修改记录
         */
        $sql = <<<SQL
UPDATE `role_qunfanglou_missions` SET `jian_status` = 1 WHERE `role_id` = $request->roleId;
SQL;


        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Map/QunFangLou/jianReceive.twig', [
            'request' => $request,
            'thing'   => $thing,
        ])));
    }


    /**
     * 取消剑任务 询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function jianCancelQuestion(TcpConnection $connection, Request $request, int $number)
    {
        return $connection->send(\cache_response($request, \view('Map/QunFangLou/jianCancelQuestion.twig', [
            'request'    => $request,
            'cancel_url' => 'Map/QunFangLou/jianCancel/' . $number,
        ])));
    }


    /**
     * 取消剑任务 Xác nhận
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function jianCancel(TcpConnection $connection, Request $request, int $number)
    {
        $thing = Helpers::getThingRowByThingId(self::$qfls[743][$number]);
        /**
         * 修改记录
         */
        $sql = <<<SQL
UPDATE `role_qunfanglou_missions` SET `jian_status` = 0 WHERE `role_id` = $request->roleId;
SQL;


        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Map/QunFangLou/jianCancel.twig', [
            'request' => $request,
            'thing'   => $thing,
        ])));
    }


    /**
     * 提交剑任务
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function jianSubmit(TcpConnection $connection, Request $request, int $number, int $role_thing_id)
    {
        $thing = Helpers::getThingRowByThingId(self::$qfls[743][$number]);
        /**
         * 修改记录
         */
        $sql = <<<SQL
UPDATE `role_qunfanglou_missions` SET `jian_status` = 0, `jian_number` = `jian_number` + 1 WHERE `role_id` = $request->roleId;
DELETE FROM `role_things` WHERE `id` = $role_thing_id;
SQL;


        Helpers::execSql($sql);

        FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);

        /**
         * 奖励Tu vi
         */
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        $role_attrs->experience += self::$rewards[743][$number];
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
        $experience = Helpers::getHansExperience(self::$rewards[743][$number]);

        if ($number < 47) {
            $continue_url = 'Map/QunFangLou/jianReceiveQuestion/' . ($number + 1);
        } else {
            /**
             * Cho剑术之魂
             */
            $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES  ($request->roleId, 244, 1);
SQL;


            Helpers::execSql($sql);

            $reward = ',Một quyển kiếm thuật chi hồn';
            $continue_url = 'Map/Index/index';
            FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);
        }
        return $connection->send(\cache_response($request, \view('Map/QunFangLou/jianSubmit.twig', [
            'request'      => $request,
            'thing'        => $thing,
            'continue_url' => $continue_url,
            'reward'       => $reward ?? null,
            'experience'   => $experience,
        ])));
    }


    /**
     * 接受刀任务 询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function daoReceiveQuestion(TcpConnection $connection, Request $request, int $number)
    {
        return $connection->send(\cache_response($request, \view('Map/QunFangLou/daoReceiveQuestion.twig', [
            'request'     => $request,
            'receive_url' => 'Map/QunFangLou/daoReceive/' . $number,
        ])));
    }


    /**
     * 接受刀任务 Xác nhận
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function daoReceive(TcpConnection $connection, Request $request, int $number)
    {
        $thing = Helpers::getThingRowByThingId(self::$qfls[746][$number]);
        /**
         * 修改记录
         */
        $sql = <<<SQL
UPDATE `role_qunfanglou_missions` SET `dao_status` = 1 WHERE `role_id` = $request->roleId;
SQL;


        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Map/QunFangLou/daoReceive.twig', [
            'request' => $request,
            'thing'   => $thing,
        ])));
    }


    /**
     * 取消刀任务 询问
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function daoCancelQuestion(TcpConnection $connection, Request $request, int $number)
    {
        return $connection->send(\cache_response($request, \view('Map/QunFangLou/daoCancelQuestion.twig', [
            'request'    => $request,
            'cancel_url' => 'Map/QunFangLou/daoCancel/' . $number,
        ])));
    }


    /**
     * 取消刀任务 Xác nhận
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function daoCancel(TcpConnection $connection, Request $request, int $number)
    {
        $thing = Helpers::getThingRowByThingId(self::$qfls[746][$number]);
        /**
         * 修改记录
         */
        $sql = <<<SQL
UPDATE `role_qunfanglou_missions` SET `dao_status` = 0 WHERE `role_id` = $request->roleId;
SQL;


        Helpers::execSql($sql);

        return $connection->send(\cache_response($request, \view('Map/QunFangLou/daoCancel.twig', [
            'request' => $request,
            'thing'   => $thing,
        ])));
    }


    /**
     * 提交刀任务
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $number
     * @param int           $role_thing_id
     *
     * @return bool|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function daoSubmit(TcpConnection $connection, Request $request, int $number, int $role_thing_id)
    {
        $thing = Helpers::getThingRowByThingId(self::$qfls[746][$number]);
        /**
         * 修改记录
         */
        $sql = <<<SQL
UPDATE `role_qunfanglou_missions` SET `dao_status` = 0, `dao_number` = `dao_number` + 1 WHERE `role_id` = $request->roleId;
DELETE FROM `role_things` WHERE `id` = $role_thing_id;
SQL;


        Helpers::execSql($sql);

        FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);

        /**
         * 奖励Nội lực
         */
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        $role_attrs->qianneng += self::$rewards[746][$number];
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
        $qianneng = Helpers::getHansNumber(self::$rewards[746][$number]);

        if ($number < 44) {
            $continue_url = 'Map/QunFangLou/daoReceiveQuestion/' . ($number + 1);
        } else {
            /**
             * Cho刀法之巅
             */
            $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES  ($request->roleId, 206, 1);
SQL;


            Helpers::execSql($sql);

            $reward = ',Một quyển đao pháp đỉnh';
            $continue_url = 'Map/Index/index';
            FlushRoleAttrs::fromRoleThingByRoleId($request->roleId);
        }
        return $connection->send(\cache_response($request, \view('Map/QunFangLou/daoSubmit.twig', [
            'request'      => $request,
            'thing'        => $thing,
            'continue_url' => $continue_url,
            'reward'       => $reward ?? null,
            'qianneng'     => $qianneng,
        ])));
    }


    /**
     * 群芳楼任务奖励
     *
     * @var array|array[]
     */
    public static array $rewards = [
        /**
         * 刀白凤、刀
         */
        746 => [
            60, 120, 180, 240, 300, 360, 420, 480, 540, 600, 660, 720, 780, 840, 900, 960, 1020, 1080, 1140, 1200,
            1260, 1320, 1380, 1440, 1500, 1560, 1620, 1680, 1740, 1800, 1860, 1920, 1980, 2040, 2100, 2160, 2220, 2280,
            2340, 2400, 2460, 2520, 2580, 2640, 2700,
        ],

        /**
         * 康敏、剑
         */
        743 => [
            264, 400, 600, 1000, 1400, 1800, 2200, 2600, 3000, 3400,
            3800, 4200, 4600, 5000, 5400, 5800, 6200, 6600, 7000, 7400,
            7800, 8200, 8600, 9000, 9400, 9800, 10200, 10600, 11000, 11400,
            11800, 12200, 12600, 13000, 13400, 13800, 14200, 14600, 15000, 15400,
            15800, 16200, 16600, 17000, 17400, 17800, 18200, 18600,
        ],

        /**
         * 梦姑、爪
         */
        744 => [60, 120, 180, 240, 300, 360, 420, 480, 540, 600, 660, 720, 780, 840, 900,],

        /**
         * 阿碧、鞋
         */
        745 => [
            264, 600, 1400, 2200, 3000, 3800, 4600, 5400, 6200, 7000,
            7800, 8600, 9400, 10200, 11000, 11800, 12600, 13400, 14200, 15000,
        ],

        /**
         * 阿紫、衣
         */
        751 => [
            264, 600, 1400, 2200, 3000, 3800, 4600, 5400, 6200, 7000,
            7800, 8600, 9400, 10200, 11000, 11800, 12600, 13400, 14200,
        ],

        /**
         * 阿朱、甲
         */
        752 => [
            60, 120, 180, 240, 300, 360, 420, 480, 540, 600, 660, 720, 780, 840, 900, 960, 1020, 1080, 1140, 1200,
            1260, 1320, 1380, 1440,
        ],
    ];

    /**
     * 群芳楼任务信息
     *
     * @var array|array[]
     */
    public static array $qfls = [
        /**
         * 刀白凤、刀
         */
        746 => [
            77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101,
            102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121,
        ],

        /**
         * 康敏、剑
         */
        743 => [
            30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54,
            55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 184, 73, 74, 75, 76,
        ],

        /**
         * 梦姑、爪
         */
        744 => [14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28,],

        /**
         * 阿碧、鞋
         */
        745 => [164, 165, 166, 167, 168, 169, 170, 171, 172, 173, 174, 175, 176, 177, 178, 179, 180, 181, 182, 183,],

        /**
         * 阿紫、衣
         */
        751 => [122, 123, 124, 125, 126, 128, 127, 129, 130, 131, 132, 133, 134, 135, 136, 137, 138, 139, 140,],

        /**
         * 阿朱、甲
         */
        752 => [
            141, 142, 143, 144, 145, 146, 147, 148, 149, 150, 151, 152, 153, 154, 155, 156, 157, 158, 159, 160,
            161, 162, 214, 192,
        ],
    ];
}
