<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy\Overview;

final readonly class VacancyOverview
{
    /** @param list<VacancyOverviewVacancy> $vacancies */
    public function __construct(
        public array $vacancies,
        public int $total,
        public int $page,
        public int $pageSize,
    ) {
        if ($total < 0 || $page < 1 || $pageSize < 1) {
            throw new \InvalidArgumentException('Overview pagination values are invalid.');
        }
    }

    public function pages(): int
    {
        return max(1, (int) ceil($this->total / $this->pageSize));
    }
}
