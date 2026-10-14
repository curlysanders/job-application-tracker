<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Application\Ai\VacancyExtractionProvider;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionInput;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionResult;

final readonly class LocalOllamaVacancyExtractionProvider implements VacancyExtractionProvider
{
    public function __construct(private LocalOllamaInference $inference)
    {
    }

    public function extract(VacancyExtractionInput $input): VacancyExtractionResult
    {
        /** @var VacancyExtractionResult $result */
        $result = $this->inference->invoke(
            'Extract only evidence stated in the vacancy. Return field proposals with exact source excerpts. Omit uncertain fields and never invent values.',
            $input->vacancyText,
            VacancyExtractionResult::class,
        );

        return $result;
    }
}
