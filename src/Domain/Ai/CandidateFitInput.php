<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

final readonly class CandidateFitInput
{
    public function __construct(
        public string $vacancyText,
        public string $resumeText,
    ) {
        AiValidation::requiredText($vacancyText, 'Vacancy text', 100_000);
        AiValidation::requiredText($resumeText, 'Resume text', 100_000);
    }
}
