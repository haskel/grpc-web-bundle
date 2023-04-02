<?php

declare(strict_types=1);

namespace Haskel\GrpcWebBundle\Exception;

use Exception;

class BaseGrpcException extends Exception implements GrpcException
{
}
