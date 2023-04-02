<?php

declare(strict_types=1);

namespace App\Haskel\GrpcWebBundle\Listener;

use Haskel\GrpcWebBundle\Exception\BaseGrpcException;
use Haskel\GrpcWebBundle\GrpcResponse;
use Google\Protobuf\Internal\Message;
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

            $trailers = [
                'grpc-status' => '0',
                'grpc-message' => 'OK',
            ];

//            $response = new StreamedResponse(
//                function () use ($result) {
//                    echo (new LengthPrefixedMessage($result->serializeToString()))->encode();
//                },
//                Response::HTTP_OK,
//                [
//                    'Content-Type' => 'application/grpc-web+proto',
//                    'grpc-encoding' => 'identity',
//                    'Trailer' => implode(', ', array_keys($trailers)),
//                ]
//            );

            $payload = (new LengthPrefixedMessage($result->serializeToString()))->encode();
            $response = new Response(
                $payload,
//                base64_encode($payload),
//                base64_encode((new LengthPrefixedMessage($result->serializeToString()))->encode()),
//                $result->serializeToString(),
                Response::HTTP_OK,
                [
                    'Content-Length' => strlen($payload),
                    'Content-Type' => 'application/grpc-web+proto',
//                    'Content-Type' => 'application/grpc-web-text',
//                    'content-encoding' => 'identity',
//                    'Trailer' => implode(', ', array_keys($trailers)),
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
                    $result[$key] = $value->getValue();
                }
                if ($value instanceof StatusMessage) {
                    $result[$key] = $value->getValue();
                }
                if ($value instanceof MetadataRecord) {
                    $result[$key] = $value->getValue();
                }
            }

            $event->setResponse($result);
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
