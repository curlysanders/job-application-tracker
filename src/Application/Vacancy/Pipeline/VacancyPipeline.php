<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy\Pipeline;

final readonly class VacancyPipeline
{
    /**
     * @param array<string, int>           $counts
     * @param list<VacancyPipelineVacancy> $vacancies
     */
    public function __construct(public array $counts, public array $vacancies)
    {
    }
}
