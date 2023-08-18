<?php
declare(strict_types=1);

namespace Haskel\GrpcWebBundle\Service;

use Haskel\GrpcWebBundle\GrpcResponse;

interface GrpcResponseBuilderInterface
{
    public function build(...$arguments): GrpcResponse;
}
