<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Shared;

/** A framework-independent RFC 4122 UUID identifier. */
final readonly class Identifier implements \Stringable
{
    private function __construct(private string $value)
    {
    }

    public static function v7(): self
    {
        $milliseconds = (int) floor(microtime(true) * 1000);
        $bytes = pack('Nn', $milliseconds >> 16, $milliseconds & 0xFFFF).random_bytes(10);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x70);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return self::fromBinary($bytes);
    }

    public static function fromString(string $value): self
    {
        $value = strtolower($value);
        if (!self::isValid($value)) {
            throw new \InvalidArgumentException('The identifier must be an RFC 4122 UUID.');
        }

        return new self($value);
    }

    public static function fromBinary(string $value): self
    {
        if (16 !== strlen($value)) {
            throw new \InvalidArgumentException('A binary identifier must contain 16 bytes.');
        }
        $hex = bin2hex($value);

        return new self(sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20)));
    }

    public static function isValid(string $value): bool
    {
        return 1 === preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value);
    }

    public function toRfc4122(): string
    {
        return $this->value;
    }

    public function toBinary(): string
    {
        $binary = hex2bin(str_replace('-', '', $this->value));
        if (false === $binary) {
            throw new \LogicException('Invalid identifier state.');
        }

        return $binary;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
