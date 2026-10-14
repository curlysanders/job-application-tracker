<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

use Symfony\Component\Validator\Constraints as Assert;

final class CandidateFitReport
{
    /** @var list<FitEvidence> */
    #[Assert\Valid]
    public array $strengths = [];

    /** @var list<FitEvidence> */
    #[Assert\Valid]
    public array $gaps = [];
}
