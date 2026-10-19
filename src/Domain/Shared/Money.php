<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Shared;

final readonly class Money
{
    /** @param numeric-string $amount */
    private function __construct(
        private string $amount,
        private string $currencyCode,
    ) {
    }

    public static function of(
        string $amount,
        string $currencyCode,
    ): self {
        if (1 !== preg_match('/^[A-Z]{3}$/', $currencyCode) || 1 !== preg_match('/^-?\d+(?:\.\d{1,2})?$/', $amount)) {
            throw new \InvalidArgumentException('Invalid money value.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $negative = str_starts_with($whole, '-');
        $whole = ltrim($negative ? substr($whole, 1) : $whole, '0');

        /* @phpstan-ignore-next-line The validated and normalized value is a numeric string. */
        return new self(($negative ? '-' : '').('' === $whole ? '0' : $whole).'.'.str_pad($fraction, 2, '0'), $currencyCode);
    }

    /** @return numeric-string */
    public function amount(): string
    {
        return $this->amount;
    }

    public function currencyCode(): string
    {
        return $this->currencyCode;
    }

    public function isPositive(): bool
    {
        return self::compareAmounts($this->amount, '0.00') > 0;
    }

    public function compare(self $other): int
    {
        if ($this->currencyCode !== $other->currencyCode) {
            throw new \InvalidArgumentException('Currencies must match.');
        }

        return self::compareAmounts($this->amount, $other->amount);
    }

    private static function compareAmounts(
        string $left,
        string $right,
    ): int {
        $leftNegative = str_starts_with($left, '-');
        $rightNegative = str_starts_with($right, '-');
        if ($leftNegative !== $rightNegative) {
            return $leftNegative ? -1 : 1;
        }

        [$leftWhole] = explode('.', ltrim($left, '-'), 2);
        [$rightWhole] = explode('.', ltrim($right, '-'), 2);
        $comparison = strcmp(str_pad($leftWhole, 20, '0', STR_PAD_LEFT), str_pad($rightWhole, 20, '0', STR_PAD_LEFT));
        if (0 === $comparison) {
            $comparison = strcmp(substr($left, -2), substr($right, -2));
        }

        return $leftNegative ? -$comparison : $comparison;
    }
}
