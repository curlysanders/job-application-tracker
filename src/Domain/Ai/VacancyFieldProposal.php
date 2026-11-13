<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

use CurlySanders\JobApplicationTracker\Domain\Vacancy\ContractType;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\CurrentIsoCurrencies;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\WorkMode;

final class VacancyFieldProposal
{
    /** @var list<string> */
    public const array ALLOWED_FIELDS = ['title', 'companyName', 'recruiterAgencyName', 'location', 'contractType', 'datePosted', 'deadline', 'minimumSalary', 'maximumSalary', 'currencyCode', 'workMode', 'hybridDetails', 'requirements', 'responsibilities', 'preferredQualifications', 'aboutJob', 'aboutCompany', 'compensationBenefits'];

    /** @param scalar|list<scalar> $value */
    public function __construct(
        public string $field = '',
        public string|int|float|bool|array $value = '',
        public string $excerpt = '',
        public float $confidence = 0.0,
        public ?int $sourceStart = null,
        public ?int $sourceEnd = null,
    ) {
    }

    public function assertValid(): void
    {
        if (!in_array($this->field, self::ALLOWED_FIELDS, true)) {
            throw new \InvalidArgumentException('The proposed vacancy field is not supported.');
        }
        if (!is_string($this->value) || '' === trim($this->value)) {
            throw new \InvalidArgumentException('The proposed value must not be blank.');
        }
        if ('contractType' === $this->field && null === ContractType::tryFrom($this->value)) {
            throw new \InvalidArgumentException('The proposed contract type must use a supported canonical value.');
        }
        if ('workMode' === $this->field && null === WorkMode::tryFrom($this->value)) {
            throw new \InvalidArgumentException('The proposed work mode must use a supported canonical value.');
        }
        if ('currencyCode' === $this->field && !in_array($this->value, array_map(static fn ($currency): string => $currency->code, CurrentIsoCurrencies::all()), true)) {
            throw new \InvalidArgumentException('The proposed currency must use an ISO 4217 code.');
        }
        if (in_array($this->field, ['datePosted', 'deadline'], true) && !$this->isIsoDate($this->value)) {
            throw new \InvalidArgumentException('The proposed date must use a valid ISO 8601 calendar date.');
        }
        if (in_array($this->field, ['minimumSalary', 'maximumSalary'], true) && 1 !== preg_match('/^(?!0+(?:\.0+)?$)\d+(?:\.\d{1,2})?$/', $this->value)) {
            throw new \InvalidArgumentException('The proposed salary must be a positive decimal value with at most two decimal places.');
        }
        AiValidation::requiredText($this->excerpt, 'Proposal excerpt', 1_000);
        AiValidation::confidence($this->confidence);
        $this->assertSourceSpan();
    }

    private function assertSourceSpan(): void
    {
        if (null === $this->sourceStart && null === $this->sourceEnd) {
            return;
        }
        if (null === $this->sourceStart || null === $this->sourceEnd || $this->sourceStart < 0 || $this->sourceEnd <= $this->sourceStart) {
            throw new \InvalidArgumentException('The proposal source span must use a non-negative half-open range.');
        }
    }

    private function isIsoDate(string $value): bool
    {
        if (false === \DateTimeImmutable::createFromFormat('!Y-m-d', $value)) {
            return false;
        }
        $errors = \DateTimeImmutable::getLastErrors();

        return false === $errors || (0 === $errors['warning_count'] && 0 === $errors['error_count']);
    }
}
