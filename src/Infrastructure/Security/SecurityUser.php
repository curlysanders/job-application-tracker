<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Security;

use CurlySanders\JobApplicationTracker\Application\Authentication\AuthenticatedUser;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface, AuthenticatedUser
{
    public function __construct(private User $user)
    {
    }

    public function domainUser(): User
    {
        return $this->user;
    }

    public function getUserIdentifier(): string
    {
        $email = $this->user->email;
        if ('' === $email) {
            throw new \LogicException('Authenticated users must have an email address.');
        }

        return $email;
    }

    public function getRoles(): array
    {
        return $this->user->getRoles();
    }

    public function getPassword(): string
    {
        return $this->user->getPassword();
    }
}
