<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\Authentication;

use CurlySanders\JobApplicationTracker\Application\Authentication\Command\RegisterUser;
use CurlySanders\JobApplicationTracker\Application\Authentication\Command\RegisterUserHandler;
use CurlySanders\JobApplicationTracker\Application\Authentication\Exception\DuplicateUserEmail;
use CurlySanders\JobApplicationTracker\Application\Authentication\Exception\UserAlreadyExists;
use CurlySanders\JobApplicationTracker\Application\Authentication\PasswordHasher;
use CurlySanders\JobApplicationTracker\Application\Authentication\UserRepository;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\EventBus;
use PHPUnit\Framework\TestCase;

final class RegisterUserHandlerTest extends TestCase
{
    public function testTranslatesADuplicateSaveRaceToThePublicException(): void
    {
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('findByEmail')->with('sander@example.com')->willReturn(null);
        $users->expects(self::once())->method('save')->willThrowException(new DuplicateUserEmail());
        $passwordHasher = $this->createMock(PasswordHasher::class);
        $passwordHasher->expects(self::once())->method('hash')->willReturn('hashed-password');
        $eventBus = $this->createMock(EventBus::class);
        $eventBus->expects(self::never())->method('dispatch');

        $this->expectException(UserAlreadyExists::class);
        new RegisterUserHandler($users, $passwordHasher, $eventBus)(new RegisterUser(' Sander@example.com ', 'SecurePassword1!'));
    }
}
