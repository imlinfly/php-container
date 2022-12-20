<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/12/15 15:35:51
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Exception;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends \Exception implements NotFoundExceptionInterface
{
}
