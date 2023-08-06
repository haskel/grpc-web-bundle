<?php

declare(strict_types=1);

namespace Haskel\GrpcWebBundle\Message;

enum CompressedFlag: int
{
    case Uncompressed = 0x00;
    case Compressed = 0x80;
}
