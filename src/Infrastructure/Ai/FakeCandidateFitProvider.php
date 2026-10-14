<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Application\Ai\CandidateFitProvider;
use CurlySanders\JobApplicationTracker\Domain\Ai\CandidateFitInput;
use CurlySanders\JobApplicationTracker\Domain\Ai\CandidateFitReport;

final class FakeCandidateFitProvider implements CandidateFitProvider
{
    private int $invocations = 0;

    public function __construct(private readonly ?CandidateFitReport $response = null)
    {
    }

    public function assess(CandidateFitInput $input): CandidateFitReport
    {
        ++$this->invocations;

        return clone ($this->response ?? new CandidateFitReport());
    }

    public function invocations(): int
    {
        return $this->invocations;
    }
}
