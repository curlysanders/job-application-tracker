<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Doctrine;

use CurlySanders\JobApplicationTracker\Domain\User\GrossMonthlySalary;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class GrossMonthlySalaryType extends Type
{
    public const string NAME = 'gross_monthly_salary';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getDecimalTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof GrossMonthlySalary) {
            throw new \InvalidArgumentException('Expected a GrossMonthlySalary value.');
        }

        return $value->toDecimal();
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?GrossMonthlySalary
    {
        if (null === $value || $value instanceof GrossMonthlySalary) {
            return $value;
        }

        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw new \InvalidArgumentException('Expected a decimal database value.');
        }

        return GrossMonthlySalary::fromDecimal((string) $value);
    }
}
