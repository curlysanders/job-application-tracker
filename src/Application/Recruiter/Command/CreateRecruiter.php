<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Recruiter\Command;

use CurlySanders\JobApplicationTracker\Application\Shared\DirectContactInput;

final readonly class CreateRecruiter
{
    /** @param list<DirectContactInput> $directContacts */
    public function __construct(
        public string $agencyName,
        public ?string $website,
        public array $directContacts,
    ) {
    }
}
