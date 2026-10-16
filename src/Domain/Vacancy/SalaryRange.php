<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Vacancy;

use CurlySanders\JobApplicationTracker\Domain\Shared\Money;

final readonly class SalaryRange
{
    private function __construct(
        private ?Money $minimum,
        private ?Money $maximum,
        private string $currencyCode,
    ) {
    }

    public static function fromDecimals(?string $minimum, ?string $maximum, string $currencyCode = 'EUR'): ?self
    {
        $minimumMoney = null === $minimum ? null : Money::of($minimum, $currencyCode);
        $maximumMoney = null === $maximum ? null : Money::of($maximum, $currencyCode);

        if (null === $minimumMoney && null === $maximumMoney) {
            return null;
        }

        if ((null !== $minimumMoney && !$minimumMoney->isPositive())
            || (null !== $maximumMoney && !$maximumMoney->isPositive())) {
            throw new \InvalidArgumentException('A salary amount must be positive.');
        }

        if (null !== $minimumMoney && null !== $maximumMoney && $minimumMoney->compare($maximumMoney) > 0) {
            throw new \InvalidArgumentException('The minimum salary cannot exceed the maximum salary.');
        }

        return new self($minimumMoney, $maximumMoney, $currencyCode);
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
        return $this->currencyCode;
    }
}
