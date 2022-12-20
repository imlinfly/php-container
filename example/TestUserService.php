<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/12/15 15:10:07
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Example;

class TestUserService
{
    public function __construct(private TestOrderService $testOrderService)
    {

    }

    public function getUserInfo(int $userId)
    {
        return [
            'user_id' => $userId,
            'username' => 'user' . $userId,
        ];
    }

    public function getUserOrder(int $userId)
    {
        return $this->testOrderService->getUserOrderList($userId);
    }
}
