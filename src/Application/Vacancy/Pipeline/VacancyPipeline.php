<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy\Pipeline;

final readonly class VacancyPipeline
{
    /**
     * @param array<string, int>           $counts
     * @param list<VacancyPipelineVacancy> $vacancies
     */
    public function __construct(
        public array $counts,
        public array $vacancies,
    ) {
        foreach ($counts as $count) {
            if ($count < 0) {
                throw new \InvalidArgumentException('Pipeline counts must map statuses to non-negative integers.');
            }
        }
    }
}
