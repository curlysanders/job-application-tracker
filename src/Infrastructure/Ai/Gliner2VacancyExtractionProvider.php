<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Application\Ai\VacancyExtractionProvider;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionInput;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionResult;

final readonly class Gliner2VacancyExtractionProvider implements VacancyExtractionProvider
{
    public function __construct(
        private Gliner2Inference $inference,
        private Gliner2VacancyResponseMapper $mapper,
    ) {
    }

    public function extract(VacancyExtractionInput $input): VacancyExtractionResult
    {
        return $this->mapper->map($this->inference->extract($input->vacancyText), $input->vacancyText);
    }
}
