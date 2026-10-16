<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

final class FitEvidence
{
    public function __construct(
        public string $statement = '',
        public string $vacancyExcerpt = '',
        public string $resumeExcerpt = '',
    ) {
    }

    public function assertValid(): void
    {
        AiValidation::requiredText($this->statement, 'Evidence statement', 255);
        AiValidation::requiredText($this->vacancyExcerpt, 'Vacancy excerpt', 1_000);
        AiValidation::requiredText($this->resumeExcerpt, 'Resume excerpt', 1_000);
    }
}
