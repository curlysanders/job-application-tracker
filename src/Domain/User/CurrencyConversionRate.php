<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\User;

final readonly class CurrencyConversionRate
{
    public function __construct(public string $sourceCurrency, public string $targetCurrency, public string $rate)
    {
        if (
            1 !== preg_match('/^[A-Z]{3}$/', $sourceCurrency)
            || 1 !== preg_match('/^[A-Z]{3}$/', $targetCurrency)
            || 1 !== preg_match('/^\d+(?:\.\d+)?$/', $rate)
            /* @phpstan-ignore-next-line Regex establishes the numeric-string invariant. */
            || 1 !== bccomp($rate, '0', 12)
        ) {
            throw new \InvalidArgumentException('A currency conversion rate must be positive.');
        }
    }
}
