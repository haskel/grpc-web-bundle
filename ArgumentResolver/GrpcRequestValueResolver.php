<?php

namespace Haskel\GrpcWebBundle\ArgumentResolver;


use Haskel\GrpcWebBundle\Constant\ProtocolContentType;
use Haskel\GrpcWebBundle\Constant\ProtocolEncoding;
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
     */
    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        if (!$argument->getType()) {
            return;
        }

        if ($argument->getType() !== LengthPrefixedMessage::class
            && !is_subclass_of($argument->getType(), Message::class)) {
            return;
        }

        $contentType = $request->headers->get('content-type');
        if (!$contentType) {
            return;
        }

        [$protocol, $encoding] = explode('+', $contentType, 2);

        if ($protocol === ProtocolContentType::GRPC) {
            throw new BaseGrpcException('Can\'t process common gRPC. Only gRPC-Web is supported');
        }
        if (!in_array($protocol, ProtocolContentType::GRPC_WEB_CONTENT_TYPES)) {
            throw new BaseGrpcException('Only gRPC-Web is supported');
        }
        if ($encoding !== null && $encoding !== ProtocolEncoding::PROTOBUF) {
            throw new BaseGrpcException('Only protobuf encoding is supported');
        }

        $content = match ($protocol) {
            ProtocolContentType::GRPC_WEB_TEXT => base64_decode($request->getContent()),
            ProtocolContentType::GRPC_WEB => $request->getContent(),
            default => $request->getContent(),
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
