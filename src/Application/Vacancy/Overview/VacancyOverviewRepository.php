<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy\Overview;

use CurlySanders\JobApplicationTracker\Domain\User\PreferredSalary;

interface VacancyOverviewRepository
{
    public function forUser(string $userId, PreferredSalary $preferredSalary, VacancyOverviewFilter $filter): VacancyOverview;
}
