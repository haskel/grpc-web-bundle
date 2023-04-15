<?php

declare(strict_types=1);

namespace Haskel\GrpcWebBundle\Listener;

use Haskel\GrpcWebBundle\Exception\BaseGrpcException;
use Haskel\GrpcWebBundle\GrpcResponse;
use Google\Protobuf\Internal\Message;
use Haskel\GrpcWebBundle\Message\LengthPrefixedMessage;
use Haskel\GrpcWebBundle\Message\MetadataRecord;
use Haskel\GrpcWebBundle\Message\StatusCode;
use Haskel\GrpcWebBundle\Message\StatusMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(KernelEvents::VIEW)]
class ResponseListener
{
    public function onKernelView(ViewEvent $event): void
    {
        $result = $event->getControllerResult();
        if ($result instanceof Message) {

            $contentType = $event->getRequest()->headers->get('content-type');
            if (!$contentType || !str_starts_with($contentType, 'application/grpc')) {
                return;
            }

            [$protocol, $encoding] = explode('+', $contentType, 2);

            $message = (new LengthPrefixedMessage($result->serializeToString()))->encode();
            $payload = match ($protocol) {
                'application/grpc-web-text' => base64_decode($message),
                'application/grpc-web' => $message,
            };

            $response = new Response(
                $message,
                Response::HTTP_OK,
                [
                    'Content-Length' => strlen($payload),
                    'Content-Type' => $contentType,
                ]
            );

            $response->headers->set('grpc-status', '0');
            $response->headers->set('grpc-message', 'OK');

            $event->setResponse($response);
        }

        if ($result instanceof LengthPrefixedMessage) {
            $response = new Response(
                $result->encode(),
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/grpc-web+proto',
                    'grpc-encoding' => 'identity',
                ]
            );

            $event->setResponse($response);
        }

        if ($result instanceof GrpcResponse) {
            $response = new Response(
                $result->getMessage()->serializeToString(),
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/grpc-web+proto',
                    'grpc-encoding' => 'identity',
                ]
            );

            $event->setResponse($response);
        }

        if (is_array($result)) {
            foreach ($result as $key => $value) {
                if ($value instanceof Message) {
                    $result[$key] = $value->serializeToString();
                }
                if ($value instanceof LengthPrefixedMessage) {
                    $result[$key] = $value->encode();
                }
                if ($value instanceof StatusCode) {
                    $result[$key] = $value->value;
                }
                if ($value instanceof StatusMessage) {
                    $result[$key] = $value->message;
                }
                if ($value instanceof MetadataRecord) {
                    $result[$key] = $value->value;
                }
            }

            $event->setResponse(new Response($result));
        }
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if ($exception instanceof BaseGrpcException) {
            $response = new Response(
                $exception->getMessage(),
                Response::HTTP_BAD_REQUEST,
                [
                    'Content-Type' => 'application/grpc-web+proto',
                    'grpc-encoding' => 'identity',
                ]
            );

            $event->setResponse($response);
        }
    }
}
