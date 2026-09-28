<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy\Pipeline;

use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;

interface VacancyPipelineRepository
{
    public function forUser(int $userId, ?VacancyStatus $selectedStatus): VacancyPipeline;
}
