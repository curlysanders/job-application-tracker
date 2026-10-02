<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Company;

use CurlySanders\JobApplicationTracker\Domain\Company\Company;

interface CompanyRepository
{
    public function findOwnedBy(string $companyId, string $userId): ?Company;

    /** @return list<Company> */
    public function searchOwnedBy(string $userId, string $query): array;

    public function save(Company $company): void;
}
