<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/12/14 14:00:56
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Test;

use LinFly\Container;
use LinFly\Example\TestController;
use LinFly\Example\TestInjectSelf;
use LinFly\FacadeContainer;
use PHPUnit\Framework\TestCase;
use stdClass;

class ContainerTest extends TestCase
{

    public function testHasInstance()
    {
        $this->assertFalse(FacadeContainer::hasInstance('test'));
        FacadeContainer::definition('test', new stdClass());
        $this->assertTrue(FacadeContainer::hasInstance('test'));
    }

    public function testInvokeClass()
    {
        $this->assertInstanceOf(stdClass::class, FacadeContainer::invokeClass(stdClass::class));
    }

    public function testBindCallbackBeforeCall()
    {
        $instance = null;
        FacadeContainer::bindCallbackBeforeCall(stdClass::class, function ($ins) use (&$instance) {
            $instance = $ins;
        });
        $testInstance = FacadeContainer::newInstance(stdClass::class);
        $this->assertInstanceOf(stdClass::class, $instance);
        $this->assertInstanceOf(stdClass::class, $testInstance);
    }

    public function testBindCallbackAfterCall()
    {
        $instance = null;
        FacadeContainer::bindCallbackAfterCall(stdClass::class, function ($ins) use (&$instance) {
            $instance = $ins;
        });
        $testInstance = FacadeContainer::newInstance(stdClass::class);
        $this->assertInstanceOf(stdClass::class, $instance);
        $this->assertInstanceOf(stdClass::class, $testInstance);
    }

    public function testIsSingle()
    {
        $this->assertFalse(FacadeContainer::isSingle(TestExample::class));
        FacadeContainer::getSingle(TestExample::class);
        $this->assertTrue(FacadeContainer::isSingle(TestExample::class));
    }

    public function testInvokeFunction()
    {
        $this->assertEquals('test', FacadeContainer::invokeFunction(function () {
            return 'test';
        }));
    }

    public function testGet()
    {
        $this->assertInstanceOf(Container::class, FacadeContainer::get(Container::class));
    }

    public function testDefinition()
    {
        FacadeContainer::definition('definitions', 'TestDefinition');
        $this->assertEquals('TestDefinition', FacadeContainer::getDefinition('definitions'));
    }

    public function testGetDefinition()
    {
        FacadeContainer::definition('get_definitions', 'TestGetDefinition');
        $this->assertEquals('TestGetDefinition', FacadeContainer::getDefinition('get_definitions'));
    }

    public function testHas()
    {
        $this->assertTrue(FacadeContainer::has(Container::class));
    }

    public function testMake()
    {
        $this->assertInstanceOf(Container::class, FacadeContainer::make(Container::class, newInstance: false));
    }

    public function testGetSingle()
    {
        $this->assertInstanceOf(TestExample::class, FacadeContainer::getSingle(TestExample::class));
    }

    public function testInvokeMethod()
    {
        $this->assertEquals('test', FacadeContainer::invokeMethod([
            TestExample::class,
            'testInvokeMethod'
        ]));

    }

    public function testNewInstance()
    {
        $this->assertEquals(1, FacadeContainer::getSingle(TestExample::class)->getId());
        $this->assertEquals(2, FacadeContainer::newInstance(TestExample::class, [2])->getId());
    }

    public function testInjectSelf()
    {
        /** @var TestInjectSelf $instance1 */
        $instance1 = FacadeContainer::getSingle(TestInjectSelf::class);
        /** @var TestInjectSelf $instance2 */
        $instance2 = FacadeContainer::newInstance(TestInjectSelf::class, [2]);

        $this->assertEquals(1, $instance1->getId());
        $this->assertTrue($instance1->getId() === $instance1->getTestInjectSelf()->getId());
        $this->assertEquals(2, $instance2->getId());
        $this->assertFalse($instance2->getId() === $instance2->getTestInjectSelf()->getId());
    }

    public function testController()
    {
        /** @var TestController $instance */
        $instance = FacadeContainer::getSingle(TestController::class);
        $info = $instance->info();
        $this->assertArrayHasKey('user_info', $info);
        $this->assertArrayHasKey('user_order', $info);
    }
}

class TestExample
{
    public function __construct(private int $id = 1)
    {
    }

    public function testInvokeMethod(): string
    {
        return 'test';
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
