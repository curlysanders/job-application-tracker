<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy\Reminder;

final readonly class DueVacancyReminder
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $nextActionTitle,
        public \DateTimeImmutable $nextActionAt,
    ) {
    }
}
