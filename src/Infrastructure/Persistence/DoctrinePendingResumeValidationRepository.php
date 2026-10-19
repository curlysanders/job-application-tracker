<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Persistence;

use CurlySanders\JobApplicationTracker\Application\UserProfile\PendingResumeValidationRepository;
use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeValidationOutcome;
use CurlySanders\JobApplicationTracker\Domain\Shared\Identifier;
use CurlySanders\JobApplicationTracker\Domain\User\PendingResume;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PendingResumeValidationRepository::class)]
final readonly class DoctrinePendingResumeValidationRepository implements PendingResumeValidationRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findPendingResume(
        string $userId,
        string $validationId,
    ): ?PendingResume {
        if (!Identifier::isValid($userId) || !Identifier::isValid($validationId)) {
            return null;
        }

        $user = $this->entityManager->find(User::class, Identifier::fromString($userId));
        $pending = $user instanceof User ? $user->getPendingResume() : null;

        return null !== $pending && $pending->validationId->equals(Identifier::fromString($validationId)) ? $pending : null;
    }

    public function completePendingResumeValidation(
        string $userId,
        string $validationId,
        ResumeValidationOutcome $outcome,
    ): ?string {
        if (!Identifier::isValid($userId) || !Identifier::isValid($validationId)) {
            return null;
        }

        $userId = Identifier::fromString($userId);
        $validationId = Identifier::fromString($validationId);

        return $this->entityManager->wrapInTransaction(function () use ($userId, $validationId, $outcome): ?string {
            $user = $this->entityManager->find(User::class, $userId, LockMode::PESSIMISTIC_WRITE);
            if (!$user instanceof User) {
                return null;
            }

            return $outcome->valid
                ? $user->promotePendingResume($validationId)
                : $user->rejectPendingResume($validationId, $outcome->failure ?? throw new \LogicException('An invalid resume outcome requires a failure message.'));
        });
    }
}
