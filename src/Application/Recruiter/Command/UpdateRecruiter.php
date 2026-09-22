<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Recruiter\Command;

use CurlySanders\JobApplicationTracker\Application\Shared\DirectContactInput;

final readonly class UpdateRecruiter
{
    /** @param list<DirectContactInput> $directContacts */
    public function __construct(
        public int $recruiterId,
        public string $agencyName,
        public ?string $website,
        public array $directContacts,
    ) {
    }
}
