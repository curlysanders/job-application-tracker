<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy\Overview;

use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\WorkMode;

final readonly class VacancyOverviewVacancy
{
    /** @param list<string> $techStackNames */
    public function __construct(
        public string $id,
        public string $title,
        public ?string $companyName,
        public ?string $recruiterName,
        public ?WorkMode $workMode,
        public array $techStackNames,
        public ?int $excitement,
        public ?string $salary,
        public ?string $nextActionTitle,
        public ?\DateTimeImmutable $nextActionAt,
        public VacancyStatus $status,
        public SalaryFit $salaryFit,
        public bool $archived,
        /** @var list<string> */
        public array $enabledTransitions,
    ) {
    }
}
