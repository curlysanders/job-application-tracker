<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Domain;

use CurlySanders\JobApplicationTracker\Domain\Shared\Identifier;
use CurlySanders\JobApplicationTracker\Domain\Shared\Money;
use CurlySanders\JobApplicationTracker\Domain\User\CurrencyConversionRate;
use CurlySanders\JobApplicationTracker\Domain\User\ResumeMetadata;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\CurrencyOption;
use PHPUnit\Framework\TestCase;

final class SharedValueObjectTest extends TestCase
{
    public function testIdentifierRoundTripsAcrossItsStringAndBinaryRepresentations(): void
    {
        $identifier = Identifier::fromString('018f0957-8a79-7fcb-bab5-2ccfbe98de81');

        self::assertSame($identifier->toRfc4122(), Identifier::fromBinary($identifier->toBinary())->toRfc4122());
        self::assertTrue($identifier->equals(Identifier::fromString('018F0957-8A79-7FCB-BAB5-2CCFBE98DE81')));
        self::assertFalse($identifier->equals(Identifier::v7()));
        self::assertFalse(Identifier::isValid('not-a-uuid'));
    }

    public function testIdentifierRejectsInvalidRepresentations(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Identifier::fromBinary('too-short');
    }

    public function testMoneyNormalizesAmountsAndRejectsIncompatibleCurrencies(): void
    {
        $amount = Money::of('00012.5', 'EUR');

        self::assertSame('12.50', $amount->amount());
        self::assertTrue($amount->isPositive());
        self::assertFalse(Money::of('-0.01', 'EUR')->isPositive());
        self::assertSame(0, $amount->compare(Money::of('12.50', 'EUR')));

        $this->expectException(\InvalidArgumentException::class);
        $amount->compare(Money::of('12.50', 'USD'));
    }

    public function testCurrencyOptionRejectsAnInvalidCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CurrencyOption('EURO', 'Euro');
    }

    public function testCurrencyConversionRateRejectsZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CurrencyConversionRate('EUR', 'USD', '0');
    }

    public function testResumeMetadataRejectsAFilenameWithDirectoryComponents(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ResumeMetadata::assertValid('resumes/1/resume.pdf', '../resume.pdf', 'application/pdf');
    }
}
