<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Authentication;

use CurlySanders\JobApplicationTracker\Domain\User\User;

interface UserRepository
{
    public function find(int $id): ?User;

    public function findByEmail(#[\SensitiveParameter] string $email): ?User;

    public function save(User $user): void;
}
