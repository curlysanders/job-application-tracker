<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Currency;

final readonly class ExchangeRates
{
    /** @param array<string, string> $ratesPerEuro */
    public function __construct(public array $ratesPerEuro, public \DateTimeImmutable $fetchedAt)
    {
        foreach ($ratesPerEuro as $currency => $rate) {
            if (1 !== preg_match('/^[A-Z]{3}$/', $currency) || 1 !== preg_match('/^\d+(?:\.\d+)?$/', $rate)) {
                throw new \InvalidArgumentException('Exchange rates must map ISO 4217 currency codes to positive decimal values.');
            }
            /* @phpstan-ignore-next-line Regex above establishes the numeric-string invariant. */
            if (1 !== bccomp($rate, '0', 12)) {
                throw new \InvalidArgumentException('Exchange rates must be positive.');
            }
        }
    }
}
