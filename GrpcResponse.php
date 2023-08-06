<?php

declare(strict_types=1);

namespace Haskel\GrpcWebBundle;

use Haskel\GrpcWebBundle\Message\CompressedFlag;
use Haskel\GrpcWebBundle\Message\GrpcMode;
use Haskel\GrpcWebBundle\Message\LengthPrefixedMessage;
use Haskel\GrpcWebBundle\Message\StatusCode;
use Google\Protobuf\GPBEmpty;
use Google\Protobuf\Internal\Message;
use Symfony\Component\HttpFoundation\Response;

class GrpcResponse extends Response
{
    private string $payload;

    public function __construct(
        Message       $message = new GPBEmpty(),
        StatusCode    $status = StatusCode::Ok,
        string        $statusMessage = 'OK',
        array         $trailers = [],
        array         $headers = [],
        private GrpcMode $type = GrpcMode::GrpcWeb,
        private array $metadata = [],
    ) {
        $trailers = [
            'grpc-status' => $status->value,
            'grpc-message' => $statusMessage,
            ...$trailers,
        ];

        // todo: clean key and value
        $trailersBody = implode(
            "",
            array_map(
                fn ($key, $value) => $key . ': ' . str_replace("\n", " ", (string)$value) . "\r\n",
                array_keys($trailers),
                $trailers
            )
        );

        $this->payload = (new LengthPrefixedMessage($message->serializeToString()))->encode()
            . (new LengthPrefixedMessage($trailersBody, CompressedFlag::Compressed))->encode();

        $content = $this->payload;
        if ($type === GrpcMode::GrpcWebText) {
            $content = base64_encode($this->payload);
        }

        $contentType = 'application/grpc-web';
        if ($type === GrpcMode::GrpcWebText) {
            $contentType = 'application/grpc-web-text';
        }

        parent::__construct(
            $content,
            Response::HTTP_OK, // @todo: map statuses grpc <-> http
            [
                'Content-Length' => strlen($content),
                'Content-Type' => $contentType,
                ...$headers,
            ]
        );
    }

    public function setType(GrpcMode $type): self
    {
        $content = $this->payload;
        if ($type === GrpcMode::GrpcWebText) {
            $content = base64_encode($this->payload);
        }
        $this->setContent($content);
        $this->headers->set('Content-Length', (string)strlen($content));

        $contentType = 'application/grpc-web';
        if ($type === GrpcMode::GrpcWebText) {
            $contentType = 'application/grpc-web-text';
        }
        $this->headers->set('Content-Type', $contentType);

        return $this;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function setMessage(Message $message): GrpcResponse
    {
        $this->message = $message;

        return $this;
    }

    public function getStatus(): StatusCode
    {
        return $this->status;
    }

    public function setStatus(StatusCode $status): GrpcResponse
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusMessage(): ?string
    {
        return $this->statusMessage;
    }

    public function setStatusMessage(?string $statusMessage): GrpcResponse
    {
        $this->statusMessage = $statusMessage;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, string> $metadata
     */
    public function setMetadata(array $metadata): GrpcResponse
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function addMetadata(string $key, string $value): GrpcResponse
    {
        $this->metadata[$key] = $value;

        return $this;
    }
}
