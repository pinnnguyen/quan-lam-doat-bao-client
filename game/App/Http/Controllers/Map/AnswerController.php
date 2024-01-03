<?php

namespace App\Http\Controllers\Map;

use App\Libs\Attrs\FlushRoleAttrs;
use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 江湖见闻
 *
 */
class AnswerController
{
    /**
     * 问题一 问题合集
     *
     * @var array|string[][]
     */
    public static array $questions1 = [
        [
            'question' => 'Trong trường hợp nào một nhiệm vụ liên tục sẽ không bị gián đoạn?',
            '1'        => 'Nhiệm vụ chưa được hoàn thành hoặc hoàn thành sau thời gian chờ.',
            '2'        => 'Dừng tác vụ sau khi mã xác minh xuất hiện',
            'answer'   => '2',
        ],
        [
            'question' => 'Sau bốn mươi năm tu luyện, bạn không thể giúp chuyển thư sao?',
            '1'        => 'Chính xác',
            '2'        => 'Sai lầm',
            'answer'   => '1',
        ],
    ];

    /**
     * 问题二 问题合集
     *
     * @var array|string[][]
     */
    public static array $questions2 = [
        [
            'question' => 'Thẩm Dương, Liêu Ninh đã từng được gọi là gì?',
            '1'        => 'Phụng Thiên',
            '2'        => 'Đại Đô',
            'answer'   => '1',
        ],
        [
            'question' => 'Nơi nào từng được biết đến là cố đô của Mười Ba Triều Đại?',
            '1'        => 'Tây An',
            '2'        => 'Bắc Kinh',
            'answer'   => '1',
        ],
    ];


    /**
     * 江湖见闻首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        $question_timestamp = json_decode($request->roleRow->question_timestamp);

        if (empty($question_timestamp)) {
            $question_timestamp = new class {
                public int $question1_timestamp = 0;
                public int $question2_timestamp = 0;
            };
            $request->roleRow->question_timestamp = json_encode($question_timestamp);
            Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
            $sql = <<<SQL
UPDATE `roles` SET `question_timestamp` = '{$request->roleRow->question_timestamp}' WHERE `id` = $request->roleId;
SQL;


            Helpers::execSql($sql);

        }
        return $connection->send(\cache_response($request, \view('Map/Answer/index.twig', [
            'request' => $request,
        ])));
    }


    /**
     * 挑战第一个问题
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function question1(TcpConnection $connection, Request $request)
    {
        /**
         * 查询是否已经挑战
         *
         */
        $question_timestamp = json_decode($request->roleRow->question_timestamp);
        if ($question_timestamp->question1_timestamp > strtotime(date('Y-m-d', time()))) {
            return $connection->send(\cache_response($request, \view('Map/Answer/message.twig', [
                'request' => $request,
                'message' => 'Bạn đã trả lời câu hỏi đầu tiên ngày hôm nay, hãy quay lại vào ngày mai.',
            ])));
        }

        /**
         * 获取问题
         *
         */
        $question_number = array_rand(self::$questions1);
        $question = self::$questions1[$question_number];

        if (mt_rand(0, 1)) {
            $answers = [
                ['name' => $question['1'], 'url' => 'Map/Answer/answer1/' . $question_number . '/1',],
                ['name' => $question['2'], 'url' => 'Map/Answer/answer1/' . $question_number . '/2',],
            ];
        } else {
            $answers = [
                ['name' => $question['2'], 'url' => 'Map/Answer/answer1/' . $question_number . '/2',],
                ['name' => $question['1'], 'url' => 'Map/Answer/answer1/' . $question_number . '/1',],
            ];
        }

