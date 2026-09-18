<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\UserProfile\Command;

use CurlySanders\JobApplicationTracker\Application\Authentication\UserRepository;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandHandler;
use CurlySanders\JobApplicationTracker\Domain\User\GrossMonthlySalary;
use CurlySanders\JobApplicationTracker\Domain\User\User;

final readonly class UpdateUserPreferencesHandler implements CommandHandler
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function __invoke(UpdateUserPreferences $command): User
    {
        $user = $this->userRepository->find($command->userId);
        if (null === $user) {
            throw new \LogicException('The authenticated user no longer exists.');
        }

        $salary = null === $command->minimumPreferredSalary || '' === trim($command->minimumPreferredSalary)
            ? null
            : GrossMonthlySalary::fromDecimal($command->minimumPreferredSalary);

        $user->updatePreferences($salary, $command->maximumCommuteMinutes, $command->preferredTransportMode);
        $this->userRepository->save($user);

        return $user;
    }
}
