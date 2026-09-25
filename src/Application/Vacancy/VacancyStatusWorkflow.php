<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy;

use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;

interface VacancyStatusWorkflow
{
    /** @throws Exception\VacancyStatusTransitionNotAllowed */
    public function apply(Vacancy $vacancy, string $transition): void;
}