        return $connection->send(\cache_response($request, \view('Map/Answer/question.twig', [
            'request'  => $request,
            'question' => $question['question'],
            'answers'  => $answers,
        ])));
    }


    /**
     * 挑战第二个问题
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function question2(TcpConnection $connection, Request $request)
    {
        /**
         * 查询是否已经挑战
         *
         */
        $question_timestamp = json_decode($request->roleRow->question_timestamp);
        if ($question_timestamp->question2_timestamp > strtotime(date('Y-m-d', time()))) {
            return $connection->send(\cache_response($request, \view('Map/Answer/message.twig', [
                'request' => $request,
                'message' => 'Bạn đã trả lời câu hỏi thứ hai ngày hôm nay, hãy quay lại vào ngày mai.',
            ])));
        }

        /**
         * 获取问题
         */
        $question_number = array_rand(self::$questions2);
        $question = self::$questions2[$question_number];

        if (mt_rand(0, 1)) {
            $answers = [
                ['name' => $question['1'], 'url' => 'Map/Answer/answer2/' . $question_number . '/1',],
                ['name' => $question['2'], 'url' => 'Map/Answer/answer2/' . $question_number . '/2',],
            ];
        } else {
            $answers = [
                ['name' => $question['2'], 'url' => 'Map/Answer/answer2/' . $question_number . '/2',],
                ['name' => $question['1'], 'url' => 'Map/Answer/answer2/' . $question_number . '/1',],
            ];
        }

        return $connection->send(\cache_response($request, \view('Map/Answer/question.twig', [
            'request'  => $request,
            'question' => $question['question'],
            'answers'  => $answers,
        ])));
    }


    /**
     * 回答第一个问题
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $question_number
     * @param int           $answer_number
     *
     * @return bool|null
     */
    public function answer1(TcpConnection $connection, Request $request, int $question_number, int $answer_number)
    {
        /**
         * 修改时间戳
         *
         */
        $question_timestamp = json_decode($request->roleRow->question_timestamp);
        $question_timestamp->question1_timestamp = time();
        $request->roleRow->question_timestamp = json_encode($question_timestamp);
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        $sql = <<<SQL
UPDATE `roles` SET `question_timestamp` = '{$request->roleRow->question_timestamp}' WHERE `id` = $request->roleId;
SQL;


        Helpers::execSql($sql);


        /**
         * 获取问题
         *
         */
        $question = self::$questions1[$question_number];
        if ($question['answer'] == $answer_number) {
            /**
             * 回答正确
             *
             */
            /**
             * 生成奖励
             *
             */
            $probability = mt_rand(1, 99);
            if ($probability <= 33) {
                /**
                 * 200điểm nội lực
                 *
                 */
                $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
                $role_attrs->qianneng += 200;
                Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
                return $connection->send(\cache_response($request, \view('Map/Answer/message.twig', [
                    'request' => $request,
                    'message' => 'Chúc mừng câu trả lời đúng của bạn, bạn đã nhận được 200 điểm tiềm năng.',
                ])));
            } elseif ($probability <= 66) {
                /**
                 * 200点最大Tinh thần
                 *
                 */
                $sql = <<<SQL
UPDATE `roles` SET `base_jingshen` = `base_jingshen` + 200 WHERE `id` = $request->roleId;
SQL;


                Helpers::execSql($sql);

                $request->roleRow->base_jingshen += 200;
                Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                FlushRoleAttrs::fromRoleRowByRoleId($request->roleId);
                return $connection->send(\cache_response($request, \view('Map/Answer/message.twig', [
                    'request' => $request,
                    'message' => 'Chúc mừng bạn đã trả lời đúng. Giá trị tinh thần tối đa của bạn đã tăng thêm 200 điểm.',
                ])));
            } else {
                /**
                 * 40两Bạch ngân
                 *
                 */
                $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

                $role_thing = Helpers::queryFetchObject($sql);
                if ($role_thing) {
                    $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` + 40000 WHERE `id` = $role_thing->id;
SQL;

                } else {
                    $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, 213, 40000);
SQL;

                }

                Helpers::execSql($sql);

                return $connection->send(\cache_response($request, \view('Map/Answer/message.twig', [
                    'request' => $request,
                    'message' => 'Chúc mừng ngươi đã trả lời đúng và được bốn mươi lạng bạc.',
                ])));
            }
        } else {
            return $connection->send(\cache_response($request, \view('Map/Answer/message.twig', [
                'request' => $request,
                'message' => 'Xin lỗi, câu trả lời của bạn sai. Vui lòng quay lại vào ngày mai.',
            ])));
        }
    }


    /**
     * 回答第二个问题
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $question_number
     * @param int           $answer_number
     *
     * @return bool|null
     */
    public function answer2(TcpConnection $connection, Request $request, int $question_number, int $answer_number)
    {
        /**
         * 修改时间戳
         *
         */
        $question_timestamp = json_decode($request->roleRow->question_timestamp);
        $question_timestamp->question2_timestamp = time();
        $request->roleRow->question_timestamp = json_encode($question_timestamp);
        Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
        $sql = <<<SQL
UPDATE `roles` SET `question_timestamp` = '{$request->roleRow->question_timestamp}' WHERE `id` = $request->roleId;
SQL;


        Helpers::execSql($sql);


        /**
         * 获取问题
         *
         */
        $question = self::$questions2[$question_number];
        if ($question['answer'] == $answer_number) {
            /**
             * 回答正确
             *
             */
            /**
             * 生成奖励
             *
             */
            $probability = mt_rand(1, 99);
            if ($probability <= 33) {
                /**
                 * 200điểm nội lực
                 *
                 */
                $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
                $role_attrs->qianneng += 200;
                Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
                return $connection->send(\cache_response($request, \view('Map/Answer/message.twig', [
                    'request' => $request,
                    'message' => 'Chúc mừng câu trả lời đúng của bạn, bạn đã nhận được 200 điểm tiềm năng.',
                ])));
            } elseif ($probability <= 66) {
                /**
                 * 200点最大Tinh thần
                 *
                 */
                $sql = <<<SQL
UPDATE `roles` SET `base_jingshen` = `base_jingshen` + 200 WHERE `id` = $request->roleId;
SQL;


                Helpers::execSql($sql);

                $request->roleRow->base_jingshen += 200;
                Helpers::setRoleRowByRoleId($request->roleId, $request->roleRow);
                FlushRoleAttrs::fromRoleRowByRoleId($request->roleId);
                return $connection->send(\cache_response($request, \view('Map/Answer/message.twig', [
                    'request' => $request,
                    'message' => 'Chúc mừng bạn đã trả lời đúng. Giá trị tinh thần tối đa của bạn đã tăng thêm 200 điểm.',
                ])));
            } else {
                /**
                 * 40两Bạch ngân
                 *
                 */
                $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

                $role_thing = Helpers::queryFetchObject($sql);
                if ($role_thing) {
                    $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` + 40000 WHERE `id` = $role_thing->id;
SQL;

                } else {
                    $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, 213, 40000);
SQL;

                }

                Helpers::execSql($sql);

                return $connection->send(\cache_response($request, \view('Map/Answer/message.twig', [
                    'request' => $request,
                    'message' => 'Chúc mừng ngươi đã trả lời đúng và được bốn mươi lạng bạc.',
                ])));
            }
        } else {
            return $connection->send(\cache_response($request, \view('Map/Answer/message.twig', [
                'request' => $request,
                'message' => 'Xin lỗi, câu trả lời của bạn sai. Vui lòng quay lại vào ngày mai.',
            ])));
        }
    }
}
