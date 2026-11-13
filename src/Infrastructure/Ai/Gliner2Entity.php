<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Ai;

final readonly class Gliner2Entity
{
    public function __construct(
        public string $kind,
        public string $text,
        public int $start,
        public int $end,
        public float $confidence,
    ) {
    }
}
