<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

final class AiValidation
{
    public static function requiredText(string $value, string $name, int $maximumLength): void
    {
        if ('' === trim($value) || mb_strlen($value) > $maximumLength) {
            throw new \InvalidArgumentException(sprintf('%s must be non-blank and no longer than %d characters.', $name, $maximumLength));
        }
    }

    public static function confidence(float $value): void
    {
        if (!is_finite($value) || $value < 0 || $value > 1) {
            throw new \InvalidArgumentException('Confidence must be a finite value between 0 and 1.');
        }
    }

    /** @param array<mixed> $values */
    public static function listOf(array $values, string $type, string $name): void
    {
        if (!array_is_list($values) || [] !== array_filter($values, static fn (mixed $value): bool => !$value instanceof $type)) {
            throw new \InvalidArgumentException(sprintf('%s must be a list of %s objects.', $name, $type));
        }
    }
}
