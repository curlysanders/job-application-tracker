<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Persistence\Doctrine;

use CurlySanders\JobApplicationTracker\Domain\User\GrossMonthlySalary;
use CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Doctrine\GrossMonthlySalaryType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\TestCase;

final class GrossMonthlySalaryTypeTest extends TestCase
{
    public function testConvertsSalaryToAndFromDatabaseValues(): void
    {
        $type = new GrossMonthlySalaryType();
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->expects(self::never())->method('getDecimalTypeDeclarationSQL');
        $salary = GrossMonthlySalary::fromDecimal('4500.00');

        self::assertSame('4500.00', $type->convertToDatabaseValue($salary, $platform));
        self::assertSame('4500.00', $type->convertToPHPValue('4500.00', $platform)?->toDecimal());
        self::assertSame('4500.00', $type->convertToPHPValue(4500, $platform)?->toDecimal());
        self::assertNull($type->convertToDatabaseValue(null, $platform));
        self::assertNull($type->convertToPHPValue(null, $platform));
        self::assertSame($salary, $type->convertToPHPValue($salary, $platform));
    }

    public function testRejectsUnexpectedValues(): void
    {
        $type = new GrossMonthlySalaryType();
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->expects(self::never())->method('getDecimalTypeDeclarationSQL');

        $this->expectException(\InvalidArgumentException::class);
        $type->convertToDatabaseValue('4500.00', $platform);
    }

    public function testRejectsUnexpectedDatabaseValues(): void
    {
        $type = new GrossMonthlySalaryType();
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->expects(self::never())->method('getDecimalTypeDeclarationSQL');

        $this->expectException(\InvalidArgumentException::class);
        $type->convertToPHPValue([], $platform);
    }
}
