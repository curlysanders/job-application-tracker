<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Authentication\Event;

final readonly class UserRegistered
{
    public function __construct(
        public int $userId,
        #[\SensitiveParameter]
        public string $email,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}
