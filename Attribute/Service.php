<?php

declare(strict_types=1);

namespace Haskel\GrpcWebBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Service
{
    public function __construct(
        public readonly string $path = '',
    ) {
    }
}
