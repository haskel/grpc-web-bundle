<?php

declare(strict_types=1);

namespace Haskel\GrpcWebBundle\Message;

class MetadataRecord
{
    public function __construct(
        public readonly string $key,
        public readonly string $value,
    ) {
    }
}
