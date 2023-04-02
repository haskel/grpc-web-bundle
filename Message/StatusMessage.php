<?php

declare(strict_types=1);

namespace Haskel\GrpcWebBundle\Message;

class StatusMessage
{
    public function __construct(
        public readonly string $message,
    ) {
    }
}
