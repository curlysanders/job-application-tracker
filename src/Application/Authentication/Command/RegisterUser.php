<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Authentication\Command;

final readonly class RegisterUser
{
    public function __construct(
        #[\SensitiveParameter]
        public string $email,
        #[\SensitiveParameter]
        public string $plainPassword,
    ) {
    }
}
