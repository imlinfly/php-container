<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/12/15 15:11:21
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Example;

class TestInjectSelf
{
    /**
     * @param TestInjectSelf $testInjectSelf The injected circular dependency can only be a single instance
     * @param int $id
     */
    public function __construct(private TestInjectSelf $testInjectSelf, private int $id = 1)
    {
        // var_dump($this->testInjectSelf->getId() === $id);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return TestInjectSelf
     */
    public function getTestInjectSelf(): TestInjectSelf
    {
        return $this->testInjectSelf;
    }

    /*public static function main(): void
    {
        FacadeContainer::getSingle(static::class);
        FacadeContainer::newInstance(static::class, [2]);
    }*/
}
