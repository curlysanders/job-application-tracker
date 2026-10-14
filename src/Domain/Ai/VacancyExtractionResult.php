<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

use Symfony\Component\Validator\Constraints as Assert;

final class VacancyExtractionResult
{
    /** @var list<VacancyFieldProposal> */
    #[Assert\Valid]
    public array $proposals = [];

    /** @var list<TechStackSuggestion> */
    #[Assert\Valid]
    public array $techStacks = [];
}
