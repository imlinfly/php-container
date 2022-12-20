<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/12/15 15:10:07
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Example;

class TestOrderService
{
    public function __construct(private TestUserService $testUserService)
    {

    }

    /**
     * 获取订单信息
     * @param int $orderNo
     * @return array
     */
    public function getOrderInfo(int $orderNo)
    {
        return [
            'order_no' => $orderNo,
            'user_info' => $this->testUserService->getUserInfo(1),
        ];
    }

    /**
     * 获取用户订单列表
     * @param int $userId
     * @return array[]
     */
    public function getUserOrderList(int $userId)
    {
        return [
            $this->getOrderInfo(1),
            $this->getOrderInfo(2),
            $this->getOrderInfo(3),
        ];
    }
}
