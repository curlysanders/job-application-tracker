<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Ai;

use CurlySanders\JobApplicationTracker\Domain\Ai\CandidateFitInput;
use CurlySanders\JobApplicationTracker\Domain\Ai\CandidateFitReport;

interface CandidateFitProvider
{
    public function assess(CandidateFitInput $input): CandidateFitReport;
}
