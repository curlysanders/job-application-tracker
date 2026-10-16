<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Vacancy;

final class CurrentIsoCurrencies
{
    /** @return list<CurrencyOption> */
    public static function all(): array
    {
        return array_map(static fn (string $code): CurrencyOption => new CurrencyOption($code, $code), ['EUR', 'USD', 'GBP', 'CHF', 'CAD', 'AUD', 'JPY']);
    }

    public static function find(string $currencyCode): ?CurrencyOption
    {
        return 1 === preg_match('/^[A-Z]{3}$/', $currencyCode) ? new CurrencyOption($currencyCode, $currencyCode) : null;
    }
}
