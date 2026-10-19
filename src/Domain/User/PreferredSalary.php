<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\User;

use CurlySanders\JobApplicationTracker\Domain\Shared\Money;

final readonly class PreferredSalary
{
    private function __construct(
        public string $currencyCode,
        public ?Money $minimum,
    ) {
        if (1 !== preg_match('/^[A-Z]{3}$/', $currencyCode)) {
            throw new \InvalidArgumentException('The currency code must be ISO 4217.');
        }
        if (null !== $minimum && $currencyCode !== $minimum->currencyCode()) {
            throw new \InvalidArgumentException('The preferred salary currency must match the salary amount.');
        }
        if (null !== $minimum && !$minimum->isPositive()) {
            throw new \InvalidArgumentException('The gross monthly salary must be positive.');
        }
    }

    public static function fromDecimal(
        ?string $minimum,
        string $currencyCode,
    ): self {
        return new self($currencyCode, null === $minimum ? null : Money::of($minimum, $currencyCode));
    }

    public function getMinimumDecimal(): ?string
    {
        return $this->minimum?->amount();
    }

    public function evaluate(
        Money $grossSalary,
        ?CurrencyConversionRate $conversionRate = null,
    ): SalaryFitStatus {
        if (null === $this->minimum) {
            return SalaryFitStatus::NotConfigured;
        }
        if ($grossSalary->currencyCode() !== $this->currencyCode) {
            if (
                null === $conversionRate
                || $conversionRate->targetCurrency !== $this->currencyCode
                || $conversionRate->sourceCurrency !== $grossSalary->currencyCode()
            ) {
                return SalaryFitStatus::ConversionUnavailable;
            }
            /** @phpstan-ignore-next-line Both value objects validate decimal strings at construction. */
            $grossSalary = bcmul($grossSalary->amount(), $conversionRate->rate, 12)
                    |> (fn ($x) => bcround($x, 2))
                    |> (fn ($x) => Money::of($x, $this->currencyCode));
        }

        return $grossSalary->compare($this->minimum) >= 0
            ? SalaryFitStatus::MeetsTarget
            : SalaryFitStatus::BelowTarget;
    }
}
