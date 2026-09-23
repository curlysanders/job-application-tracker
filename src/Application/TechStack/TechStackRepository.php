<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\TechStack;

use CurlySanders\JobApplicationTracker\Domain\TechStack\TechStack;

interface TechStackRepository
{
    public const int AUTOCOMPLETE_PAGE_SIZE = 20;

    public function findBySlug(string $slug): ?TechStack;

    /** @return PaginatedResults<TechStack> */
    public function search(string $query, int $page = 1): PaginatedResults;

    /** @return PaginatedResults<string> */
    public function categories(string $query, int $page = 1): PaginatedResults;

    public function save(TechStack $techStack): void;
}
