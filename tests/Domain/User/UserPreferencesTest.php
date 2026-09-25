<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Domain\User;

use Brick\Money\Money;
use CurlySanders\JobApplicationTracker\Domain\User\CurrencyConversionRate;
use CurlySanders\JobApplicationTracker\Domain\User\PreferredSalary;
use CurlySanders\JobApplicationTracker\Domain\User\PreferredTransportMode;
use CurlySanders\JobApplicationTracker\Domain\User\SalaryFitStatus;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class UserPreferencesTest extends TestCase
{
    public function testSalaryIsStoredAndComparedExactly(): void
    {
        $user = new User();
        $user->updatePreferences(PreferredSalary::fromDecimal('4500.00', 'EUR'), 45, PreferredTransportMode::PublicTransport);

        self::assertSame('4500.00', $user->getPreferredSalary()->getMinimumDecimal());
        self::assertSame(SalaryFitStatus::MeetsTarget, $user->evaluatesSalary(Money::of('4500.00', 'EUR')));
        self::assertSame(SalaryFitStatus::BelowTarget, $user->evaluatesSalary(Money::of('4499.99', 'EUR')));
    }

    public function testUnconfiguredSalaryDoesNotProduceAMatch(): void
    {
        self::assertSame(
            SalaryFitStatus::NotConfigured,
            new User()->evaluatesSalary(Money::of('4500.00', 'EUR')),
        );
    }

    public function testSalaryIsConvertedBeforeItIsCompared(): void
    {
        $user = new User();
        $user->updatePreferences(PreferredSalary::fromDecimal('5000.00', 'USD'), null, null);

        self::assertSame(
            SalaryFitStatus::MeetsTarget,
            $user->evaluatesSalary(
                Money::of('4600.00', 'EUR'),
                new CurrencyConversionRate('EUR', 'USD', '1.10'),
            ),
        );
        self::assertSame(
            SalaryFitStatus::BelowTarget,
            $user->evaluatesSalary(
                Money::of('4500.00', 'EUR'),
                new CurrencyConversionRate('EUR', 'USD', '1.10'),
            ),
        );
        self::assertSame(
            SalaryFitStatus::ConversionUnavailable,
            $user->evaluatesSalary(Money::of('4600.00', 'EUR')),
        );
    }

    public function testSalaryAndCommuteMustBePositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PreferredSalary::fromDecimal('0.00', 'EUR');
    }

    public function testCommuteMustBePositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new User()->updatePreferences(PreferredSalary::fromDecimal(null, 'EUR'), 0, null);
    }

    public function testResumeMetadataCanBeReplaced(): void
    {
        $user = new User();
        $uploadedAt = new \DateTimeImmutable('2026-09-21T12:00:00+00:00');

        $user->replaceResume('resumes/1/first.pdf', 'first.pdf', 'application/pdf', $uploadedAt);
        $user->replaceResume('resumes/1/second.docx', 'second.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', $uploadedAt);

        self::assertSame('resumes/1/second.docx', $user->getResumeStoragePath());
        self::assertSame('second.docx', $user->getResumeOriginalFilename());
        self::assertSame('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $user->getResumeMimeType());
        self::assertSame($uploadedAt, $user->getResumeUploadedAt());
    }
}
