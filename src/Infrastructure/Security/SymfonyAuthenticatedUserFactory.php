<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Security;

use CurlySanders\JobApplicationTracker\Application\Authentication\AuthenticatedUser;
use CurlySanders\JobApplicationTracker\Application\Authentication\AuthenticatedUserFactory;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(AuthenticatedUserFactory::class)]
final readonly class SymfonyAuthenticatedUserFactory implements AuthenticatedUserFactory
{
    public function create(User $user): AuthenticatedUser
    {
        return new SecurityUser($user);
    }
}
