<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Recruiter;

use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;

interface RecruiterRepository
{
    public function find(int $id): ?Recruiter;

    /** @return list<Recruiter> */
    public function search(string $query): array;

    public function save(Recruiter $recruiter): void;
}
