<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Persistence\Doctrine;

use CurlySanders\JobApplicationTracker\Domain\Shared\Identifier;
use CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Doctrine\IdentifierType;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use PHPUnit\Framework\TestCase;

final class IdentifierTypeTest extends TestCase
{
    public function testConvertsDomainIdentifiersBetweenDatabaseAndPhpValues(): void
    {
        $type = new IdentifierType();
        $platform = new MariaDBPlatform();
        $identifier = Identifier::v7();

        self::assertSame(IdentifierType::NAME, $type->getName());
        self::assertSame($identifier->toBinary(), $type->convertToDatabaseValue($identifier, $platform));
        self::assertSame($identifier->toRfc4122(), $type->convertToPHPValue($identifier->toBinary(), $platform)?->toRfc4122());
        self::assertSame($identifier, $type->convertToPHPValue($identifier, $platform));
    }

    public function testRejectsNonBinaryDatabaseValuesAndNonDomainIdentifiers(): void
    {
        $type = new IdentifierType();
        $platform = new MariaDBPlatform();

        foreach ([static fn (): ?Identifier => $type->convertToPHPValue(123, $platform), static fn (): ?Identifier => $type->convertToPHPValue('short', $platform), static fn (): ?string => $type->convertToDatabaseValue('uuid', $platform)] as $conversion) {
            try {
                $conversion();
                self::fail('Expected an invalid identifier conversion to be rejected.');
            } catch (ValueNotConvertible) {
            }
        }
        self::addToAssertionCount(3);
    }
}
