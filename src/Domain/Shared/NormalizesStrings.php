<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Shared;

trait NormalizesStrings
{
    private static function required(string $value, string $message): string
    {
        $value = trim($value);
        if ('' === $value) {
            throw new \InvalidArgumentException($message);
        }

        return $value;
    }

    private static function optional(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $value = trim($value);

        return '' === $value ? null : $value;
    }
}
