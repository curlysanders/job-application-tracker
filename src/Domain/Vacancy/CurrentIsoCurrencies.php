<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Vacancy;

use Brick\Money\CurrencyType;
use Brick\Money\IsoCurrencyProvider;

final class CurrentIsoCurrencies
{
    /** @return list<CurrencyOption> */
    public static function all(): array
    {
        $currencies = [];
        foreach (IsoCurrencyProvider::getInstance()->getAvailableCurrencies() as $currency) {
            if (CurrencyType::IsoCurrent === $currency->getCurrencyType()) {
                $currencies[] = new CurrencyOption($currency->getCurrencyCode(), $currency->getName());
            }
        }

        return $currencies;
    }

    public static function find(string $currencyCode): ?CurrencyOption
    {
        foreach (IsoCurrencyProvider::getInstance()->getAvailableCurrencies() as $currency) {
            if ($currencyCode === $currency->getCurrencyCode()) {
                return new CurrencyOption($currency->getCurrencyCode(), $currency->getName());
            }
        }

        return null;
    }
}
