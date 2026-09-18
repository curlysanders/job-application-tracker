<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Domain\User;

use CurlySanders\JobApplicationTracker\Domain\User\GrossMonthlySalary;
use CurlySanders\JobApplicationTracker\Domain\User\PreferredTransportMode;
use CurlySanders\JobApplicationTracker\Domain\User\SalaryFitStatus;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class UserPreferencesTest extends TestCase
{
    public function testSalaryIsStoredAndComparedExactly(): void
    {
        $user = new User();
        $user->updatePreferences(GrossMonthlySalary::fromDecimal('4500.00'), 45, PreferredTransportMode::PublicTransport);

        self::assertSame('4500.00', $user->getMinimumPreferredSalary()?->toDecimal());
        self::assertSame(SalaryFitStatus::MeetsTarget, $user->evaluatesSalary(GrossMonthlySalary::fromDecimal('4500.00')));
        self::assertSame(SalaryFitStatus::BelowTarget, $user->evaluatesSalary(GrossMonthlySalary::fromDecimal('4499.99')));
    }

    public function testUnconfiguredSalaryDoesNotProduceAMatch(): void
    {
        self::assertSame(
            SalaryFitStatus::NotConfigured,
            new User()->evaluatesSalary(GrossMonthlySalary::fromDecimal('4500.00')),
        );
    }

    public function testSalaryAndCommuteMustBePositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        GrossMonthlySalary::fromDecimal('0.00');
    }

    public function testCommuteMustBePositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new User()->updatePreferences(null, 0, null);
    }
}
