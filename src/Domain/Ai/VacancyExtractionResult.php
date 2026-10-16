<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

final class VacancyExtractionResult
{
    /** @var list<VacancyFieldProposal> */
    public array $proposals = [];

    /** @var list<TechStackSuggestion> */
    public array $techStacks = [];

    public function assertValid(): void
    {
        AiValidation::listOf($this->proposals, VacancyFieldProposal::class, 'Proposals');
        AiValidation::listOf($this->techStacks, TechStackSuggestion::class, 'Technology suggestions');
        foreach ($this->proposals as $proposal) {
            $proposal->assertValid();
        }
        foreach ($this->techStacks as $suggestion) {
            $suggestion->assertValid();
        }
    }
}
