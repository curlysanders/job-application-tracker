<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CandidateFitInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 100_000)]
        public string $vacancyText,
        #[Assert\NotBlank]
        #[Assert\Length(max: 100_000)]
        public string $resumeText,
    ) {
    }
}
