<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

use Symfony\Component\Validator\Constraints as Assert;

final class TechStackSuggestion
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 120)]
        public string $name = '',
        #[Assert\NotBlank]
        #[Assert\Length(max: 1_000)]
        public string $excerpt = '',
        #[Assert\Range(min: 0, max: 1)]
        public float $confidence = 0.0,
    ) {
    }
}
