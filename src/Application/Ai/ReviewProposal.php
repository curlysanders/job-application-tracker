<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Ai;

final readonly class ReviewProposal
{
    public function __construct(
        public string $key,
        public string $field,
        public string $value,
        public string $excerpt,
        public float $confidence,
        public ?int $sourceStart = null,
        public ?int $sourceEnd = null,
        public string $evidenceBefore = '',
        public string $evidenceText = '',
        public string $evidenceAfter = '',
    ) {
    }
}
