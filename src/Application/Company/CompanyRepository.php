<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Company;

use CurlySanders\JobApplicationTracker\Domain\Company\Company;

interface CompanyRepository
{
    public function find(int $id): ?Company;

    /** @return list<Company> */
    public function search(string $query): array;

    public function save(Company $company): void;
}
