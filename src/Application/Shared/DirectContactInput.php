<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Shared;

final readonly class DirectContactInput
{
    public function __construct(
        public string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $linkedinUrl,
    ) {
    }
}
