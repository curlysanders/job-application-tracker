<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

use Symfony\Component\Validator\Constraints as Assert;

final class FitEvidence
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $statement = '',
        #[Assert\NotBlank]
        #[Assert\Length(max: 1_000)]
        public string $vacancyExcerpt = '',
        #[Assert\NotBlank]
        #[Assert\Length(max: 1_000)]
        public string $resumeExcerpt = '',
    ) {
    }
}
