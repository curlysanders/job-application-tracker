<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Vacancy;

use Brick\Money\Currency;
use Brick\Money\Money;

final readonly class SalaryRange
{
    private function __construct(
        private ?Money $minimum,
        private ?Money $maximum,
        private Currency $currency,
    ) {
    }

    public static function fromDecimals(?string $minimum, ?string $maximum, string $currencyCode = 'EUR'): ?self
    {
        $currency = Currency::of($currencyCode);
        $minimumMoney = null === $minimum ? null : Money::of($minimum, $currency);
        $maximumMoney = null === $maximum ? null : Money::of($maximum, $currency);

        if (null === $minimumMoney && null === $maximumMoney) {
            return null;
        }

        if ((null !== $minimumMoney && !$minimumMoney->isPositive())
            || (null !== $maximumMoney && !$maximumMoney->isPositive())) {
            throw new \InvalidArgumentException('A salary amount must be positive.');
        }

        if (null !== $minimumMoney && null !== $maximumMoney && $minimumMoney->isGreaterThan($maximumMoney)) {
            throw new \InvalidArgumentException('The minimum salary cannot exceed the maximum salary.');
        }

        return new self($minimumMoney, $maximumMoney, $currency);
    }

    public function getMinimum(): ?Money
    {
        return $this->minimum;
    }

    public function getMaximum(): ?Money
    {
        return $this->maximum;
    }

    public function getCurrencyCode(): string
    {
        return $this->currency->getCurrencyCode();
    }
}
