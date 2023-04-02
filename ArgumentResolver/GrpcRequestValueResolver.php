<?php

namespace App\Haskel\GrpcWebBundle\ArgumentResolver;


use Haskel\GrpcWebBundle\Exception\BaseGrpcException;
use Haskel\GrpcWebBundle\Message\LengthPrefixedMessage;
use Generator;
use Google\Protobuf\Internal\Message;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class GrpcRequestValueResolver implements ValueResolverInterface
{
    /**
     * {@inheritdoc}
     *
     * todo: check headers and type grpc mode
     */
    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        if (!$argument->getType()) {
            return;
        }

        $contentType = $request->headers->get('content-type');
        if (!$contentType || !str_starts_with($contentType, 'application/grpc')) {
            return;
        }

        [$protocol, $encoding] = explode('+', $contentType, 2);

        if ($protocol === 'application/grpc') {
            throw new BaseGrpcException('Only gRPC-Web is supported');
        }
        if ($encoding !== null && $encoding !== 'proto') {
            throw new BaseGrpcException('Only protobuf encoding is supported');
        }

        $content = match ($protocol) {
            'application/grpc-web-text' => base64_decode($request->getContent()),
            'application/grpc-web' => $request->getContent(),
        };

        if ($argument->getType() === LengthPrefixedMessage::class) {
            yield LengthPrefixedMessage::decode($content);
        }

        if (is_subclass_of($argument->getType(), Message::class)) {
            $lengthPrefixedMessage = LengthPrefixedMessage::decode($content);
            $message = new ($argument->getType());
            $message->mergeFromString($lengthPrefixedMessage->getMessage());

            yield $message;
        }
    }

}
