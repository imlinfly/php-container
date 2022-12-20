<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/12/15 15:11:21
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Example;

class TestController
{
    public function __construct(private TestUserService $testUserService)
    {
    }

    public function info()
    {
        return [
            'user_info' => $this->testUserService->getUserInfo(1),
            'user_order' => $this->testUserService->getUserOrder(1),
        ];
    }
}
