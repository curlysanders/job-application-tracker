<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy\Pipeline;

use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;

final readonly class VacancyPipelineVacancy
{
    public function __construct(
        public string $title,
        public ?string $companyName,
        public ?string $recruiterName,
        public VacancyStatus $status,
    ) {
    }
}
