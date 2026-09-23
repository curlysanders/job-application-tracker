<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\TechStack;

/**
 * @template T
 */
final readonly class PaginatedResults
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public array $items,
        public bool $hasNextPage,
    ) {
    }
}
