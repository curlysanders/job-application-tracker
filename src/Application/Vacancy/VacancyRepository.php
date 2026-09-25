<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy;

use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;

interface VacancyRepository
{
    public function findOwnedBy(int $vacancyId, int $userId): ?Vacancy;

    public function save(Vacancy $vacancy): void;
}
