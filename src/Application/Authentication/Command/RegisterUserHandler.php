<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Authentication\Command;

use CurlySanders\JobApplicationTracker\Application\Authentication\Event\UserRegistered;
use CurlySanders\JobApplicationTracker\Application\Authentication\Exception\UserAlreadyExists;
use CurlySanders\JobApplicationTracker\Application\Authentication\PasswordHasher;
use CurlySanders\JobApplicationTracker\Application\Authentication\UserRepository;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandHandler;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\EventBus;
use CurlySanders\JobApplicationTracker\Domain\User\User;

final readonly class RegisterUserHandler implements CommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private PasswordHasher $passwordHasher,
        private EventBus $eventBus,
    ) {
    }

    public function __invoke(RegisterUser $command): User
    {
        $email = User::normalizeEmail($command->email);

        if (null !== $this->userRepository->findByEmail($email)) {
            throw new UserAlreadyExists();
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hash($user, $command->plainPassword));
        $this->userRepository->save($user);

        $userId = $user->getId();
        if (null === $userId) {
            throw new \LogicException('A persisted user must have an identifier.');
        }

        $this->eventBus->dispatch(new UserRegistered($userId, $user->getEmail(), $user->getCreatedAt()));

        return $user;
    }
}
