<?php

declare(strict_types=1);

namespace Haskel\GrpcWebBundle\Listener;

use Google\Protobuf\GPBEmpty;
use Haskel\GrpcWebBundle\Constant\RequestAttribute;
use Haskel\GrpcWebBundle\Exception\AbortedException;
use Haskel\GrpcWebBundle\Exception\AlreadyExistsException;
use Haskel\GrpcWebBundle\Exception\CancelledException;
use Haskel\GrpcWebBundle\Exception\DataLossException;
use Haskel\GrpcWebBundle\Exception\DeadlineExceededException;
use Haskel\GrpcWebBundle\Exception\FailedPreconditionException;
use Haskel\GrpcWebBundle\Exception\InternalException;
use Haskel\GrpcWebBundle\Exception\InvalidArgumentException;
use Haskel\GrpcWebBundle\Exception\NotFoundException;
use Haskel\GrpcWebBundle\Exception\OutOfRangeException;
use Haskel\GrpcWebBundle\Exception\PermissionDeniedException;
use Haskel\GrpcWebBundle\Exception\ResourceExhaustedException;
use Haskel\GrpcWebBundle\Exception\UnauthenticatedException;
use Haskel\GrpcWebBundle\Exception\UnavailableException;
use Haskel\GrpcWebBundle\Exception\UnimplementedException;
use Haskel\GrpcWebBundle\Exception\UnknownErrorException;
use Haskel\GrpcWebBundle\GrpcResponse;
use Google\Protobuf\Internal\Message;
use Haskel\GrpcWebBundle\Message\GrpcMode;
use Haskel\GrpcWebBundle\Message\LengthPrefixedMessage;
use Haskel\GrpcWebBundle\Message\MetadataRecord;
use Haskel\GrpcWebBundle\Message\StatusCode;
use Haskel\GrpcWebBundle\Message\StatusMessage;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;

class ResponseListener
{
    /** @var array<string, StatusCode> */
    protected array $exceptionToCodeMap = [
        CancelledException::class => StatusCode::Cancelled,
        UnknownErrorException::class => StatusCode::Unknown,
        InvalidArgumentException::class => StatusCode::InvalidArgument,
        DeadlineExceededException::class => StatusCode::DeadlineExceeded,
        NotFoundException::class => StatusCode::NotFound,
        AlreadyExistsException::class => StatusCode::AlreadyExists,
        PermissionDeniedException::class => StatusCode::PermissionDenied,
        ResourceExhaustedException::class => StatusCode::ResourceExhausted,
        FailedPreconditionException::class => StatusCode::FailedPrecondition,
        AbortedException::class => StatusCode::Aborted,
        OutOfRangeException::class => StatusCode::OutOfRange,
        UnimplementedException::class => StatusCode::Unimplemented,
        InternalException::class => StatusCode::Internal,
        UnavailableException::class => StatusCode::Unavailable,
        DataLossException::class => StatusCode::DataLoss,
        UnauthenticatedException::class => StatusCode::Unauthenticated,
    ];

    /** @var array<string, callable> */
    protected array $exceptionHandlers = [];

    public function __construct(private LoggerInterface $logger)
    {
    }

    public function onKernelView(ViewEvent $event): void
    {
        $result = $event->getControllerResult();

        if ($result instanceof GrpcResponse) {
            $event->setResponse($result);
            return;
        }

        if ($result instanceof LengthPrefixedMessage) {
            throw new RuntimeException('LengthPrefixedMessage as return type is not implemented');
        }

        if ($result instanceof Message) {
            $event->setResponse(new GrpcResponse($result));
            return;
        }

        if (is_iterable($result)) {
            $response = new GrpcResponse();

            foreach ($result as $key => $value) {
                if ($value instanceof Message) {
                    $response->setMessage($value);
                }
                if ($value instanceof StatusCode) {
                    $response->setStatus($value);
                }
                if ($value instanceof StatusMessage) {
                    $response->setStatusMessage($value->message);
                }
                if ($value instanceof MetadataRecord) {
                    $response->addMetadata($value->key, $value->value);
                }
            }

            $event->setResponse($response);
        }
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $this->logger->error($exception->getMessage(), ['exception' => $exception]);

        if (isset($this->exceptionHandlers[$exception::class])) {
            $response = call_user_func($this->exceptionHandlers[$exception::class], $exception);
            $response ??= new GrpcResponse(status: StatusCode::Unknown,statusMessage: 'Unknown error');
            $event->setResponse($response);
            return;
        }

        $responseMessage = new GPBEmpty();
        $statusCode = $this->exceptionToCodeMap[$exception::class] ?? StatusCode::Unknown;

        $response = new GrpcResponse(
            $responseMessage,
            $statusCode,
            $exception->getMessage(),
        );

        $event->setResponse($response);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        if (!$response instanceof GrpcResponse) {
            return;
        }

        $type = GrpcMode::getByContentType(
            $event->getRequest()->headers->get('content-type')
        );

        $response->setType($type);
    }

    public function addExceptionToCodeMapping(string $exceptionClass, StatusCode $code): void
    {
        $this->exceptionToCodeMap[$exceptionClass] = $code;
    }

    public function addExceptionHandler(string $exceptionClass, callable $handler): void
    {
        $this->exceptionHandlers[$exceptionClass] = $handler;
    }
}
