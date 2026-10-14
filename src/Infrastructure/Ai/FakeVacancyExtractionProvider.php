<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Application\Ai\VacancyExtractionProvider;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionInput;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionResult;

final class FakeVacancyExtractionProvider implements VacancyExtractionProvider
{
    private int $invocations = 0;

    public function __construct(private readonly ?VacancyExtractionResult $response = null)
    {
    }

    public function extract(VacancyExtractionInput $input): VacancyExtractionResult
    {
        ++$this->invocations;

        return clone ($this->response ?? new VacancyExtractionResult());
    }

    public function invocations(): int
    {
        return $this->invocations;
    }
}
