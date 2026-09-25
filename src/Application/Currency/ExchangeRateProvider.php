<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Currency;

interface ExchangeRateProvider
{
    /** @return list<string> */
    public function supportedCurrencyCodes(): array;

    public function latestRates(): ?ExchangeRates;
}
