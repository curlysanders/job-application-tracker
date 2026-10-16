<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Security;

use CurlySanders\JobApplicationTracker\Application\Authentication\UserRepository;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Infrastructure\Security\DoctrineSecurityUserProvider;
use CurlySanders\JobApplicationTracker\Infrastructure\Security\SecurityUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class DoctrineSecurityUserProviderTest extends TestCase
{
    public function testLoadsAndRefreshesSecurityUsersFromTheDomainRepository(): void
    {
        $user = new User()->setEmail('sander@example.test');
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::exactly(2))->method('findByEmail')->with('sander@example.test')->willReturn($user);
        $provider = new DoctrineSecurityUserProvider($users);

        $loaded = $provider->loadUserByIdentifier('sander@example.test');

        self::assertSame('sander@example.test', $loaded->getUserIdentifier());
        self::assertSame('sander@example.test', $provider->refreshUser($loaded)->getUserIdentifier());
        self::assertTrue($provider->supportsClass(SecurityUser::class));
        self::assertFalse($provider->supportsClass(User::class));
    }

    public function testRejectsUnknownAndUnsupportedUsers(): void
    {
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('findByEmail')->with('missing@example.test')->willReturn(null);
        $provider = new DoctrineSecurityUserProvider($users);

        try {
            $provider->loadUserByIdentifier('missing@example.test');
            self::fail('Expected an unknown user to be rejected.');
        } catch (UserNotFoundException $exception) {
            self::assertSame('missing@example.test', $exception->getUserIdentifier());
        }

        $this->expectException(UnsupportedUserException::class);
        $provider->refreshUser(self::createStub(UserInterface::class));
    }

    public function testUpgradesTheDomainUsersPassword(): void
    {
        $user = new User()->setEmail('sander@example.test')->setPassword('old-hash');
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('save')->with($user);
        $provider = new DoctrineSecurityUserProvider($users);

        $provider->upgradePassword(new SecurityUser($user), 'new-hash');

        self::assertSame('new-hash', $user->getPassword());
    }

    public function testRejectsPasswordUpgradesForUnsupportedUsers(): void
    {
        $provider = new DoctrineSecurityUserProvider(self::createStub(UserRepository::class));

        $this->expectException(UnsupportedUserException::class);
        $provider->upgradePassword(self::createStub(PasswordAuthenticatedUserInterface::class), 'new-hash');
    }
}
