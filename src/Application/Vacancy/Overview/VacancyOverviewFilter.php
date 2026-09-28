<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy\Overview;

use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\WorkMode;

final readonly class VacancyOverviewFilter
{
    public const int PAGE_SIZE = 20;

    public function __construct(
        public ?string $query,
        public ?VacancyStatus $status,
        public ?int $excitement,
        public ?WorkMode $workMode,
        public ?SalaryFit $salaryFit,
        public bool $archived,
        public int $page,
    ) {
    }
}
