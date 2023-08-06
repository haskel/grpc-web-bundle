<?php

declare(strict_types=1);

namespace Haskel\GrpcWebBundle\Message;

use Haskel\GrpcWebBundle\Exception\BaseGrpcException;

class LengthPrefixedMessage
{
    public const HEADERS_LENGTH = 5;

    public const HEADER_COMPRESSED_POSITION = 0;
    public const HEADER_COMPRESSED_BYTES_COUNT = 1;

    public const HEADER_MESSAGE_LENGTH_START_POSITION = 1;
    public const HEADER_MESSAGE_LENGTH_BYTES_COUNT= 4;

    private CompressedFlag $compressedFlag;
    private int $messageLength;
    private string $message;

    public function __construct(
        string $message,
        CompressedFlag $compressedFlag = CompressedFlag::Uncompressed,
    ) {
        $this->compressedFlag = $compressedFlag;
        $this->messageLength = strlen($message);
        $this->message = $message;
    }

    public function encode(): string {
        return pack('c', $this->compressedFlag->value)
            . pack('N', $this->messageLength)
            . $this->message;
    }

    public static function decode(string $data): LengthPrefixedMessage
    {
        $compressedFlag = CompressedFlag::from(
            ord(substr($data, self::HEADER_COMPRESSED_POSITION, self::HEADER_COMPRESSED_BYTES_COUNT))
        );

        $messageLengthHeader = unpack(
            'N',
            substr($data, self::HEADER_MESSAGE_LENGTH_START_POSITION, self::HEADER_MESSAGE_LENGTH_BYTES_COUNT)
        );

        if (!$messageLengthHeader) {
            throw new BaseGrpcException('Message length header is empty');
        }

        $messageLength = $messageLengthHeader[1];
        $message = substr($data, self::HEADERS_LENGTH, $messageLength);

        return new LengthPrefixedMessage($message, $compressedFlag);
    }

    public static function encodeString(
        string $message,
        CompressedFlag $compressedFlag = CompressedFlag::Uncompressed
    ): string {
        $compressedFlag = pack('c', $compressedFlag->value);
        $messageLength = pack('N', $message);

        return $compressedFlag . $messageLength . $message;
    }


    public function getCompressedFlag(): CompressedFlag
    {
        return $this->compressedFlag;
    }

    public function getMessageLength(): int
    {
        return $this->messageLength;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
