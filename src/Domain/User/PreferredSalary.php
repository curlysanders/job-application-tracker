<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\User;

use Brick\Math\RoundingMode;
use Brick\Money\Currency;
use Brick\Money\Money;

final readonly class PreferredSalary
{
    private function __construct(private string $currencyCode, private ?Money $minimum)
    {
        Currency::of($currencyCode);

        if (null !== $minimum && $currencyCode !== $minimum->getCurrency()->getCurrencyCode()) {
            throw new \InvalidArgumentException('The preferred salary currency must match the salary amount.');
        }
        if (null !== $minimum && !$minimum->isPositive()) {
            throw new \InvalidArgumentException('The gross monthly salary must be positive.');
        }
    }

    public static function fromDecimal(?string $minimum, string $currencyCode): self
    {
        $currency = Currency::of($currencyCode);

        return new self($currency->getCurrencyCode(), null === $minimum ? null : Money::of($minimum, $currency));
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function getMinimum(): ?Money
    {
        return $this->minimum;
    }

    public function getMinimumDecimal(): ?string
    {
        return null === $this->minimum ? null : (string) $this->minimum->getAmount();
    }

    public function evaluate(Money $grossSalary, ?CurrencyConversionRate $conversionRate = null): SalaryFitStatus
    {
        if (null === $this->minimum) {
            return SalaryFitStatus::NotConfigured;
        }
        if ($grossSalary->getCurrency()->getCurrencyCode() !== $this->currencyCode) {
            if (
                null === $conversionRate
                || $conversionRate->targetCurrency !== $this->currencyCode
                || $conversionRate->sourceCurrency !== $grossSalary->getCurrency()->getCurrencyCode()
            ) {
                return SalaryFitStatus::ConversionUnavailable;
            }
            $currency = Currency::of($this->currencyCode);
            $grossSalary = Money::of(
                $grossSalary->getAmount()->multipliedBy($conversionRate->rate)->toScale($currency->getDefaultFractionDigits(), RoundingMode::HalfUp),
                $currency,
            );
        }

        return $grossSalary->isGreaterThanOrEqualTo($this->minimum)
            ? SalaryFitStatus::MeetsTarget
            : SalaryFitStatus::BelowTarget;
    }
}
