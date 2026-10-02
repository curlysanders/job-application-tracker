<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Recruiter;

use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;

interface RecruiterRepository
{
    public function findOwnedBy(string $recruiterId, string $userId): ?Recruiter;

    /** @return list<Recruiter> */
    public function searchOwnedBy(string $userId, string $query): array;

    public function save(Recruiter $recruiter): void;
}
