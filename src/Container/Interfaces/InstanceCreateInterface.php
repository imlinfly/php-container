<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/12/16 16:32:45
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Container\Interfaces;

interface InstanceCreateInterface
{
    /**
     * Called when the container creates an instance
     *
     * The parameters passed to the container.
     * Note: the parameters here are those passed to the container,
     * not the complete parameters of the class constructor obtained by the container
     * @param array $arguments
     * @return void
     */
    public function instanceCreate(array $arguments = []): void;
}
