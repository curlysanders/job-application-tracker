<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\User;

use Brick\Math\BigDecimal;
use Brick\Money\Currency;

final readonly class CurrencyConversionRate
{
    public function __construct(public string $sourceCurrency, public string $targetCurrency, public string $rate)
    {
        Currency::of($sourceCurrency);
        Currency::of($targetCurrency);
        if (!BigDecimal::of($rate)->isGreaterThan(0)) {
            throw new \InvalidArgumentException('A currency conversion rate must be positive.');
        }
    }
}
