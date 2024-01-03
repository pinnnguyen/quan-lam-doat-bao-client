<?php

namespace App\Http\Controllers\Map;

use App\Libs\Helpers;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

/**
 * 钓鱼
 *
 */
class FishingController
{
    /**
     * 钓鱼首页
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function index(TcpConnection $connection, Request $request)
    {
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->weaponThingId != 29) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi không có mang theo cần câu, không thể câu cá, ngươi có thể đến phía tây cửa hàng mua sắm một cây cần câu. '.$role_attrs->weaponThingId,
            ])));
        }
        if ($role_attrs->weaponDurability < 1) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi cần câu đã hư hao, không thể câu cá, ngươi có thể sửa chữa hoặc là đến phía tây cửa hàng mua sắm một cây cần câu.',
            ])));
        }

        return $connection->send(\cache_response($request, \view('Map/Fishing/index.twig', [
            'request' => $request,
        ])));
    }


    /**
     * Bắt đầu câu cá
     *
     * @param TcpConnection $connection
     * @param Request       $request
     *
     * @return bool|null
     */
    public function start(TcpConnection $connection, Request $request)
    {
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->weaponThingId != 29) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi không có mang theo cần câu, không thể câu cá, ngươi có thể đến phía tây cửa hàng mua sắm một cây cần câu.',
            ])));
        }
        if ($role_attrs->weaponDurability < 1) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi cần câu đã hư hao, không thể câu cá, ngươi có thể sửa chữa hoặc là đến phía tây cửa hàng mua sắm một cây cần câu.',
            ])));
        }

        $role_attrs->startFishingTimestamp = time();
        Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
        return $this->view($connection, $request);
    }


    /**
     * 空钩
     *
     * @var array|string[]
     */
    public static array $kong = [
        'Ngươi đột nhiên nhắc tới côn, phát hiện cái gì cũng không câu đến.',
        'Ngươi đột nhiên nhắc tới côn, câu tới rồi một cái cá sấu, dọa ngươi một cú sốc.',
    ];

    /**
     * 可爱三件套
     *
     * @var array|string[]
     */
    public static array $san = [
        'Ngươi đột nhiên nhắc tới côn, câu tới rồi một cái yếm đỏ, thật đen đủi.',
        'Ngươi đột nhiên nhắc tới côn, câu tới rồi một cái phá mũ rơm, thật đen đủi.',
        'Ngươi đột nhiên nhắc tới côn, câu tới rồi một cái đầu heo, thật đen đủi.',
    ];

    /**
     * 普通鱼
     *
     * @var array|string[]
     */
    public static array $pu = [
        'Ngươi đột nhiên nhắc tới côn, câu tới rồi một cái cá trắm cỏ, bán cho cá lái buôn được đến $M。',
        'Ngươi đột nhiên nhắc tới côn, câu tới rồi một cái cá nheo, bán cho cá lái buôn được đến $M。',
        'Ngươi đột nhiên nhắc tới côn, câu tới rồi một cái cá mè, bán cho cá lái buôn được đến $M。',
        'Ngươi đột nhiên nhắc tới côn, câu tới rồi một cái cá chép, bán cho cá lái buôn được đến $M。',
        'Ngươi đột nhiên nhắc tới côn, câu tới rồi một cái cá trắm đen, bán cho cá lái buôn được đến $M。',
        'Ngươi đột nhiên nhắc tới côn, câu tới rồi một cái biên cá, bán cho cá lái buôn được đến $M。',
        'Ngươi đột nhiên nhắc tới côn, câu tới rồi một cái lư ngư, bán cho cá lái buôn được đến $M。',
    ];

    /**
     * 鲤鱼
     *
     * @var array|string[]
     */
    public static array $li = [
        'Ngươi đột nhiên nhắc tới côn, câu tới rồi một cái màu đỏ đại cá chép, bán cho cá lái buôn được đến $M。',
    ];

    /**
     * 秘籍
     *
     * @var array|string[]
     */
    public static array $mi = [
        'Ngươi đột nhiên nhắc tới côn, câu tới rồi một quyển trong truyền thuyết cao cấp kiếm thuật.', // 202
        'Ngươi đột nhiên nhắc tới côn, câu tới rồi một quyển trong truyền thuyết đao pháp tinh muốn ngoại thiên.', // 239
        'Ngươi đột nhiên nhắc tới côn, câu tới rồi một quyển trong truyền thuyết hủy đi chiêu giảm bớt lực chi thuật.', // 195
        'Ngươi đột nhiên nhắc tới côn, câu tới rồi một quyển trong truyền thuyết đá phiến.', // 198
        'Ngươi đột nhiên nhắc tới côn, câu tới rồi một quyển trong truyền thuyết Võ Mục Di Thư.', // 211
        'Ngươi đột nhiên nhắc tới côn, câu tới rồi một quyển trong truyền thuyết đạp tuyết vô ngân.', // 208
    ];

    public static array $miThing = [202, 239, 195, 198, 211, 208];

    /**
     * 荷包
     *
     * @var array|string[]
     */
    public static array $he = [
        'Ngươi đột nhiên nhắc tới côn, câu tới rồi một quyển trong truyền thuyết đá phiến.$M。',
    ];


    /**
     * Xem xét钓鱼结果
     *
     * @param TcpConnection $connection
     * @param Request       $request
     * @param int           $is_fuck
     *
     * @return bool|null
     */
    public function view(TcpConnection $connection, Request $request, int $is_fuck = 0)
    {
        $role_attrs = Helpers::getRoleAttrsByRoleId($request->roleId);
        if ($role_attrs->weaponThingId != 29) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi không có mang theo cần câu, không thể câu cá, ngươi có thể đến phía tây cửa hàng mua sắm một cây cần câu.',
            ])));
        }
        if ($role_attrs->weaponDurability < 1) {
            return $connection->send(\cache_response($request, \view('Base/message.twig', [
                'request' => $request,
                'message' => 'Ngươi cần câu đã hư hao, không thể câu cá, ngươi có thể sửa chữa hoặc là đến phía tây cửa hàng mua sắm một cây cần câu.',
            ])));
        }

        $messages = [];

        if (time() - $role_attrs->startFishingTimestamp >= 12) {
            if ($is_fuck) {
                $experience = mt_rand(1, 4);
                $qianneng = 1;
                $messages[] = 'Ngươi được đến ' . Helpers::getHansExperience($experience) . ' Tu hành, ' . Helpers::getHansNumber($qianneng) . ' Điểm tiềm năng.';

                $role_attrs->experience += $experience;

                if ($role_attrs->qianneng < $role_attrs->maxQianneng) {
                    $role_attrs->qianneng += $qianneng;
                } else {
                    $messages[] = 'Ngươi tiềm năng đã đầy!';
                }

                Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
                $message = self::$kong[array_rand(self::$kong)];
                return $connection->send(\cache_response($request, \view('Map/Fishing/result.twig', [
                    'request'  => $request,
                    'message'  => $message,
                    'messages' => $messages,
                ])));
            } else {
                $message = 'Mặt nước gió êm sóng lặng, lơ là cũng chậm rãi không có động tĩnh, con cá tựa hồ đã đem mồi câu ăn sạch.';
                return $connection->send(\cache_response($request, \view('Map/Fishing/view.twig', [
                    'request'  => $request,
                    'messages' => $messages,
                    'message'  => $message,
                ])));
            }
        } elseif (time() - $role_attrs->startFishingTimestamp >= 10) {
            if ($is_fuck) {
                $status = mt_rand(1, 10000);
                if ($status < 1001) {
                    $experience = mt_rand(1, 4);
                    $qianneng = 1;
                    $message = self::$kong[array_rand(self::$kong)];
                } elseif ($status < 2001) {
                    $experience = mt_rand(4, 10);
                    $qianneng = 1;
                    $role_attrs->weaponDurability -= 1;
                    $message = self::$san[array_rand(self::$san)];
                } elseif ($status < 9901) {
                    $money = mt_rand(1, 15);
                    $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

                    $role_thing = Helpers::queryFetchObject($sql);
                    if ($role_thing) {
                        $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` + $money WHERE `id` = $role_thing->id;
SQL;

                    } else {
                        $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, 213, $money);
SQL;

                    }

                    Helpers::execSql($sql);


                    $experience = mt_rand(20, 50);
                    $qianneng = intdiv($experience, 20);
                    $role_attrs->weaponDurability -= 1;
                    $message = preg_replace('/\$M/', Helpers::getHansMoney($money), self::$pu[array_rand(self::$pu)]);
                } elseif ($status < 9903) {
                    $mi = array_rand(self::$mi);
                    $mi_thing_id = self::$miThing[$mi];
                    $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, $mi_thing_id, 1);
SQL;


                    Helpers::execSql($sql);

                    $experience = mt_rand(12, 20);
                    $qianneng = 1;
                    $role_attrs->weaponDurability -= 1;
                    $message = self::$mi[$mi];
                } elseif ($status < 9951) {
                    $money = mt_rand(30, 100);
                    $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

                    $role_thing = Helpers::queryFetchObject($sql);
                    if ($role_thing) {
                        $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` + $money WHERE `id` = $role_thing->id;
SQL;

                    } else {
                        $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, 213, $money);
SQL;

                    }

                    Helpers::execSql($sql);

                    $experience = mt_rand(100, 200);
                    $qianneng = intdiv($experience, 20);
                    $role_attrs->weaponDurability -= 1;
                    $message = preg_replace('/\$M/', Helpers::getHansMoney($money), self::$li[array_rand(self::$li)]);
                } else {
                    $money = mt_rand(2000, 8000);
                    $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

                    $role_thing = Helpers::queryFetchObject($sql);
                    if ($role_thing) {
                        $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` + $money WHERE `id` = $role_thing->id;
SQL;

                    } else {
                        $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, 213, $money);
SQL;

                    }

                    Helpers::execSql($sql);

                    $experience = mt_rand(12, 20);
                    $qianneng = 1;
                    $role_attrs->weaponDurability -= 1;
                    $message = preg_replace('/\$M/', Helpers::getHansMoney($money), self::$he[array_rand(self::$he)]);
                }
                $messages[] = 'Ngươi được đến ' . Helpers::getHansExperience($experience) . ' Tu hành, ' . Helpers::getHansNumber($qianneng) . ' Điểm tiềm năng.';


                $role_attrs->experience += $experience;

                if ($role_attrs->qianneng < $role_attrs->maxQianneng) {
                    $role_attrs->qianneng += $qianneng;
                } else {
                    $messages[] = 'Ngươi tiềm năng đã đầy!';
                }

                Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
                return $connection->send(\cache_response($request, \view('Map/Fishing/result.twig', [
                    'request'  => $request,
                    'message'  => $message,
                    'messages' => $messages,
                ])));
            } else {
                $message = 'Mặt hồ bọt nước văng khắp nơi, lơ là một trận kịch liệt đong đưa, giống như có con cá ở cắn câu.';
                return $connection->send(\cache_response($request, \view('Map/Fishing/view.twig', [
                    'request'  => $request,
                    'messages' => $messages,
                    'message'  => $message,
                ])));
            }
        } elseif (time() - $role_attrs->startFishingTimestamp >= 7) {
            if ($is_fuck) {
                $status = mt_rand(1, 10000);
                if ($status < 1501) {
                    $experience = mt_rand(1, 4);
                    $qianneng = 1;
                    $message = self::$kong[array_rand(self::$kong)];
                } elseif ($status < 2901) {
                    $experience = mt_rand(4, 10);
                    $qianneng = 1;
                    $role_attrs->weaponDurability -= 1;
                    $message = self::$san[array_rand(self::$san)];
                } elseif ($status < 9901) {
                    $money = mt_rand(1, 15);
                    $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

                    $role_thing = Helpers::queryFetchObject($sql);
                    if ($role_thing) {
                        $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` + $money WHERE `id` = $role_thing->id;
SQL;

                    } else {
                        $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, 213, $money);
SQL;

                    }

                    Helpers::execSql($sql);


                    $experience = mt_rand(20, 50);
                    $qianneng = intdiv($experience, 20);
                    $role_attrs->weaponDurability -= 1;
                    $message = preg_replace('/\$M/', Helpers::getHansMoney($money), self::$pu[array_rand(self::$pu)]);
                } elseif ($status < 9903) {
                    $mi = array_rand(self::$mi);
                    $mi_thing_id = self::$miThing[$mi];
                    $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, $mi_thing_id, 1);
