<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\UserProfile\Command;

use CurlySanders\JobApplicationTracker\Application\Authentication\UserRepository;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandHandler;
use CurlySanders\JobApplicationTracker\Domain\User\PreferredSalary;
use CurlySanders\JobApplicationTracker\Domain\User\PreferredTransportMode;
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

        $preferredTransportMode = null === $command->preferredTransportMode
            ? null
            : PreferredTransportMode::tryFrom($command->preferredTransportMode)
                ?? throw new \LogicException('The preferred transport mode is invalid.');

        $user->updatePreferences(
            PreferredSalary::fromDecimal(
                null === $command->minimumPreferredSalary || '' === trim($command->minimumPreferredSalary)
                    ? null
                    : $command->minimumPreferredSalary,
                $command->minimumPreferredSalaryCurrency,
            ),
            $command->maximumCommuteMinutes,
            $preferredTransportMode,
        );
        $this->userRepository->save($user);

        return $user;
    }
}
