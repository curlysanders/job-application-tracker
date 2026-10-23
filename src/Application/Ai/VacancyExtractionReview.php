<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Ai;

/** @phpstan-type FieldProposals array<string, list<ReviewProposal>> */
final readonly class VacancyExtractionReview
{
    /**
     * @param FieldProposals       $fields
     * @param list<ReviewProposal> $techStacks
     * @param list<ReviewProposal> $companyEvidence
     */
    public function __construct(
        public string $vacancyText,
        public array $fields,
        public array $techStacks,
        public array $companyEvidence,
    ) {
    }
}
