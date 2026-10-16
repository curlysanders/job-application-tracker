<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

final readonly class VacancyExtractionInput
{
    public function __construct(
        public string $vacancyText,
        public string $language = 'auto',
    ) {
        AiValidation::requiredText($vacancyText, 'Vacancy text', 100_000);
    }
}
