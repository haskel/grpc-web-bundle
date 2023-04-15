<?php

declare(strict_types=1);

namespace Haskel\GrpcWebBundle;

use Haskel\GrpcWebBundle\Message\StatusCode;
use Google\Protobuf\GPBEmpty;
use Google\Protobuf\Internal\Message;

class GrpcResponse
{
    /**
     * @param array<string, string> $metadata
     */
    public function __construct(
        private Message $message = new GPBEmpty(),
        private StatusCode $status = StatusCode::Ok,
        private ?string $statusMessage = null,
        private array $metadata = [],
    ) {
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
