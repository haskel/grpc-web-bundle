<?php

declare(strict_types=1);

namespace Haskel\GrpcWebBundle;

use Haskel\GrpcWebBundle\Message\StatusCode;
use Symfony\Component\HttpFoundation\Response;

class HttpToGrpcStatusMapping
{
    public const MAPPING = [
        [Response::HTTP_BAD_REQUEST => StatusCode::InvalidArgument],
        [Response::HTTP_UNAUTHORIZED => StatusCode::Unauthenticated],
        [Response::HTTP_FORBIDDEN => StatusCode::PermissionDenied],
        [Response::HTTP_NOT_FOUND => StatusCode::NotFound],
        [Response::HTTP_METHOD_NOT_ALLOWED => StatusCode::Unimplemented],
        [Response::HTTP_NOT_ACCEPTABLE => StatusCode::FailedPrecondition],
        [Response::HTTP_REQUEST_TIMEOUT => StatusCode::DeadlineExceeded],
        [Response::HTTP_CONFLICT => StatusCode::Aborted],
        [Response::HTTP_GONE => StatusCode::OutOfRange],
        [Response::HTTP_LENGTH_REQUIRED => StatusCode::FailedPrecondition],
        [Response::HTTP_PRECONDITION_FAILED => StatusCode::FailedPrecondition],
    ];
}
