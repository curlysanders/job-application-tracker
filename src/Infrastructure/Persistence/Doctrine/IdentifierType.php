<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Doctrine;

use CurlySanders\JobApplicationTracker\Domain\Shared\Identifier;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;

final class IdentifierType extends Type
{
    public const string NAME = 'identifier';

    public function getSQLDeclaration(
        array $column,
        AbstractPlatform $platform,
    ): string {
        return $platform->getBinaryTypeDeclarationSQL(['length' => 16, 'fixed' => true]);
    }

    public function convertToPHPValue(
        mixed $value,
        AbstractPlatform $platform,
    ): ?Identifier {
        if (null === $value || $value instanceof Identifier) {
            return $value;
        }

        if (!is_string($value)) {
            throw ValueNotConvertible::new($value, self::NAME, 'An identifier must be stored as a 16-byte binary value.');
        }

        try {
            return Identifier::fromBinary($value);
        } catch (\InvalidArgumentException $exception) {
            throw ValueNotConvertible::new($value, self::NAME, $exception->getMessage(), $exception);
        }
    }

    public function convertToDatabaseValue(
        mixed $value,
        AbstractPlatform $platform,
    ): ?string {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof Identifier) {
            throw ValueNotConvertible::new($value, self::NAME, 'Only domain identifiers can be persisted.');
        }

        return $value->toBinary();
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
