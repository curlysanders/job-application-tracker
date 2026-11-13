<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

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
        VacancyFieldValue::assertValid($this->field, $this->value);
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
}
