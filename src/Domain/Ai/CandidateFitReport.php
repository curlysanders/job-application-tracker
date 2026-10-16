<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

final class CandidateFitReport
{
    /** @var list<FitEvidence> */
    public array $strengths = [];

    /** @var list<FitEvidence> */
    public array $gaps = [];

    public function assertValid(): void
    {
        AiValidation::listOf($this->strengths, FitEvidence::class, 'Strengths');
        AiValidation::listOf($this->gaps, FitEvidence::class, 'Gaps');
        foreach ([...$this->strengths, ...$this->gaps] as $evidence) {
            $evidence->assertValid();
        }
    }
}
