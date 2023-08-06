<?php
declare(strict_types=1);

namespace Haskel\GrpcWebBundle\Constant;

class ProtocolContentType
{
    public const GRPC = 'application/grpc';
    public const GRPC_WEB = 'application/grpc-web';
    public const GRPC_WEB_TEXT = 'application/grpc-web-text';

    public const GRPC_WEB_CONTENT_TYPES = [
        self::GRPC_WEB,
        self::GRPC_WEB_TEXT,
    ];
}
