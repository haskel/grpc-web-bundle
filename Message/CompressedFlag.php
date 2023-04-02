<?php

declare(strict_types=1);

namespace Haskel\GrpcWebBundle\Message;

enum CompressedFlag: int
{
    case Uncompressed = 0;
    case Compressed = 1;
}
