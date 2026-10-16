<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Shared;

final class WebAddress
{
    public static function optionalHttpUrl(?string $value, string $label): ?string
    {
        $value = null === $value ? null : trim($value);
        if (null === $value || '' === $value) {
            return null;
        }

        $parts = parse_url($value);
        if (!is_array($parts) || !isset($parts['host']) || !in_array($parts['scheme'] ?? null, ['http', 'https'], true)) {
            throw new \InvalidArgumentException(sprintf('%s must be a valid HTTP URL.', $label));
        }

        return $value;
    }
}
