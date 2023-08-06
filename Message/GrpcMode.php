<?php

declare(strict_types=1);

namespace Haskel\GrpcWebBundle\Message;

use Haskel\GrpcWebBundle\Constant\ProtocolContentType;
use InvalidArgumentException;

enum GrpcMode: string
{
    case Grpc = 'grpc';
    case GrpcWeb = 'grpc-web';
    case GrpcWebText = 'grpc-web-text';

    public static function getByContentType(?string $contentType = null): self
    {
        return match ($contentType) {
            ProtocolContentType::GRPC => self::Grpc,
            ProtocolContentType::GRPC_WEB => self::GrpcWeb,
            ProtocolContentType::GRPC_WEB_TEXT => self::GrpcWebText,
            default => throw new InvalidArgumentException('Unknown content type: ' . $contentType),
        };
    }
}
