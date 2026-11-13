<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

final class TechStackSuggestion
{
    public function __construct(
        public string $name = '',
        public string $excerpt = '',
        public float $confidence = 0.0,
        public ?int $sourceStart = null,
        public ?int $sourceEnd = null,
    ) {
    }

    public function assertValid(): void
    {
        AiValidation::requiredText($this->name, 'Technology name', 120);
        AiValidation::requiredText($this->excerpt, 'Technology excerpt', 1_000);
        AiValidation::confidence($this->confidence);
        if ((null !== $this->sourceStart || null !== $this->sourceEnd) && (null === $this->sourceStart || null === $this->sourceEnd || $this->sourceStart < 0 || $this->sourceEnd <= $this->sourceStart)) {
            throw new \InvalidArgumentException('The technology source span must use a non-negative half-open range.');
        }
    }
}
