<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Company\Command;

use CurlySanders\JobApplicationTracker\Application\Shared\DirectContactInput;

final readonly class UpdateCompany
{
    /** @param list<DirectContactInput> $directContacts */
    public function __construct(
        public int $companyId,
        public string $name,
        public ?string $website,
        public ?string $industry,
        public array $directContacts,
    ) {
    }
}
