<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

final class VacancyFieldProposal
{
    /** @param scalar|list<scalar> $value */
    public function __construct(
        public string $field = '',
        public string|int|float|bool|array $value = '',
        public string $excerpt = '',
        public float $confidence = 0.0,
    ) {
    }

    public function assertValid(): void
    {
        $allowedFields = ['title', 'companyName', 'location', 'contractType', 'datePosted', 'deadline', 'minimumSalary', 'maximumSalary', 'currencyCode', 'workMode', 'hybridDetails', 'requirements', 'responsibilities', 'preferredQualifications', 'aboutJob', 'aboutCompany', 'compensationBenefits'];
        if (!in_array($this->field, $allowedFields, true)) {
            throw new \InvalidArgumentException('The proposed vacancy field is not supported.');
        }
        if ([] === $this->value || '' === $this->value) {
            throw new \InvalidArgumentException('The proposed value must not be blank.');
        }
        AiValidation::requiredText($this->excerpt, 'Proposal excerpt', 1_000);
        AiValidation::confidence($this->confidence);
    }
}
