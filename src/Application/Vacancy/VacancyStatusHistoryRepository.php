<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy;

use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatusHistory;

interface VacancyStatusHistoryRepository
{
    public function add(VacancyStatusHistory $history): void;

    /** @return list<VacancyStatusHistory> */
    public function findForVacancy(Vacancy $vacancy): array;
}
