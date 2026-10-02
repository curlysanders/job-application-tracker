<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy\Command;

final readonly class SetVacancyArchived
{
    public function __construct(public string $userId, public string $vacancyId, public bool $archived)
    {
    }
}
