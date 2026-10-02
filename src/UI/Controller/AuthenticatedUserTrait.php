<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller;

use CurlySanders\JobApplicationTracker\Domain\User\User;

trait AuthenticatedUserTrait
{
    private function currentUser(): ?User
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $user : null;
    }

    private function requireAuthenticatedUser(string $message): User
    {
        return $this->currentUser() ?? throw new \LogicException($message);
    }
}
