<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy\Command;

final readonly class TransitionVacancyStatus
{
    public function __construct(
        public string $userId,
        public string $vacancyId,
        public string $transition,
        public ?string $note = null,
    ) {
    }
}
