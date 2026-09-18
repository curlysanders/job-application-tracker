<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\User;

use Brick\Money\Money;

final readonly class GrossMonthlySalary
{
    private function __construct(private Money $money)
    {
    }

    public static function fromDecimal(string $amount): self
    {
        $money = Money::of($amount, 'EUR');

        if (!$money->isPositive()) {
            throw new \InvalidArgumentException('The gross monthly salary must be positive.');
        }

        return new self($money);
    }

    public function isAtLeast(self $other): bool
    {
        return $this->money->isGreaterThanOrEqualTo($other->money);
    }

    public function toDecimal(): string
    {
        return (string) $this->money->getAmount();
    }
}