SQL;


                    Helpers::execSql($sql);

                    $experience = mt_rand(12, 20);
                    $qianneng = 1;
                    $role_attrs->weaponDurability -= 1;
                    $message = self::$mi[$mi];
                } elseif ($status < 9951) {
                    $money = mt_rand(30, 100);
                    $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

                    $role_thing = Helpers::queryFetchObject($sql);
                    if ($role_thing) {
                        $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` + $money WHERE `id` = $role_thing->id;
SQL;

                    } else {
                        $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, 213, $money);
SQL;

                    }

                    Helpers::execSql($sql);

                    $experience = mt_rand(100, 200);
                    $qianneng = intdiv($experience, 20);
                    $role_attrs->weaponDurability -= 1;
                    $message = preg_replace('/\$M/', Helpers::getHansMoney($money), self::$li[array_rand(self::$li)]);
                } else {
                    $money = mt_rand(2000, 8000);
                    $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

                    $role_thing = Helpers::queryFetchObject($sql);
                    if ($role_thing) {
                        $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` + $money WHERE `id` = $role_thing->id;
SQL;

                    } else {
                        $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, 213, $money);
SQL;

                    }

                    Helpers::execSql($sql);


                    $experience = mt_rand(12, 20);
                    $qianneng = 1;
                    $role_attrs->weaponDurability -= 1;
                    $message = preg_replace('/\$M/', Helpers::getHansMoney($money), self::$he[array_rand(self::$he)]);
                }
                $messages[] = 'Ngươi được đến ' . Helpers::getHansExperience($experience) . ' Tu hành, ' . Helpers::getHansNumber($qianneng) . ' Điểm tiềm năng.';

                $role_attrs->experience += $experience;

                if ($role_attrs->qianneng < $role_attrs->maxQianneng) {
                    $role_attrs->qianneng += $qianneng;
                } else {
                    $messages[] = 'Ngươi tiềm năng đã đầy!';
                }
                Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
                return $connection->send(\cache_response($request, \view('Map/Fishing/result.twig', [
                    'request'  => $request,
                    'message'  => $message,
                    'messages' => $messages,
                ])));
            } else {
                $message = 'Mặt nước đầy sao điểm điểm, lơ là khi thượng đương thời, giống như có rất nhiều con cá ở cắn câu.';
                return $connection->send(\cache_response($request, \view('Map/Fishing/view.twig', [
                    'request'  => $request,
                    'messages' => $messages,
                    'message'  => $message,
                ])));
            }


        } elseif (time() - $role_attrs->startFishingTimestamp >= 4) {
            if ($is_fuck) {
                $status = mt_rand(1, 10000);
                if ($status < 4001) {
                    $experience = mt_rand(1, 4);
                    $qianneng = 1;
                    $message = self::$kong[array_rand(self::$kong)];
                } elseif ($status < 6901) {
                    $experience = mt_rand(4, 10);
                    $qianneng = 1;
                    $role_attrs->weaponDurability -= 1;
                    $message = self::$san[array_rand(self::$san)];
                } elseif ($status < 9901) {
                    $money = mt_rand(1, 15);
                    $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

                    $role_thing = Helpers::queryFetchObject($sql);
                    if ($role_thing) {
                        $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` + $money WHERE `id` = $role_thing->id;
SQL;

                    } else {
                        $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, 213, $money);
SQL;

                    }

                    Helpers::execSql($sql);


                    $experience = mt_rand(20, 50);
                    $qianneng = intdiv($experience, 20);
                    $role_attrs->weaponDurability -= 1;
                    $message = preg_replace('/\$M/', Helpers::getHansMoney($money), self::$pu[array_rand(self::$pu)]);
                } elseif ($status < 9903) {
                    $mi = array_rand(self::$mi);
                    $mi_thing_id = self::$miThing[$mi];
                    $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, $mi_thing_id, 1);
SQL;


                    Helpers::execSql($sql);

                    $experience = mt_rand(12, 20);
                    $qianneng = 1;
                    $role_attrs->weaponDurability -= 1;
                    $message = self::$mi[$mi];
                } elseif ($status < 9951) {
                    $money = mt_rand(30, 100);
                    $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

                    $role_thing = Helpers::queryFetchObject($sql);
                    if ($role_thing) {
                        $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` + $money WHERE `id` = $role_thing->id;
SQL;

                    } else {
                        $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, 213, $money);
SQL;

                    }

                    Helpers::execSql($sql);

                    $experience = mt_rand(100, 200);
                    $qianneng = intdiv($experience, 20);
                    $role_attrs->weaponDurability -= 1;
                    $message = preg_replace('/\$M/', Helpers::getHansMoney($money), self::$li[array_rand(self::$li)]);
                } else {
                    $money = mt_rand(2000, 8000);
                    $sql = <<<SQL
SELECT `id` FROM `role_things` WHERE `role_id` = $request->roleId AND `thing_id` = 213;
SQL;

                    $role_thing = Helpers::queryFetchObject($sql);
                    if ($role_thing) {
                        $sql = <<<SQL
UPDATE `role_things` SET `number` = `number` + $money WHERE `id` = $role_thing->id;
SQL;

                    } else {
                        $sql = <<<SQL
INSERT INTO `role_things` (`role_id`, `thing_id`, `number`) VALUES ($request->roleId, 213, $money);
SQL;

                    }

                    Helpers::execSql($sql);


                    $experience = mt_rand(12, 20);
                    $qianneng = 1;
                    $role_attrs->weaponDurability -= 1;
                    $message = preg_replace('/\$M/', Helpers::getHansMoney($money), self::$he[array_rand(self::$he)]);
                }
                $messages[] = 'Ngươi được đến ' . Helpers::getHansExperience($experience) . ' Tu hành, ' . Helpers::getHansNumber($qianneng) . ' Điểm tiềm năng.';

                $role_attrs->experience += $experience;

                if ($role_attrs->qianneng < $role_attrs->maxQianneng) {
                    $role_attrs->qianneng += $qianneng;
                } else {
                    $messages[] = 'Ngươi tiềm năng đã đầy!';
                }
                Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
                return $connection->send(\cache_response($request, \view('Map/Fishing/result.twig', [
                    'request'  => $request,
                    'message'  => $message,
                    'messages' => $messages,
                ])));
            } else {
                $message = 'Mặt hồ vi ba nhộn nhạo, lơ là nhẹ nhàng đong đưa, giống như có con cá ở cắn câu.';
                return $connection->send(\cache_response($request, \view('Map/Fishing/view.twig', [
                    'request'  => $request,
                    'messages' => $messages,
                    'message'  => $message,
                ])));
            }
        } else {
            if ($is_fuck) {
                $status = mt_rand(0, 1);
                if ($status === 0) {
                    $experience = mt_rand(1, 4);
                    $message = self::$kong[array_rand(self::$kong)];
                } else {
                    $experience = mt_rand(4, 10);
                    $role_attrs->weaponDurability -= 1;
                    $message = self::$san[array_rand(self::$san)];
                }
//                $qianneng = 1;
//                $messages[] = 'Ngươi được đến ' . Helpers::getHansExperience($experience) . ' Tu hành, ' . Helpers::getHansNumber($qianneng) . ' Điểm tiềm năng.';
                $qianneng = 0;
                $messages[] = 'Ngươi được đến ' . Helpers::getHansExperience($experience) . 'Tu hành。';


                $role_attrs->experience += $experience;

//                if ($role_attrs->qianneng < $role_attrs->maxQianneng) {
//                    $role_attrs->qianneng += $qianneng;
//                } else {
//                    $messages[] = 'Ngươi tiềm năng đã đầy!';
//                }
                Helpers::setRoleAttrsByRoleId($request->roleId, $role_attrs);
                return $connection->send(\cache_response($request, \view('Map/Fishing/result.twig', [
                    'request'  => $request,
                    'message'  => $message,
                    'messages' => $messages,
                ])));
            } else {
                $message = 'Mặt nước sóng nước lóng lánh, lơ là cũng không nhúc nhích, tựa hồ không có con cá ở cắn câu.';
                return $connection->send(\cache_response($request, \view('Map/Fishing/view.twig', [
                    'request'  => $request,
                    'messages' => $messages,
                    'message'  => $message,
                ])));
            }
        }
    }
}
