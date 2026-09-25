<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Currency;

final readonly class ExchangeRates
{
    /** @param array<string, string> $ratesPerEuro */
    public function __construct(public array $ratesPerEuro, public \DateTimeImmutable $fetchedAt)
    {
    }
}
