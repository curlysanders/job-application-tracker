<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Domain\Vacancy;

use CurlySanders\JobApplicationTracker\Domain\Vacancy\SalaryRange;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SalaryRangeTest extends TestCase
{
    public function testKeepsPreciseMultiCurrencyAmounts(): void
    {
        $salaryRange = SalaryRange::fromDecimals('4500.10', '6000.99', 'USD');
        self::assertNotNull($salaryRange);

        self::assertSame('USD', $salaryRange->getCurrencyCode());
        self::assertSame('4500.10', (string) $salaryRange->getMinimum()?->getAmount());
        self::assertSame('6000.99', (string) $salaryRange->getMaximum()?->getAmount());
    }

    public function testAllowsAnOpenEndedSalaryRange(): void
    {
        $salaryRange = SalaryRange::fromDecimals(null, '5000.00');

        self::assertNull($salaryRange?->getMinimum());
        self::assertSame('5000.00', (string) $salaryRange?->getMaximum()?->getAmount());
    }

    public function testReturnsNullWhenNoSalaryWasProvided(): void
    {
        self::assertNull(SalaryRange::fromDecimals(null, null));
    }

    #[DataProvider('invalidRanges')]
    public function testRejectsInvalidRanges(?string $minimum, ?string $maximum): void
    {
        $this->expectException(\InvalidArgumentException::class);

        SalaryRange::fromDecimals($minimum, $maximum);
    }

    /** @return iterable<string, array{?string, ?string}> */
    public static function invalidRanges(): iterable
    {
        yield 'zero minimum' => ['0.00', null];
        yield 'negative maximum' => [null, '-1.00'];
        yield 'minimum exceeds maximum' => ['5000.00', '4500.00'];
    }
}
