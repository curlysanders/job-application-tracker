<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Authentication;

use CurlySanders\JobApplicationTracker\Domain\User\User;

interface AuthenticatedUserFactory
{
    public function create(User $user): AuthenticatedUser;
}
