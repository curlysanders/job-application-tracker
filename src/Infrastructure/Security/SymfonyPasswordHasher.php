<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Security;

use CurlySanders\JobApplicationTracker\Application\Authentication\PasswordHasher;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsAlias(PasswordHasher::class)]
final readonly class SymfonyPasswordHasher implements PasswordHasher
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function hash(User $user, #[\SensitiveParameter] string $plainPassword): string
    {
        return $this->passwordHasher->hashPassword($user, $plainPassword);
    }
}
