<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

use CurlySanders\JobApplicationTracker\Domain\Vacancy\ContractType;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\CurrentIsoCurrencies;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\WorkMode;

final class VacancyFieldValue
{
    public const string POSITIVE_DECIMAL_PATTERN = '/^(?!0+(?:\.0+)?$)\d+(?:\.\d{1,2})?$/';

    public static function assertValid(
        string $field,
        mixed $value,
    ): void {
        if (!in_array($field, VacancyFieldProposal::ALLOWED_FIELDS, true)) {
            throw new \InvalidArgumentException('The proposed vacancy field is not supported.');
        }
        if (!is_string($value) || '' === trim($value)) {
            throw new \InvalidArgumentException('The proposed value must not be blank.');
        }
        if ('contractType' === $field && null === ContractType::tryFrom($value)) {
            throw new \InvalidArgumentException('The proposed contract type must use a supported canonical value.');
        }
        if ('workMode' === $field && null === WorkMode::tryFrom($value)) {
            throw new \InvalidArgumentException('The proposed work mode must use a supported canonical value.');
        }
        if ('currencyCode' === $field && !in_array($value, array_map(static fn ($currency): string => $currency->code, CurrentIsoCurrencies::all()), true)) {
            throw new \InvalidArgumentException('The proposed currency must use an ISO 4217 code.');
        }
        if (in_array($field, ['datePosted', 'deadline'], true) && !self::isIsoDate($value)) {
            throw new \InvalidArgumentException('The proposed date must use a valid ISO 8601 calendar date.');
        }
        if (in_array($field, ['minimumSalary', 'maximumSalary'], true) && 1 !== preg_match(self::POSITIVE_DECIMAL_PATTERN, $value)) {
            throw new \InvalidArgumentException('The proposed salary must be a positive decimal value with at most two decimal places.');
        }
    }

    private static function isIsoDate(string $value): bool
    {
        if (false === \DateTimeImmutable::createFromFormat('!Y-m-d', $value)) {
            return false;
        }
        $errors = \DateTimeImmutable::getLastErrors();

        return false === $errors || (0 === $errors['warning_count'] && 0 === $errors['error_count']);
    }
}
